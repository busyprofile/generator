<?php

namespace App\Services\RequisiteProviders;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
use App\Services\Order\Features\OrderDetailProvider\Values\Gateway;
use App\Services\Order\Features\OrderDetailProvider\Values\Trader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GarexProvider extends AbstractRequisiteProvider
{
    /**
     * Маппинг банков: код Garex => код PaymentGateway
     * 
     * Слева: код банка от Garex (поле 'bank' в ответе)
     * Справа: код PaymentGateway в вашей БД
     */
    protected const BANK_MAP = [
        // === Основные банки ===
        'sber' => 'sberbank',
        't-bank' => 'tinkoff',
        'alfa-bank' => 'alfabank',
        'vtb' => 'vtb',
        'gazprombank' => 'gazprombank',
        'sovcombank' => 'sovkom',
        'psbank' => 'promsvyaz',
        'pochtabank' => 'pochta',
        'ozonbank' => 'ozon',
        'wb-bank' => 'wb_rub',
        'raiffeisen' => 'raiffeisenbank',
        'mts-bank' => 'mts_bank',
        'rshb' => 'rosselhozbank',
        'zenit' => 'zenit',
        'avangard' => 'avangard',
        'mkb' => 'mkb',
        'domrfbank' => 'domrfbank',
        'rosbank' => 'ros_bank',
        'yoomoney' => 'yoomoney',
        
        // === Другие банки ===
        'unistream' => 'unistream',
        'renessans' => 'rencredit',
        'yandex-bank' => 'jandeks-bank',
        'otp-bank' => 'otp_rub',
        'ingosstrah' => 'ingo',
        'primore' => 'primbank',
        'ak-bars' => 'ak_bars_bank',
        'uralsib' => 'uralsib',
        'atb' => 'aziatsko-tihookeanskij-bank',
        'mtsdengi' => 'mtsdengi_rub',
        'poidem' => 'poidem',
        'nskbl' => 'nskbl',
        'bks-bank' => 'bksbank',
        'el-plat' => 'elpat',
        'abr' => 'abr',
        'solid' => 'solid',
        'dolinskbank' => 'kb-dolinsk',
        'centrinvest' => 'centrinvest',
        'chelinvest' => 'chelinvest',
        'rsb' => 'rsb',
        'sdm-bank' => 'sdm',
        'rncb' => 'rncb',
        'pskb' => 'pskb',
        'bank-hlynov' => 'bank-hlynov',
        'bbr' => 'bbr-bank',
        'tkbbank' => 'tkbbank',
        'cupis' => 'cupis_rub',
        'forabank' => 'fora',
        
        // === Таджикистан ===
        'eskhata' => 'esxata_rub',
        'ibt' => 'mbtjs_rub',
        'arvand' => 'arvard_rub',
        'oriyonbonk' => 'orienbank',
        'tawhidbank' => 'tavhidbank_rub',
        'dc_tj' => 'dushambecity_rub',
        
        // === Операторы связи ===
        'mts' => 'mts',
        'beeline' => 'beeline',
        'megafon' => 'megafon',
        't2' => 't2',
    ];

    public function getName(): string
    {
        return 'garex';
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
        // Все настройки (api_url, api_token, merchant_id) берутся из additional_settings терминала
        // Значения по умолчанию используются только если не заданы в БД
        return array_merge(parent::getDefaultConfig(), [
            'max_response_time_ms' => 30000, // 30 секунд в миллисекундах
            'number_of_retries' => 3,
            'supported_currencies' => ['RUB', 'AZN'],
            'supported_detail_types' => ['card', 'phone', 'account_number'],
            'supported_gateways' => [],
            // Маппинг методов Garex - статичный, не настраивается
            'supported_methods' => [
                'c2c' => 'card',
                'sbp' => 'phone',
                'm2tjs_sbp' => 'phone',
                'm2abh_sbp' => 'phone',
                'm2tjs_c2c' => 'card',
                'm2abh_c2c' => 'card',
                'link2pay' => 'account_number',
                'bank-account' => 'account_number',
                'c2c_wt' => 'card',
                'sbp_wt' => 'phone',
                'sber2sber' => 'phone',
                'alfa2alfa' => 'phone',
                'vtb2vtb' => 'phone',
                'tbank2tbank' => 'phone',
            ],
            'method_mapping' => [
                'card' => 'c2c',
                'phone' => 'sbp',
                'account_number' => 'bank-account',
            ],
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
        
        $this->logInfo('Attempting to get Garex requisites', [
            'merchant_id' => $merchant->id,
            'order_id' => $order?->id,
            'order_uuid' => $order?->uuid,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'detail_type' => $detailType?->value,
            'gateway_id' => $gateway?->id,
        ]);

        // Используем executeWithLogging для автоматического логирования в БД
        return $this->executeWithLogging(
            merchant: $merchant,
            market: $market,
            amount: $amount,
            detailType: $detailType,
            currency: $currency,
            gateway: $gateway,
            transgran: $transgran,
            callback: fn() => $this->makeGarexApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran, $order)
        );
    }

    /**
     * Выполнить API запрос к Garex
     */
    protected function makeGarexApiRequest(
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
            'merchant_id',
            'trader_id',
        ]);
        
        try {
            // Определяем метод платежа на основе типа реквизита
            $method = $this->determinePaymentMethod($detailType, $transgran);
            if (!$method) {
                $this->logWarning('Could not determine payment method for Garex', [
                    'detail_type' => $detailType?->value,
                    'transgran' => $transgran,
                ]);
                return null;
            }

            // Определяем банк если указан гейтвей
            $assetOrBank = $this->determineBank($gateway);

            // Используем uuid ордера как orderId для провайдера
            $orderId = $order?->uuid ?? Str::uuid()->toString();

            // Формируем данные для запроса
            $requestData = [
                'orderId' => $orderId,
                'merchantId' => $this->config['merchant_id'],
                'method' => $method,
                'amount' => $amount->toUnitsInt() / 100, // Конвертируем копейки в рубли
                'currency' => $currency?->getCode() ?? 'RUB',
                'userId' => 'user_' . $merchant->id,
                'userIp' => request()->ip() ?? '127.0.0.1',
                'callbackUri' => $this->config['callback_url'],
            ];

            // Добавляем банк если определен
            if ($assetOrBank) {
                $requestData['assetOrBank'] = $assetOrBank;
            }

            $url = $this->config['api_url'] . '/api/merchant/payments/payin';
            $headers = [
                'Authorization' => 'Bearer ' . $this->config['api_token'],
                'Content-Type' => 'application/json',
            ];

            $this->logInfo('Making Garex API request', [
                'url' => $url,
                'method' => $method,
                'amount' => $requestData['amount'],
                'currency' => $requestData['currency'],
                'order_id' => $orderId,
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
                $this->logError('Garex API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'request_data' => $requestData,
                ]);
                return null;
            }

            $responseData = $response->json();
            
            $this->logInfo('Garex API request successful', [
                'response_data' => $responseData,
                'order_id' => $orderId,
            ]);

            // Проверяем статус ответа (state находится в result)
            $result = $responseData['result'] ?? null;
            $state = $result['state'] ?? null;
            
            if (!$state || $state === 'failed') {
                $this->logWarning('Garex payment creation failed', [
                    'state' => $state ?? 'unknown',
                    'response_data' => $responseData,
                ]);
                return null;
            }

            // Если статус 'created' - реквизиты не выданы
            if ($state === 'created') {
                $this->logInfo('Garex payment created but no requisites provided', [
                    'state' => $state,
                    'order_id' => $orderId,
                ]);
                return null;
            }

            // Получаем ID сделки у провайдера
            $providerOrderId = $result['id'] ?? null;

            // Создаем Detail объект (передаем result и наш orderId для callback)
            return $this->createDetailFromResponse($merchant, $result, $amount, $currency, $method, $orderId, $providerOrderId);

        } catch (\Exception $e) {
            $this->logError('Exception in Garex API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Определить метод платежа на основе типа реквизита
     */
    protected function determinePaymentMethod(?DetailType $detailType, ?bool $transgran): ?string
    {
        if (!$detailType) {
            return 'c2c'; // По умолчанию карта
        }

        $methodMapping = $this->config['method_mapping'];
        $baseMethod = $methodMapping[$detailType->value] ?? 'c2c';

        // Если трансграничный перевод, добавляем соответствующий суффикс
        if ($transgran) {
            // Определяем страну назначения (можно добавить логику)
            $country = 'tjs'; // По умолчанию Таджикистан
            
            if ($baseMethod === 'c2c') {
                return "m2{$country}_c2c";
            } elseif ($baseMethod === 'sbp') {
                return "m2{$country}_sbp";
            }
        }

        return $baseMethod;
    }

    /**
     * Определить банк на основе гейтвея (для запроса к Garex)
     */
    protected function determineBank(?PaymentGateway $gateway): ?string
    {
        if (!$gateway) {
            return null;
        }

        // Обратный маппинг: PaymentGateway код => Garex код
        $gatewayToGarex = array_flip(self::BANK_MAP);
        
        return $gatewayToGarex[$gateway->code] ?? null;
    }

    /**
     * Генерировать уникальный orderId
     */
    protected function generateOrderId(Merchant $merchant): string
    {
        return 'garex_' . $merchant->id . '_' . time() . '_' . Str::random(8);
    }

    /**
     * Создать Detail объект из ответа Garex
     * 
     * @param string $orderId Наш сгенерированный orderId для callback идентификации
     * @param string|null $providerOrderId ID сделки в системе Garex
     */
    protected function createDetailFromResponse(Merchant $merchant, array $responseData, Money $amount, ?Currency $currency, string $method, string $orderId, ?string $providerOrderId = null): Detail
    {
        // Получаем пользователя Garex
        $garexUser = \App\Models\User::find($this->config['trader_id'] ?? 50);
        if (!$garexUser) {
            $this->logError('Garex user not found', [
                'trader_id' => $this->config['trader_id'] ?? 50,
            ]);
            throw new \Exception('Garex user not found');
        }

        // Проверяем и создаем кошелек для пользователя Garex если его нет
        if (!$garexUser->wallet) {
            $wallet = \App\Models\Wallet::create([
                'user_id' => $garexUser->id,
                'trust_balance' => \App\Services\Money\Money::fromPrecision(150000, \App\Services\Money\Currency::USDT()), // 1500 USDT
                'balance' => \App\Services\Money\Money::fromPrecision(0, \App\Services\Money\Currency::USDT()),
                'currency' => \App\Services\Money\Currency::USDT(),
            ]);
            
            $this->logInfo('Created wallet for Garex user', [
                'user_id' => $garexUser->id,
                'wallet_id' => $wallet->id,
                'trust_balance' => $wallet->trust_balance->toPrecision(),
            ]);
        } else {
            $this->logInfo('Garex user already has wallet', [
                'user_id' => $garexUser->id,
                'wallet_id' => $garexUser->wallet->id,
                'trust_balance' => $garexUser->wallet->trust_balance->toPrecision(),
            ]);
        }

        // Получаем или создаем устройство Garex
        $garexDevice = \App\Models\UserDevice::firstOrCreate(
            ['user_id' => $garexUser->id, 'name' => 'Garex Device'],
            [
                'user_id' => $garexUser->id,
                'name' => 'Garex Device',
                'token' => \App\Models\UserDevice::generateToken(),
                'android_id' => 'garex-device-' . time(),
                'device_model' => 'Garex Platform',
                'android_version' => '1.0',
                'manufacturer' => 'Garex',
                'brand' => 'Garex',
                'connected_at' => now(),
            ]
        );

        $this->logInfo('Garex device ensured', [
            'device_id' => $garexDevice->id,
            'user_id' => $garexUser->id,
            'device_name' => $garexDevice->name,
        ]);

        // Определяем банк из ответа Garex
        $garexBank = $responseData['bank'] ?? $responseData['bankCode'] ?? null;
        $garexBankName = $responseData['bankName'] ?? null;
        $garexBankNormalized = $garexBank ? strtolower(trim($garexBank)) : null;
        
        $this->logInfo('Garex returned bank', [
            'garex_bank' => $garexBank,
            'garex_bank_normalized' => $garexBankNormalized,
            'bank_name' => $garexBankName,
        ]);

        // Проверяем наличие банка в ответе
        if (!$garexBankNormalized) {
            $this->logError('Garex returned no bank code', [
                'response_data' => $responseData,
            ]);
            throw new \Exception('Garex returned no bank code');
        }

        // Ищем в маппинге: Garex код => PaymentGateway код
        $gatewayCode = self::BANK_MAP[$garexBankNormalized] ?? null;
        
        // Если не нашли по коду банка, пробуем по имени банка
        if (!$gatewayCode && $garexBankName) {
            $bankNameNormalized = strtolower(trim($garexBankName));
            $gatewayCode = self::BANK_MAP[$bankNameNormalized] ?? null;
        }
        
        if (!$gatewayCode) {
            $this->logError('Bank not found in BANK_MAP', [
                'garex_bank' => $garexBank,
                'garex_bank_normalized' => $garexBankNormalized,
                'bank_name' => $garexBankName,
                'hint' => "Добавьте в BANK_MAP: '{$garexBankNormalized}' => 'ваш_код_gateway',",
            ]);
            throw new \Exception("Garex bank '{$garexBank}' not found in BANK_MAP");
        }

        // Ищем PaymentGateway в БД
        $paymentGateway = \App\Models\PaymentGateway::where('code', $gatewayCode)
            ->where('currency', 'rub')
            ->where('is_active', true)
            ->first();

        if (!$paymentGateway) {
            $this->logError('PaymentGateway not found in database', [
                'garex_bank' => $garexBank,
                'gateway_code' => $gatewayCode,
            ]);
            throw new \Exception("PaymentGateway '{$gatewayCode}' not found in database");
        }

        $this->logInfo('Found PaymentGateway based on Garex bank', [
            'garex_bank' => $garexBank,
            'gateway_code' => $gatewayCode,
            'gateway_id' => $paymentGateway->id,
            'gateway_name' => $paymentGateway->name,
        ]);

        // Определяем тип реквизита на основе метода
        $detailType = $this->getDetailTypeFromMethod($method);
        $detailValue = $responseData['address'] ?? '';
        $initials = $responseData['recipient'] ?? 'Garex';

        // Проверяем существующий PaymentDetail с теми же параметрами
        $existingPaymentDetail = \App\Models\PaymentDetail::where('is_external', true)
            ->where('detail', $detailValue)
            ->where('detail_type', $detailType)
            ->where('user_id', $garexUser->id)
            ->whereHas('paymentGateways', function ($query) use ($paymentGateway) {
                $query->where('payment_gateways.id', $paymentGateway->id);
            })
            ->first();

        if ($existingPaymentDetail) {
            $this->logInfo('Reusing existing external payment detail', [
                'payment_detail_id' => $existingPaymentDetail->id,
                'detail' => $detailValue,
                'detail_type' => $detailType->value,
                'gateway_id' => $paymentGateway->id,
            ]);
            
            $paymentDetail = $existingPaymentDetail;
        } else {
            // Создаем новую запись PaymentDetail с флагом is_external = true
            $paymentDetail = \App\Models\PaymentDetail::create([
                'name' => 'Garex: ' . ($detailValue ?: 'N/A'),
                'detail' => $detailValue,
                'detail_type' => $detailType,
                'initials' => $initials,
                'is_active' => true,
                'is_external' => true, // Помечаем как внешний реквизит
                'daily_limit' => \App\Services\Money\Money::fromPrecision('10000000', $currency ?? \App\Services\Money\Currency::RUB()), // 100k лимит
                'current_daily_limit' => \App\Services\Money\Money::fromPrecision('0', $currency ?? \App\Services\Money\Currency::RUB()),
                'max_pending_orders_quantity' => 1, // Только один заказ на реквизит
                'currency' => $currency ?? \App\Services\Money\Currency::RUB(),
                'user_id' => $garexUser->id,
                'user_device_id' => $garexDevice->id,
            ]);

            // Привязываем к платежному шлюзу
            $paymentDetail->paymentGateways()->attach($paymentGateway->id);
            
            $this->logInfo('Created new external payment detail', [
                'payment_detail_id' => $paymentDetail->id,
                'detail' => $detailValue,
                'detail_type' => $detailType->value,
                'gateway_id' => $paymentGateway->id,
            ]);
        }

        // Создаем Gateway объект с учетом настроек мерчанта
        $gatewayValue = (new \App\Services\Order\Features\OrderDetailProvider\Classes\Utils\GatewayFactory($merchant))
            ->make($paymentGateway);

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
            id: $garexUser->id,
            trustBalance: \App\Services\Money\Money::fromPrecision(150000, \App\Services\Money\Currency::USDT()),
            teamLeaderID: null,
            teamLeaderCommissionRate: 0.0,
            traderCommissionRate: $traderCommissionRate,
            additional_team_leader_ids: [],
        );

        // Берём курс из ответа провайдера
        $garexRate = (float) ($responseData['rate'] ?? 0);
        if ($garexRate <= 0) {
            throw new \Exception('Invalid rate from Garex response');
        }

        $exchangePrice = \App\Services\Money\Money::fromPrecision(
            (string) $garexRate,
            $currency ?? \App\Services\Money\Currency::RUB()
        );

        // Финальная сумма из ответа провайдера
        // amount из ответа Garex приходит в рублях (1580), fromPrecision сам конвертирует в копейки
        $garexAmount = (float) ($responseData['amount'] ?? $amount->toPrecision());
        $finalAmount = \App\Services\Money\Money::fromPrecision(
            (string) $garexAmount,
            $currency ?? \App\Services\Money\Currency::RUB()
        );

        // Рассчитываем сумму в USDT: amount / rate
        $totalProfitValue = $garexAmount / $garexRate;
        $totalProfit = \App\Services\Money\Money::fromPrecision(
            (string) round($totalProfitValue, 6),
            \App\Services\Money\Currency::USDT()
        );

        // Рассчитываем комиссии
        $serviceCommissionRate = $gateway->serviceCommissionRate;
        $totalCommissionRate = $serviceCommissionRate;

        // Общая комиссия в USDT
        $totalCommissionValue = $totalProfitValue * ($totalCommissionRate / 100);
        $totalCommission = \App\Services\Money\Money::fromPrecision(
            (string) round($totalCommissionValue, 6),
            \App\Services\Money\Currency::USDT()
        );

        // Прибыль мерчанта = total_profit - total_commission
        $merchantProfit = $totalProfit->sub($totalCommission);

        // Рассчитываем прибыль трейдера и сервиса из комиссии
        if ($totalCommissionRate > 0 && $traderCommissionRate > 0) {
            $traderProfit = $totalCommission->mul($traderCommissionRate / $totalCommissionRate);
            $serviceProfit = $totalCommission->sub($traderProfit);
        } else {
            $traderProfit = \App\Services\Money\Money::zero(\App\Services\Money\Currency::USDT());
            $serviceProfit = $totalCommission;
        }

        $traderPaidForOrder = $totalProfit->sub($traderProfit);

        $this->logInfo('Calculated profits from Garex response', [
            'garex_rate' => $garexRate,
            'garex_amount' => $garexAmount,
            'total_profit' => $totalProfit->toBeauty(),
            'merchant_profit' => $merchantProfit->toBeauty(),
            'trader_profit' => $traderProfit->toBeauty(),
            'service_profit' => $serviceProfit->toBeauty(),
            'trader_paid_for_order' => $traderPaidForOrder->toBeauty(),
            'trader_commission_rate' => $traderCommissionRate,
            'service_commission_rate' => $serviceCommissionRate,
        ]);

        // Создаем Detail объект с реальным PaymentDetail ID
        $detail = new Detail(
            id: $paymentDetail->id,
            userID: $trader->id,
            paymentGatewayID: $gateway->id,
            userDeviceID: $garexDevice->id,
            dailyLimit: $paymentDetail->daily_limit,
            currentDailyLimit: $paymentDetail->current_daily_limit,
            currency: $currency ?? \App\Services\Money\Currency::RUB(),
            exchangePrice: $exchangePrice,
            totalProfit: $totalProfit,
            serviceProfit: $serviceProfit,
            merchantProfit: $merchantProfit,
            traderProfit: $traderProfit,
            teamLeaderProfit: \App\Services\Money\Money::zero(\App\Services\Money\Currency::USDT()),
            traderCommissionRate: $traderCommissionRate,
            teamLeaderCommissionRate: 0.0,
            traderPaidForOrder: $traderPaidForOrder,
            gateway: $gateway,
            trader: $trader,
            amount: $finalAmount,
            externalRequisites: null,
            providerOrderId: $providerOrderId,
        );

        $this->logInfo('Detail объект создан с реальным PaymentDetail', [
            'detail_id' => $detail->id,
            'payment_detail_id' => $paymentDetail->id,
            'user_id' => $garexUser->id,
            'device_id' => $garexDevice->id,
        ]);

        return $detail;
    }

    /**
     * Определить тип реквизита на основе метода Garex
     */
    protected function getDetailTypeFromMethod(string $method): DetailType
    {
        $methodToDetailTypeMapping = [
            'c2c' => DetailType::CARD,
            'c2c_wt' => DetailType::CARD,
            'm2tjs_c2c' => DetailType::CARD,
            'm2abh_c2c' => DetailType::CARD,
            'sbp' => DetailType::PHONE,
            'sbp_wt' => DetailType::PHONE,
            'm2tjs_sbp' => DetailType::PHONE,
            'm2abh_sbp' => DetailType::PHONE,
            'sber2sber' => DetailType::PHONE,
            'alfa2alfa' => DetailType::PHONE,
            'vtb2vtb' => DetailType::PHONE,
            'tbank2tbank' => DetailType::PHONE,
            'link2pay' => DetailType::ACCOUNT_NUMBER,
            'bank-account' => DetailType::ACCOUNT_NUMBER,
        ];

        return $methodToDetailTypeMapping[$method] ?? DetailType::CARD;
    }

    /**
     * Получить баланс Garex
     */
    public function getBalance(): ?float
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_token'],
                'Content-Type' => 'application/json',
            ])
            ->timeout(10) // Стандартный таймаут 10 секунд для запросов баланса
            ->get($this->config['api_url'] . '/api/merchant/balance');

            if ($response->successful()) {
                $data = $response->json();
                return $data['amount'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            $this->logWarning('Failed to get Garex balance', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function getSupportedCurrencies(): array
    {
        return $this->config['supported_currencies'] ?? ['RUB', 'AZN'];
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
     * Проверить обязательные поля конфигурации
     */
    private function assertConfig(array $required): void
    {
        foreach ($required as $field) {
            if (!isset($this->config[$field]) || $this->config[$field] === '') {
                throw new InvalidArgumentException("Garex config field '{$field}' is missing");
            }
        }
    }
}
