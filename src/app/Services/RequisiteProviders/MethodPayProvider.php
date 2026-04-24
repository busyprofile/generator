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
 * MethodPay Provider - интеграция с внешним MethodPay API (H2H)
 * 
 * Документация API:
 * - POST /api/h2h/order - создание сделки
 * - GET /api/h2h/order/{order_id} - получение сделки
 * - Заголовки: Accept: application/json, Access-Token: token
 */
class MethodPayProvider extends AbstractRequisiteProvider
{
    public function getName(): string
    {
        return 'methodpay';
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
        // Все настройки берутся из additional_settings терминала
        // Значения по умолчанию используются только если не заданы в БД
        return array_merge(parent::getDefaultConfig(), [
            'max_response_time_ms' => 30000, // 30 секунд в миллисекундах
            'number_of_retries' => 3,
            'supported_currencies' => ['RUB', 'USD', 'EUR'],
            'supported_detail_types' => ['card', 'phone', 'account_number'],
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
        
        $this->logInfo('Attempting to get MethodPay requisites', [
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
            callback: fn() => $this->makeMethodPayApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran, $order)
        );
    }

    /**
     * Выполнить API запрос к MethodPay (H2H API)
     */
    protected function makeMethodPayApiRequest(
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
            'access_token',
            'merchant_id',
            'trader_id',
        ]);
        
        try {
            // Используем uuid ордера как external_id для провайдера
            $externalId = $order?->uuid ?? Str::uuid()->toString();

            // Формируем данные для запроса согласно H2H API
            $requestData = [
                'external_id' => $externalId,
                'amount' => $amount->toUnitsInt() / 100, // Конвертируем копейки в рубли (целое число)
                'merchant_id' => $this->config['merchant_id'],
                'callback_url' => $this->config['callback_url'],
            ];

            // Добавляем payment_gateway или currency
            if ($gateway) {
                $requestData['payment_gateway'] = $this->mapGatewayCode($gateway);
            } elseif ($currency) {
                $requestData['currency'] = strtolower($currency->getCode());
            }

            // Добавляем тип реквизита если указан
            if ($detailType) {
                $requestData['payment_detail_type'] = $detailType->value;
            }

            // Добавляем флаг трансграничного платежа
            if ($transgran !== null) {
                $requestData['transgran'] = $transgran;
            }

            $url = $this->config['api_url'] . '/api/h2h/order';
            $headers = [
                'Accept' => 'application/json',
                'Access-Token' => $this->config['access_token'],
                'X-Max-Wait-Ms' => (string) $this->config['max_response_time_ms'],
            ];

            $this->logInfo('Making MethodPay H2H API request', [
                'url' => $url,
                'external_id' => $externalId,
                'amount' => $requestData['amount'],
                'currency' => $requestData['currency'] ?? null,
                'payment_gateway' => $requestData['payment_gateway'] ?? null,
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

            if (!$response->successful()) {
                $this->logError('MethodPay API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'request_data' => $requestData,
                ]);
                return null;
            }

            $responseData = $response->json();
            
            $this->logInfo('MethodPay API request successful', [
                'response_data' => $responseData,
                'external_id' => $externalId,
            ]);

            // Проверяем успешность ответа
            if (empty($responseData['success']) || $responseData['success'] !== true) {
                $this->logWarning('MethodPay API returned unsuccessful response', [
                    'success' => $responseData['success'] ?? false,
                    'message' => $responseData['message'] ?? 'Unknown error',
                ]);
                return null;
            }

            $data = $responseData['data'] ?? null;
            if (!$data) {
                $this->logWarning('MethodPay API returned empty data');
                return null;
            }

            // Проверяем статус сделки
            if (($data['status'] ?? '') !== 'pending') {
                $this->logWarning('MethodPay order has unexpected status', [
                    'status' => $data['status'] ?? 'unknown',
                    'sub_status' => $data['sub_status'] ?? 'unknown',
                ]);
                return null;
            }

            // Проверяем наличие реквизитов
            $paymentDetail = $data['payment_detail'] ?? null;
            if (!$paymentDetail || empty($paymentDetail['detail'])) {
                $this->logWarning('MethodPay order has no payment details', [
                    'order_id' => $data['order_id'] ?? 'unknown',
                ]);
                return null;
            }

            // Получаем order_id из ответа MethodPay (это их внутренний ID сделки)
            $providerOrderId = $data['order_id'] ?? null;

            // Создаем Detail объект (передаём наш externalId для callback идентификации)
            return $this->createDetailFromResponse($merchant, $data, $amount, $currency, $externalId, $providerOrderId);

        } catch (\Exception $e) {
            $this->logError('Exception in MethodPay API request', [
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
                throw new InvalidArgumentException("MethodPay config field '{$field}' is missing");
            }
        }
    }

    /**
     * Генерировать уникальный external_id
     */
    protected function generateExternalId(Merchant $merchant): string
    {
        return 'methodpay_' . $merchant->id . '_' . time() . '_' . Str::random(8);
    }

    /**
     * Маппинг кода гейтвея на код MethodPay
     */
    protected function mapGatewayCode(PaymentGateway $gateway): string
    {
        // Маппинг внутренних кодов на коды MethodPay
        $mapping = [
            'sberbank' => 'sberbank',
            'tinkoff' => 'tinkoff',
            'alfabank' => 'alfabank',
            'vtb' => 'vtb',
            'gazprombank' => 'gazprombank',
            'raiffeisenbank' => 'raiffeisenbank',
            // Добавить другие маппинги по необходимости
        ];

        return $mapping[$gateway->code] ?? $gateway->code;
    }

    /**
     * Создать Detail объект из ответа MethodPay
     * 
     * @param string $externalId Наш сгенерированный external_id для callback идентификации
     * @param string|null $providerOrderId ID сделки в системе MethodPay
     */
    protected function createDetailFromResponse(
        Merchant $merchant, 
        array $responseData, 
        Money $amount, 
        ?Currency $currency,
        string $externalId,
        ?string $providerOrderId = null
    ): Detail {
        // Получаем или создаем пользователя MethodPay
        $methodPayUser = User::find($this->config['trader_id']);
        if (!$methodPayUser) {
            $this->logError('MethodPay user not found', [
                'trader_id' => $this->config['trader_id'],
            ]);
            throw new \Exception('MethodPay user not found');
        }

        // Проверяем и создаем кошелек если нужно
        if (!$methodPayUser->wallet) {
            $wallet = Wallet::create([
                'user_id' => $methodPayUser->id,
                'trust_balance' => Money::fromPrecision(150000, Currency::USDT()),
                'balance' => Money::fromPrecision(0, Currency::USDT()),
                'currency' => Currency::USDT(),
            ]);
            
            $this->logInfo('Created wallet for MethodPay user', [
                'user_id' => $methodPayUser->id,
                'wallet_id' => $wallet->id,
            ]);
        }

        // Получаем или создаем устройство MethodPay
        $methodPayDevice = UserDevice::firstOrCreate(
            ['user_id' => $methodPayUser->id, 'name' => 'MethodPay Device'],
            [
                'user_id' => $methodPayUser->id,
                'name' => 'MethodPay Device',
                'token' => UserDevice::generateToken(),
                'android_id' => 'methodpay-device-' . time(),
                'device_model' => 'MethodPay Platform',
                'android_version' => '1.0',
                'manufacturer' => 'MethodPay',
                'brand' => 'MethodPay',
                'connected_at' => now(),
            ]
        );

        // Получаем данные из ответа
        $paymentDetailData = $responseData['payment_detail'];
        $detailValue = $paymentDetailData['detail'];
        $detailType = $this->mapDetailType($paymentDetailData['detail_type']);
        $initials = $paymentDetailData['initials'] ?? 'MethodPay';

        // Находим PaymentGateway
        $gatewayCode = $responseData['payment_gateway'] ?? null;
        $paymentGateway = $this->findPaymentGateway($gatewayCode, $currency);

        if (!$paymentGateway) {
            throw new \Exception('No suitable PaymentGateway found for MethodPay response');
        }

        // Проверяем существующий PaymentDetail
        $existingPaymentDetail = PaymentDetail::where('is_external', true)
            ->where('detail', $detailValue)
            ->where('detail_type', $detailType)
            ->where('user_id', $methodPayUser->id)
            ->whereHas('paymentGateways', function ($query) use ($paymentGateway) {
                $query->where('payment_gateways.id', $paymentGateway->id);
            })
            ->first();

        if ($existingPaymentDetail) {
            $this->logInfo('Reusing existing MethodPay payment detail', [
                'payment_detail_id' => $existingPaymentDetail->id,
            ]);
            $paymentDetail = $existingPaymentDetail;
        } else {
            // Создаем новую запись PaymentDetail
            $paymentDetail = PaymentDetail::create([
                'name' => 'MethodPay: ' . ($detailValue ?: 'N/A'),
                'detail' => $detailValue,
                'detail_type' => $detailType,
                'initials' => $initials,
                'is_active' => true,
                'is_external' => true,
                'daily_limit' => Money::fromPrecision('10000000', $currency ?? Currency::RUB()),
                'current_daily_limit' => Money::fromPrecision('0', $currency ?? Currency::RUB()),
                'max_pending_orders_quantity' => 1,
                'currency' => $currency ?? Currency::RUB(),
                'user_id' => $methodPayUser->id,
                'user_device_id' => $methodPayDevice->id,
            ]);

            $paymentDetail->paymentGateways()->attach($paymentGateway->id);
            
            $this->logInfo('Created new MethodPay payment detail', [
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
            id: $methodPayUser->id,
            trustBalance: Money::fromPrecision(150000, Currency::USDT()),
            teamLeaderID: null,
            teamLeaderCommissionRate: 0.0,
            traderCommissionRate: $traderCommissionRate,
            additional_team_leader_ids: [],
        );

        // Берём курс и профиты из ответа провайдера
        $exchangePrice = Money::fromPrecision(
            (string) ($responseData['conversion_price'] ?? 0),
            $currency ?? Currency::RUB()
        );

        // Профиты из ответа провайдера (в USDT)
        $totalProfit = Money::fromPrecision(
            (string) ($responseData['profit'] ?? 0),
            Currency::USDT()
        );
        $merchantProfit = Money::fromPrecision(
            (string) ($responseData['merchant_profit'] ?? 0),
            Currency::USDT()
        );

        // Финальная сумма к оплате из ответа (может отличаться от запрошенной)
        // amount из ответа MethodPay приходит в рублях (1040), fromPrecision сам конвертирует в копейки
        $finalAmount = Money::fromPrecision(
            (string) ($responseData['amount'] ?? $amount->toPrecision()),
            $currency ?? Currency::RUB()
        );

        // Рассчитываем комиссии на основе данных от провайдера
        $totalCommission = $totalProfit->sub($merchantProfit);
        $serviceCommissionRate = $gateway->serviceCommissionRate;

        // Рассчитываем прибыль трейдера и сервиса
        if ($serviceCommissionRate > 0 && $traderCommissionRate > 0) {
            $traderProfit = $totalCommission->mul($traderCommissionRate / $serviceCommissionRate);
            $serviceProfit = $totalCommission->sub($traderProfit);
        } else {
            $traderProfit = Money::zero(Currency::USDT());
            $serviceProfit = $totalCommission;
        }

        $traderPaidForOrder = $totalProfit->sub($traderProfit);

        $this->logInfo('Calculated profits from MethodPay response', [
            'conversion_price' => $exchangePrice->toBeauty(),
            'total_profit' => $totalProfit->toBeauty(),
            'merchant_profit' => $merchantProfit->toBeauty(),
            'trader_profit' => $traderProfit->toBeauty(),
            'service_profit' => $serviceProfit->toBeauty(),
            'trader_paid_for_order' => $traderPaidForOrder->toBeauty(),
            'trader_commission_rate' => $traderCommissionRate,
            'service_commission_rate' => $serviceCommissionRate,
        ]);

        // Создаем Detail объект
        return new Detail(
            id: $paymentDetail->id,
            userID: $trader->id,
            paymentGatewayID: $gateway->id,
            userDeviceID: $methodPayDevice->id,
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
     * Маппинг типа реквизита из MethodPay в DetailType
     */
    protected function mapDetailType(string $type): DetailType
    {
        return match ($type) {
            'card' => DetailType::CARD,
            'phone' => DetailType::PHONE,
            'account_number' => DetailType::ACCOUNT_NUMBER,
            default => DetailType::CARD,
        };
    }

    /**
     * Найти подходящий PaymentGateway
     */
    protected function findPaymentGateway(?string $gatewayCode, ?Currency $currency): ?PaymentGateway
    {
        $currencyCode = strtolower($currency?->getCode() ?? 'rub');

        if ($gatewayCode) {
            $paymentGateway = PaymentGateway::where('code', $gatewayCode)
                ->where('currency', $currencyCode)
                ->where('is_active', true)
                ->first();

            if ($paymentGateway) {
                return $paymentGateway;
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
        return $this->config['supported_currencies'] ?? ['RUB', 'USD', 'EUR'];
    }

    protected function getSupportedDetailTypes(): array
    {
        return $this->config['supported_detail_types'] ?? ['card', 'phone', 'account_number'];
    }

    protected function getSupportedGateways(): array
    {
        return $this->config['supported_gateways'] ?? [];
    }

    /**
     * Получить баланс из MethodPay API
     * 
     * GET /api/wallet/balance
     * Headers: Access-Token, Accept: application/json
     * Response: {"success": true, "data": {"balance": "10000.00"}}
     */
    public function getBalance(): ?float
    {
        $apiUrl = $this->config['api_url'] ?? null;
        $accessToken = $this->config['access_token'] ?? null;

        if (!$apiUrl || !$accessToken) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Access-Token' => $accessToken,
            ])
            ->timeout(10)
            ->get(rtrim($apiUrl, '/') . '/api/wallet/balance');

            if ($response->successful()) {
                $data = $response->json();
                if (($data['success'] ?? false) && isset($data['data']['balance'])) {
                    return (float) $data['data']['balance'];
                }
            }

            $this->logWarning('Failed to get balance from MethodPay API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            $this->logError('Exception getting MethodPay balance', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Отменить сделку в MethodPay
     * 
     * PATCH /api/h2h/order/{order_id}/cancel
     * Досрочно закрывает сделку если она находится в статусе pending и не имеет открытых споров.
     * 
     * @param \App\Models\Order $order Ордер для отмены
     * @return bool Успешность операции
     */
    public function cancelOrder(\App\Models\Order $order): bool
    {
        // Используем provider_order_id если есть, иначе external_id
        $providerOrderId = $order->provider_order_id;
        
        if (!$providerOrderId) {
            $this->logWarning('MethodPay cancelOrder: no provider_order_id or external_id', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        $apiUrl = $this->config['api_url'] ?? null;
        $accessToken = $this->config['access_token'] ?? null;

        if (!$apiUrl || !$accessToken) {
            $this->logWarning('MethodPay cancelOrder: missing api_url or access_token');
            return false;
        }

        try {
            $url = rtrim($apiUrl, '/') . '/api/h2h/order/' . $providerOrderId . '/cancel';
            $headers = [
                'Accept' => 'application/json',
                'Access-Token' => $accessToken,
            ];

            $this->logInfo('Cancelling order in MethodPay', [
                'order_id' => $order->id,
                'provider_order_id' => $providerOrderId,
                'url' => $url,
            ]);

            $startTime = microtime(true);
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->patch($url);
            $duration = (microtime(true) - $startTime) * 1000;

            // Логируем запрос
            $this->logHttpRequest(
                'PATCH',
                $url,
                $headers,
                [],
                $response->status(),
                $response->body(),
                $duration
            );

            if ($response->successful()) {
                $data = $response->json();
                if (($data['success'] ?? false) === true) {
                    $this->logInfo('Order cancelled successfully in MethodPay', [
                        'order_id' => $order->id,
                        'provider_order_id' => $providerOrderId,
                        'response' => $data,
                    ]);
                    return true;
                }
                
                $this->logWarning('MethodPay cancel returned unsuccessful response', [
                    'order_id' => $order->id,
                    'provider_order_id' => $providerOrderId,
                    'response' => $data,
                ]);
                return false;
            }

            $this->logWarning('Failed to cancel order in MethodPay', [
                'order_id' => $order->id,
                'provider_order_id' => $providerOrderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            $this->logError('Exception cancelling order in MethodPay', [
                'order_id' => $order->id,
                'provider_order_id' => $providerOrderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
