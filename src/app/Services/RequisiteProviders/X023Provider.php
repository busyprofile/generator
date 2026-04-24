<?php

namespace App\Services\RequisiteProviders;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentDetail;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Wallet;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Classes\Utils\GatewayFactory;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
use App\Services\Order\Features\OrderDetailProvider\Values\Gateway;
use App\Services\Order\Features\OrderDetailProvider\Values\Trader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * X023 Provider - интеграция с внешним X023 API
 * 
 * Документация API:
 * - POST /api/v1/order/ - создание ордера
 * - Авторизация: Bearer token в заголовке Authorization
 * 
 * Статусы:
 * - ACTIVE: ордер активен
 * - CLOSED: ордер завершён успешно
 * - EXPIRED: время истекло
 * - APPEAL: спор открыт
 * - DECLINED: отклонён
 */
class X023Provider extends AbstractRequisiteProvider
{
    public function getName(): string
    {
        return 'x023';
    }

    public function getPriority(): int
    {
        // Приоритет = rate * 10 (меньше rate = выше приоритет)
        if (isset($this->config['rate']) && $this->config['rate'] > 0) {
            return (int) round($this->config['rate'] * 10);
        }
        
        return $this->config['priority'] ?? 100;
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'max_response_time_ms' => 30000, // 30 секунд
            'number_of_retries' => 3,
            'supported_currencies' => ['RUB'],
            'supported_detail_types' => ['card', 'phone'],
            'supported_gateways' => [],
        ]);
    }

    public function getRequisites(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null,
        ?Order $order = null
    ): ?Detail {
        
        $this->logInfo('Attempting to get X023 requisites', [
            'merchant_id' => $merchant->id,
            'order_id' => $order?->id,
            'order_uuid' => $order?->uuid,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'detail_type' => $detailType?->value,
            'gateway_id' => $gateway?->id,
        ]);

        return $this->executeWithLogging(
            merchant: $merchant,
            market: $market,
            amount: $amount,
            detailType: $detailType,
            currency: $currency,
            gateway: $gateway,
            transgran: $transgran,
            callback: fn() => $this->makeX023ApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran, $order)
        );
    }

    /**
     * Выполнить API запрос к X023
     */
    protected function makeX023ApiRequest(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null,
        ?Order $order = null
    ): ?Detail {
        $this->assertConfig([
            'api_url',
            'api_token',
            'trader_id',
        ]);
        
        try {
            // Используем uuid ордера как outside_order_id для провайдера
            $externalId = $order?->uuid ?? Str::uuid()->toString();

            // Определяем is_sbp на основе detailType
            $isSbp = $this->determineIsSbp($detailType);

            // Формируем данные для запроса
            $requestData = [
                'amount' => $amount->toUnitsInt() / 100, // Конвертируем копейки в рубли
                'currency' => strtoupper($currency?->getCode() ?? 'RUB'),
                'is_sbp' => $isSbp,
                'callbackUri' => $this->config['callback_url'] ?? '',
                'outside_order_id' => $externalId,
                'commission' => 0,
                'client_id' => (string) $merchant->id,
            ];

            $url = rtrim($this->config['api_url'], '/') . '/api/v1/order/';
            $headers = [
                'Authorization' => 'Bearer ' . $this->config['api_token'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            $this->logInfo('Making X023 API request', [
                'url' => $url,
                'external_id' => $externalId,
                'amount' => $requestData['amount'],
                'is_sbp' => $isSbp,
            ]);

            // Логируем запрос перед отправкой
            $this->logHttpRequest('POST', $url, $headers, $requestData);

            // Выполняем запрос с retry механизмом
            $startTime = microtime(true);
            $response = $this->withRetry(function () use ($url, $headers, $requestData) {
                return Http::withHeaders($headers)
                    ->timeout((int)($this->config['max_response_time_ms'] / 1000))
                    ->post($url, $requestData);
            });
            $duration = (microtime(true) - $startTime) * 1000;

            // Логируем ответ
            $this->logHttpRequest(
                'POST',
                $url,
                $headers,
                $requestData,
                $response->status(),
                $response->body(),
                $duration
            );

            // X023 возвращает 201 при успешном создании
            if ($response->status() !== 201) {
                $this->logError('X023 API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'request_data' => $requestData,
                ]);
                return null;
            }

            $responseData = $response->json();
            
            $this->logInfo('X023 API request successful', [
                'response_data' => $responseData,
                'external_id' => $externalId,
            ]);

            // Проверяем наличие реквизитов
            $bankDetails = $responseData['bank_details'] ?? null;
            if (!$bankDetails) {
                $this->logWarning('X023 order has no bank_details', [
                    'provider_order_id' => $responseData['id'] ?? 'unknown',
                ]);
                return null;
            }

            // Получаем provider_order_id из ответа X023
            $providerOrderId = $responseData['id'] ?? null;

            // Создаем Detail объект
            return $this->createDetailFromResponse($merchant, $responseData, $amount, $currency, $externalId, $providerOrderId);

        } catch (\Exception $e) {
            $this->logError('Exception in X023 API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Проверить обязательные поля конфигурации
     */
    private function assertConfig(array $required): void
    {
        foreach ($required as $field) {
            if (!isset($this->config[$field]) || $this->config[$field] === '') {
                throw new InvalidArgumentException("X023 config field '{$field}' is missing");
            }
        }
    }

    /**
     * Генерировать уникальный external_id (outside_order_id)
     */
    protected function generateExternalId(Merchant $merchant): string
    {
        return 'x023_' . $merchant->id . '_' . time() . '_' . Str::random(8);
    }

    /**
     * Определить is_sbp на основе типа реквизита
     */
    protected function determineIsSbp(?DetailType $detailType): bool
    {
        if ($detailType === null) {
            return true; // По умолчанию SBP
        }

        return match ($detailType) {
            DetailType::PHONE => true,  // SBP по номеру телефона
            DetailType::CARD => false,  // Карта
            default => true,
        };
    }

    /**
     * Создать Detail объект из ответа X023
     */
    protected function createDetailFromResponse(
        Merchant $merchant, 
        array $responseData, 
        Money $amount, 
        ?Currency $currency,
        string $externalId,
        ?string $providerOrderId = null
    ): Detail {
        // Получаем пользователя X023
        $x023User = User::find($this->config['trader_id']);
        if (!$x023User) {
            $this->logError('X023 user not found', [
                'trader_id' => $this->config['trader_id'],
            ]);
            throw new \Exception('X023 user not found');
        }

        // Проверяем и создаем кошелек если нужно
        if (!$x023User->wallet) {
            $wallet = Wallet::create([
                'user_id' => $x023User->id,
                'trust_balance' => Money::fromPrecision(150000, Currency::USDT()),
                'balance' => Money::fromPrecision(0, Currency::USDT()),
                'currency' => Currency::USDT(),
            ]);
            
            $this->logInfo('Created wallet for X023 user', [
                'user_id' => $x023User->id,
                'wallet_id' => $wallet->id,
            ]);
        }

        // Получаем или создаем устройство X023
        $x023Device = UserDevice::firstOrCreate(
            ['user_id' => $x023User->id, 'name' => 'X023 Device'],
            [
                'user_id' => $x023User->id,
                'name' => 'X023 Device',
                'token' => UserDevice::generateToken(),
                'android_id' => 'x023-device-' . time(),
                'device_model' => 'X023 Platform',
                'android_version' => '1.0',
                'manufacturer' => 'X023',
                'brand' => 'X023',
                'connected_at' => now(),
            ]
        );

        // Получаем данные из ответа
        $bankDetails = $responseData['bank_details']; // Номер карты или телефона
        $isSbp = $responseData['is_sbp'] ?? false;
        $detailType = $isSbp ? DetailType::PHONE : DetailType::CARD;
        $initials = $responseData['fio'] ?? 'X023';
        $bankName = $responseData['bank_name'] ?? null;

        // Находим PaymentGateway
        $paymentGateway = $this->findPaymentGateway($bankName, $currency);

        if (!$paymentGateway) {
            throw new \Exception('No suitable PaymentGateway found for X023 response');
        }

        // Проверяем существующий PaymentDetail
        $existingPaymentDetail = PaymentDetail::where('is_external', true)
            ->where('detail', $bankDetails)
            ->where('detail_type', $detailType)
            ->where('user_id', $x023User->id)
            ->whereHas('paymentGateways', function ($query) use ($paymentGateway) {
                $query->where('payment_gateways.id', $paymentGateway->id);
            })
            ->first();

        if ($existingPaymentDetail) {
            $this->logInfo('Reusing existing X023 payment detail', [
                'payment_detail_id' => $existingPaymentDetail->id,
            ]);
            $paymentDetail = $existingPaymentDetail;
        } else {
            // Создаем новую запись PaymentDetail
            $paymentDetail = PaymentDetail::create([
                'name' => 'X023: ' . ($bankDetails ?: 'N/A'),
                'detail' => $bankDetails,
                'detail_type' => $detailType,
                'initials' => $initials,
                'is_active' => true,
                'is_external' => true,
                'daily_limit' => Money::fromPrecision('10000000', $currency ?? Currency::RUB()),
                'current_daily_limit' => Money::fromPrecision('0', $currency ?? Currency::RUB()),
                'max_pending_orders_quantity' => 1,
                'currency' => $currency ?? Currency::RUB(),
                'user_id' => $x023User->id,
                'user_device_id' => $x023Device->id,
            ]);

            $paymentDetail->paymentGateways()->attach($paymentGateway->id);
            
            $this->logInfo('Created new X023 payment detail', [
                'payment_detail_id' => $paymentDetail->id,
            ]);
        }

        // Создаем Gateway объект
        $gatewayValue = (new GatewayFactory($merchant))->make($paymentGateway);

        $gateway = new Gateway(
            id: $gatewayValue->id,
            code: $gatewayValue->code,
            reservationTime: $gatewayValue->reservationTime,
            serviceCommissionRate: $gatewayValue->serviceCommissionRate,
            traderCommissionRate: $gatewayValue->traderCommissionRate,
            partnerExternalId: null, // external_id не перезаписывается, используем uuid для callback
        );

        // Комиссия трейдера из provider_terminal.rate
        $traderCommissionRate = isset($this->config['rate']) && $this->config['rate'] > 0
            ? (float) $this->config['rate']
            : $gateway->traderCommissionRate;

        // Создаем Trader объект
        $trader = new Trader(
            id: $x023User->id,
            trustBalance: Money::fromPrecision(150000, Currency::USDT()),
            teamLeaderID: null,
            teamLeaderCommissionRate: 0.0,
            traderCommissionRate: $traderCommissionRate,
            additional_team_leader_ids: [],
        );

        // Рассчитываем курс из ответа
        $amountRub = (float) ($responseData['amount'] ?? 0);
        $amountUsdt = (float) ($responseData['amount_in_usdt'] ?? 0);
        $rate = $amountUsdt > 0 ? $amountRub / $amountUsdt : 0;

        $exchangePrice = Money::fromPrecision(
            (string) $rate,
            $currency ?? Currency::RUB()
        );

        // Комиссия от провайдера
        $commission = (float) ($responseData['commission'] ?? 0);

        // Рассчитываем профиты
        $totalProfit = Money::fromPrecision(
            (string) ($amountUsdt * ($traderCommissionRate / 100)),
            Currency::USDT()
        );
        
        $serviceCommissionRate = $gateway->serviceCommissionRate;
        
        if ($serviceCommissionRate > 0 && $traderCommissionRate > 0) {
            $traderProfit = $totalProfit->mul($traderCommissionRate / $serviceCommissionRate);
            $serviceProfit = $totalProfit->sub($traderProfit);
        } else {
            $traderProfit = Money::zero(Currency::USDT());
            $serviceProfit = $totalProfit;
        }

        $merchantProfit = Money::zero(Currency::USDT());
        $traderPaidForOrder = $totalProfit->sub($traderProfit);

        // Финальная сумма
        $finalAmount = Money::fromPrecision(
            (string) $amountRub,
            $currency ?? Currency::RUB()
        );

        $this->logInfo('Calculated profits from X023 response', [
            'rate' => $rate,
            'amount_rub' => $amountRub,
            'amount_usdt' => $amountUsdt,
            'total_profit' => $totalProfit->toBeauty(),
            'trader_profit' => $traderProfit->toBeauty(),
            'service_profit' => $serviceProfit->toBeauty(),
        ]);

        // Создаем Detail объект
        return new Detail(
            id: $paymentDetail->id,
            userID: $trader->id,
            paymentGatewayID: $gateway->id,
            userDeviceID: $x023Device->id,
            dailyLimit: $paymentDetail->daily_limit,
            currentDailyLimit: $paymentDetail->current_daily_limit,
            currency: $currency ?? Currency::RUB(),
            exchangePrice: $exchangePrice,
            totalProfit: $totalProfit,
            serviceProfit: $serviceProfit,
            merchantProfit: $merchantProfit,
            traderProfit: $traderProfit,
            teamLeaderProfit: Money::zero(Currency::USDT()),
            traderCommissionRate: $traderCommissionRate,
            teamLeaderCommissionRate: 0.0,
            traderPaidForOrder: $traderPaidForOrder,
            gateway: $gateway,
            trader: $trader,
            amount: $finalAmount,
            externalRequisites: null,
            providerOrderId: $providerOrderId,
        );
    }

    /**
     * Найти подходящий PaymentGateway
     */
    protected function findPaymentGateway(?string $bankName, ?Currency $currency): ?PaymentGateway
    {
        $currencyCode = strtolower($currency?->getCode() ?? 'rub');

        // Маппинг названий банков на коды
        $bankMapping = [
            'Сбербанк' => 'sberbank',
            'Сбер' => 'sberbank',
            'Тинькофф' => 'tinkoff',
            'Т-Банк' => 'tinkoff',
            'Альфа-Банк' => 'alfabank',
            'Альфа' => 'alfabank',
            'ВТБ' => 'vtb',
            'Газпромбанк' => 'gazprombank',
            'Райффайзен' => 'raiffeisenbank',
        ];

        if ($bankName) {
            $gatewayCode = $bankMapping[$bankName] ?? null;
            
            if ($gatewayCode) {
                $paymentGateway = PaymentGateway::where('code', $gatewayCode)
                    ->where('currency', $currencyCode)
                    ->where('is_active', true)
                    ->first();

                if ($paymentGateway) {
                    return $paymentGateway;
                }
            }
        }

        // Fallback - ищем любой активный гейтвей для валюты
        return PaymentGateway::where('currency', $currencyCode)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->first();
    }

    protected function getSupportedCurrencies(): array
    {
        return $this->config['supported_currencies'] ?? ['RUB'];
    }

    protected function getSupportedDetailTypes(): array
    {
        return $this->config['supported_detail_types'] ?? ['card', 'phone'];
    }

    protected function getSupportedGateways(): array
    {
        return $this->config['supported_gateways'] ?? [];
    }

    /**
     * Получить баланс - не реализовано для X023
     */
    public function getBalance(): ?float
    {
        // X023 не поддерживает получение баланса
        return null;
    }

    /**
     * Отменить ордер в X023 - не реализовано
     */
    public function cancelOrder(\App\Models\Order $order): bool
    {
        // X023 не поддерживает отмену ордера через API
        $this->logWarning('X023 does not support order cancellation via API', [
            'order_id' => $order->id,
        ]);
        return false;
    }
}

