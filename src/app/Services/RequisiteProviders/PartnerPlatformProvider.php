<?php

namespace App\Services\RequisiteProviders;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentDetail;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
use App\Services\Order\Features\OrderDetailProvider\Values\Gateway;
use App\Services\Order\Features\OrderDetailProvider\Values\Trader;
use Illuminate\Support\Facades\Http;

class PartnerPlatformProvider extends AbstractRequisiteProvider
{
    public function getName(): string
    {
        return 'partner_platform';
    }

    public function getPriority(): int
    {
        return 15; // Ниже приоритет чем у внутреннего
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'enabled' => env('PARTNER_PLATFORM_ENABLED', false),
            'api_url' => env('PARTNER_PLATFORM_API_URL', 'https://app.hillcard.net'),
            'api_key' => env('PARTNER_PLATFORM_API_KEY'),
            'timeout' => (int) env('PARTNER_PLATFORM_TIMEOUT', 30),
            'supported_currencies' => ['RUB', 'USD', 'EUR'],
            'supported_detail_types' => ['card', 'phone', 'account_number', 'qr_code'],
            'supported_gateways' => [], // Пустой массив = поддерживаем все
            'min_amount' => (int) env('PARTNER_PLATFORM_MIN_AMOUNT', 100), // 1 рубль в копейках
            'max_amount' => (int) env('PARTNER_PLATFORM_MAX_AMOUNT', 50000000), // 500k рублей в копейках
            'merchant_mapping' => [],
            'default_partner_merchant_id' => env('PARTNER_PLATFORM_DEFAULT_MERCHANT_ID', 'default_merchant'),
            'callback_base_url' => env('PARTNER_PLATFORM_CALLBACK_BASE_URL', config('app.url')),
            'device_token' => env('PARTNER_PLATFORM_DEVICE_TOKEN'),
            'gateway_mapping' => [],
        ]);
    }

    public function supports(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?bool $transgran = null,
        ?Merchant $merchant = null
    ): bool {
        return parent::supports($amount, $currency, $gateway, $detailType, $transgran, $merchant);
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
        
        $this->log('info', 'Attempting to get partner platform requisites', [
            'merchant_id' => $merchant->id,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'detail_type' => $detailType?->value,
            'gateway_id' => $gateway?->id,
            'provider' => 'partner_platform'
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
            callback: fn() => $this->makeApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran)
        );
    }

    /**
     * Выполнить H2H API запрос к партнерской платформе
     */
    protected function makeApiRequest(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null
    ): ?Detail {
        
        // Формируем данные для H2H запроса
        $requestData = [
            'external_id' => 'cascade_' . uniqid() . '_' . time(),
            'amount' => (int)($amount->toUnitsInt() / 100), // Конвертируем копейки в рубли
            'currency' => strtolower($currency?->getCode() ?? 'rub'),
            'merchant_id' => $this->getPartnerMerchantId($merchant),
            'callback_url' => $this->getCallbackUrl(),
        ];

        // Добавляем опциональные параметры
        if ($detailType) {
            $requestData['payment_detail_type'] = $detailType->value;
        }

        if ($gateway) {
            $requestData['payment_gateway'] = $this->mapGatewayToPartner($gateway);
        }

        if ($transgran !== null) {
            $requestData['transgran'] = $transgran;
        }

        $this->log('info', 'Sending H2H request to partner platform', [
            'url' => $this->config['api_url'] . '/api/h2h/order',
            'payload' => $requestData,
            'provider' => 'partner_platform'
        ]);

        // Выполняем H2H запрос
        $response = Http::withHeaders([
                'Access-Token' => $this->config['api_key'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Hill2Platform-H2H/1.0',
            ])
            ->timeout($this->config['timeout'])
            ->post($this->config['api_url'] . '/api/h2h/order', $requestData);

        $this->log('info', 'Partner platform HTTP response received', [
            'status_code' => $response->status(),
            'successful' => $response->successful(),
            'response_size' => strlen($response->body()),
            'provider' => 'partner_platform'
        ]);

        if (!$response->successful()) {
            $this->log('error', 'Partner API HTTP request failed', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'provider' => 'partner_platform'
            ]);
            throw new \Exception("Partner API request failed: " . $response->body());
        }

        $data = $response->json();
        
        $this->log('info', 'Partner platform JSON response parsed', [
            'has_success_key' => array_key_exists('success', $data),
            'success_value' => $data['success'] ?? 'missing',
            'has_data_key' => array_key_exists('data', $data),
            'data_empty' => empty($data['data']),
            'response_keys' => array_keys($data),
            'provider' => 'partner_platform'
        ]);

        if (empty($data['success']) || empty($data['data'])) {
            $this->log('warning', 'Partner platform returned unsuccessful response', [
                'full_response' => $data,
                'success_check' => empty($data['success']) ? 'EMPTY_SUCCESS' : 'SUCCESS_OK',
                'data_check' => empty($data['data']) ? 'EMPTY_DATA' : 'DATA_OK',
                'provider' => 'partner_platform'
            ]);
            return null;
        }

        try {
            $detail = $this->createDetailFromApiResponse($data['data'], $gateway, $amount);
            
            $this->log('info', 'Partner platform requisites successfully created', [
                'detail_id' => $detail->id,
                'external_id' => $data['data']['external_id'] ?? 'unknown',
                'payment_detail' => $data['data']['payment_detail']['detail'] ?? 'unknown',
                'provider' => 'partner_platform'
            ]);
            
            return $detail;
        } catch (\Exception $e) {
            $this->log('error', 'Failed to create payment detail from partner response', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'external_id' => $data['data']['external_id'] ?? 'unknown',
                'provider' => 'partner_platform'
            ]);
            return null;
        }
    }

    /**
     * Создать реальную запись PaymentDetail и Detail объект из ответа партнерской платформы
     */
    protected function createDetailFromApiResponse(array $data, ?PaymentGateway $gateway, Money $amount): Detail
    {
        // Извлекаем информацию из H2H ответа
        $currency = Currency::make(strtoupper($data['currency'] ?? 'RUB'));
        $exchangePrice = Money::fromPrecision((string)($data['conversion_price'] ?? 1), $currency->getCode());
        $profitCurrency = strtoupper($data['profit_currency'] ?? 'USDT');
        
        $totalProfit = Money::fromPrecision((string)($data['profit'] ?? 0), $profitCurrency);
        $merchantProfit = Money::fromPrecision((string)($data['merchant_profit'] ?? 0), $profitCurrency);
        $serviceProfit = Money::fromPrecision((string)($data['service_profit'] ?? 0), $profitCurrency);
        $traderProfit = Money::fromPrecision('0', $profitCurrency); // Партнерские ордера не дают прибыль трейдеру
        
        // Если service_profit = 0, рассчитываем как разность
        if ($serviceProfit->equalsToZero() && $totalProfit->greaterThan($merchantProfit)) {
            $serviceProfit = $totalProfit->sub($merchantProfit);
        }

        // Получаем или создаем партнерского пользователя
        $partnerUser = $this->getPartnerUser();
        
        // Получаем или создаем устройство для партнерского пользователя
        $partnerDevice = $this->getPartnerUserDevice($partnerUser);

        $detailValue = $data['payment_detail']['detail'] ?? '';
        $detailType = DetailType::from($data['payment_detail']['detail_type'] ?? 'phone');
        $gatewayId = $gateway?->id ?? $this->findGatewayIdByCode($data['payment_gateway'] ?? null) ?? 1;

        // Проверяем существующий PaymentDetail с теми же параметрами
        $existingPaymentDetail = PaymentDetail::where('is_external', true)
            ->where('detail', $detailValue)
            ->where('detail_type', $detailType)
            ->where('user_id', $partnerUser->id)
            ->whereHas('paymentGateways', function ($query) use ($gatewayId) {
                $query->where('payment_gateways.id', $gatewayId);
            })
            ->first();

        if ($existingPaymentDetail) {
            $this->log('info', 'Reusing existing external payment detail', [
                'payment_detail_id' => $existingPaymentDetail->id,
                'detail' => $detailValue,
                'detail_type' => $detailType->value,
                'gateway_id' => $gatewayId,
                'provider' => 'partner_platform'
            ]);
            
            $paymentDetail = $existingPaymentDetail;
        } else {
            // Создаем новую запись PaymentDetail с флагом is_external = true
            $paymentDetail = PaymentDetail::create([
                'name' => 'Partner Platform: ' . ($data['payment_detail']['detail'] ?? 'N/A'),
                'detail' => $detailValue,
                'detail_type' => $detailType,
                'initials' => $data['payment_detail']['initials'] ?? 'Partner',
                'is_active' => true,
                'is_external' => true, // Помечаем как внешний реквизит
                'daily_limit' => Money::fromPrecision('10000000', $currency->getCode()), // 100k лимит
                'current_daily_limit' => Money::fromPrecision('0', $currency->getCode()),
                'max_pending_orders_quantity' => 1, // Только один заказ на реквизит
                'currency' => $currency,
                'user_id' => $partnerUser->id,
                'user_device_id' => $partnerDevice->id,
            ]);

            // Привязываем к платежному шлюзу
            $paymentDetail->paymentGateways()->attach($gatewayId);
            
            $this->log('info', 'Created new external payment detail', [
                'payment_detail_id' => $paymentDetail->id,
                'detail' => $detailValue,
                'detail_type' => $detailType->value,
                'gateway_id' => $gatewayId,
                'provider' => 'partner_platform'
            ]);
        }

        // Проверяем что PaymentDetail действительно создался и доступен
        $verifyPaymentDetail = PaymentDetail::find($paymentDetail->id);
        $this->log('debug', 'PaymentDetail verification after creation', [
            'payment_detail_id' => $paymentDetail->id,
            'found_by_find' => $verifyPaymentDetail ? 'YES' : 'NO',
            'user_id' => $paymentDetail->user_id,
            'device_id' => $paymentDetail->user_device_id,
            'is_external' => $paymentDetail->is_external,
            'gateway_attached' => $paymentDetail->paymentGateways()->count(),
            'provider' => 'partner_platform'
        ]);

        // Создаем мок-объекты для системы
        $mockGateway = new Gateway(
            id: $gatewayId,
            code: $data['payment_gateway'] ?? 'partner',
            reservationTime: max(300, ($data['expires_at'] ?? time() + 300) - time()),
            serviceCommissionRate: (float)($data['service_commission_rate_total'] ?? 0),
            traderCommissionRate: (float)($data['trader_commission_rate'] ?? 0),
            partnerExternalId: $data['external_id'] ?? null,
        );

        $mockTrader = new Trader(
            id: $partnerUser->id,
            trustBalance: Money::fromPrecision('1000000', Currency::USDT()->getCode()),
            teamLeaderID: null,
            teamLeaderCommissionRate: 0.0,
            traderCommissionRate: $mockGateway->traderCommissionRate,
        );

        // Рассчитываем реальную сумму для резервирования в USDT
        // Конвертируем сумму ордера в USDT по курсу обмена от партнера
        $orderAmountRub = $amount->toUnitsInt() / 100; // Конвертируем из копеек в рубли
        $exchangeRate = (float)($data['conversion_price'] ?? 80); // Курс RUB/USDT
        $amountInUsdt = $orderAmountRub / $exchangeRate;
        
        $traderPaidForOrder = Money::fromPrecision((string)round($amountInUsdt, 2), Currency::USDT()->getCode());
        
        $this->log('info', 'Calculated trader payment amount', [
            'order_amount_rub' => $orderAmountRub,
            'exchange_rate' => $exchangeRate,
            'amount_in_usdt' => $amountInUsdt,
            'trader_paid_rounded' => $traderPaidForOrder->toPrecision(),
            'provider' => 'partner_platform'
        ]);

        // Проверяем достаточность средств у партнерского пользователя
        if ($partnerUser->wallet) {
            $currentBalance = $partnerUser->wallet->trust_balance;
            if ($currentBalance->lessThan($traderPaidForOrder)) {
                $this->log('error', 'Insufficient balance for partner user', [
                    'user_id' => $partnerUser->id,
                    'current_balance' => $currentBalance->toPrecision(),
                    'required_amount' => $traderPaidForOrder->toPrecision(),
                    'deficit' => $traderPaidForOrder->sub($currentBalance)->toPrecision(),
                    'provider' => 'partner_platform'
                ]);
                
                throw new \Exception('Insufficient balance for partner user: need ' . $traderPaidForOrder->toPrecision() . ' USDT, have ' . $currentBalance->toPrecision() . ' USDT');
            }
            
            $this->log('info', 'Partner user balance check passed', [
                'user_id' => $partnerUser->id,
                'current_balance' => $currentBalance->toPrecision(),
                'required_amount' => $traderPaidForOrder->toPrecision(),
                'remaining_after' => $currentBalance->sub($traderPaidForOrder)->toPrecision(),
                'provider' => 'partner_platform'
            ]);
        }

        $this->log('info', 'Created external payment detail from partner platform response', [
            'payment_detail_id' => $paymentDetail->id,
            'detail' => $data['payment_detail']['detail'] ?? 'N/A',
            'gateway' => $data['payment_gateway'] ?? 'unknown',
            'total_profit' => $totalProfit->toPrecision() . ' ' . $profitCurrency,
            'is_external' => true,
            'provider' => 'partner_platform'
        ]);

        return new Detail(
            id: $paymentDetail->id, // Реальный ID созданной записи
            userID: $paymentDetail->user_id,
            paymentGatewayID: $mockGateway->id,
            userDeviceID: $paymentDetail->user_device_id,
            dailyLimit: $paymentDetail->daily_limit,
            currentDailyLimit: $paymentDetail->current_daily_limit,
            currency: $paymentDetail->currency,
            exchangePrice: $exchangePrice,
            totalProfit: $totalProfit,
            serviceProfit: $serviceProfit,
            merchantProfit: $merchantProfit,
            traderProfit: $traderProfit,
            teamLeaderProfit: Money::fromPrecision('0', $profitCurrency),
            traderCommissionRate: $mockGateway->traderCommissionRate,
            teamLeaderCommissionRate: 0.0,
            traderPaidForOrder: $traderPaidForOrder,
            gateway: $mockGateway,
            trader: $mockTrader,
            amount: $amount,
        );
    }

    /**
     * Получить или создать партнерского пользователя
     */
    protected function getPartnerUser(): User
    {
        // Используем существующего партнерского пользователя с ID 51 (partner@gmail.com)
        $user = User::find(51);
        
        if (!$user) {
            // Если пользователь 51 не найден, создаем нового
            $user = User::firstOrCreate(
                ['email' => 'partner-platform@hillcard.net'],
                [
                    'name' => 'Partner Platform User',
                    'password' => bcrypt('unused'),
                    'email_verified_at' => now(),
                    'is_online' => true,
                ]
            );
            
            // Назначаем роль Trader если её нет
            if (!$user->hasRole('Trader')) {
                $user->assignRole('Trader');
            }
        }

        // Создаем кошелек если его нет
        if (!$user->wallet) {
            services()->wallet()->create($user);
            $user->refresh();
            
            $this->log('info', 'Created wallet for partner user', [
                'user_id' => $user->id,
                'wallet_id' => $user->wallet?->id,
                'provider' => 'partner_platform'
            ]);
        }
        
        // Проверяем состояние кошелька
        $wallet = $user->wallet;
        if ($wallet) {
            $this->log('debug', 'Partner user wallet status', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'trust_balance' => $wallet->trust_balance?->toPrecision() ?? 'NULL',
                'merchant_balance' => $wallet->merchant_balance?->toPrecision() ?? 'NULL',
                'provider' => 'partner_platform'
            ]);
        } else {
            $this->log('warning', 'Partner user has no wallet after creation attempt', [
                'user_id' => $user->id,
                'provider' => 'partner_platform'
            ]);
        }

        return $user;
    }

    /**
     * Получить или создать устройство для партнерского пользователя
     */
    protected function getPartnerUserDevice(User $user): UserDevice
    {
        return UserDevice::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Partner Platform Device'],
            [
                'user_id' => $user->id,
                'name' => 'Partner Platform Device',
                'device_id' => 'partner-platform-device',
                'token' => $this->config['device_token'] ?? 'partner-platform-token-' . time(),
                'user_agent' => 'PartnerPlatform/1.0',
                'last_activity_at' => now(),
            ]
        );
    }

    /**
     * Получить ID мерчанта в партнерской системе
     */
    protected function getPartnerMerchantId(Merchant $merchant): string
    {
        // Проверяем маппинг мерчантов
        $mapping = $this->config['merchant_mapping'] ?? [];
        if (isset($mapping[$merchant->id])) {
            return $mapping[$merchant->id];
        }

        // Используем дефолтный ID мерчанта
        return $this->config['default_partner_merchant_id'] ?? 'default_merchant';
    }

    /**
     * Маппинг шлюза к коду партнерской системы
     */
    protected function mapGatewayToPartner(PaymentGateway $gateway): ?string
    {
        $mapping = $this->config['gateway_mapping'] ?? [];
        return $mapping[$gateway->id] ?? null;
    }

    /**
     * Найти ID шлюза по коду партнера
     */
    protected function findGatewayIdByCode(?string $partnerGatewayCode): ?int
    {
        if (!$partnerGatewayCode) {
            $this->log('debug', 'Partner gateway code is null, using default gateway', [
                'default_gateway_id' => 1,
                'provider' => 'partner_platform'
            ]);
            return 1; // Дефолтный шлюз
        }

        // Простой маппинг кодов к ID
        $codeToIdMapping = [
            'sberbank' => 1,
            'tinkoff' => 3,
            'vtb' => 4,
            'alfa' => 2,
            'alfabank' => 2, // Альфа-Банк (полное название)
            'raiffeisen' => 5,
        ];

        $gatewayId = $codeToIdMapping[$partnerGatewayCode] ?? 1;

        $this->log('debug', 'Gateway code mapping', [
            'partner_gateway_code' => $partnerGatewayCode,
            'mapped_gateway_id' => $gatewayId,
            'found_in_mapping' => isset($codeToIdMapping[$partnerGatewayCode]) ? 'YES' : 'NO',
            'provider' => 'partner_platform'
        ]);

        return $gatewayId;
    }

    /**
     * Получить URL для callback от партнерской платформы
     */
    protected function getCallbackUrl(): string
    {
        // Используем callback_url из конфига (формируется автоматически с UUID терминала)
        if (!empty($this->config['callback_url'])) {
            return $this->config['callback_url'];
        }
        
        // Fallback для обратной совместимости
        $baseUrl = $this->config['callback_base_url'] ?? config('app.url');
        return rtrim($baseUrl, '/') . '/api/callback/' . ($this->config['provider_terminal_uuid'] ?? 'unknown');
    }

    protected function getSupportedCurrencies(): array
    {
        return $this->config['supported_currencies'] ?? ['RUB'];
    }

    protected function getSupportedDetailTypes(): array
    {
        return $this->config['supported_detail_types'] ?? ['phone'];
    }

    protected function getSupportedGateways(): array
    {
        return $this->config['supported_gateways'] ?? [];
    }
} 