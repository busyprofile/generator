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
 * AlphaPay Provider - интеграция с AlphaPay API (app.cash)
 * 
 * Документация API:
 * - POST /api/v2/s2s/invoices/set/ - создание счета с предустановленным способом оплаты
 * - GET /api/v1/s2s/invoice/<uuid:id>/ - получение информации по счету
 * - POST /api/v1/s2s/invoice/<uuid:id>/cancel/ - отмена счета
 * - GET /api/v1/s2s/balance/<currency>/ - баланс
 * 
 * Авторизация: HMAC (APIKEY, SIGNATURE, NONCE заголовки)
 * SIGNATURE = HMAC-SHA256(api_key + nonce, secret).hexdigest().upper()
 */
class AlphaPayProvider extends AbstractRequisiteProvider
{
    /**
     * Маппинг банков: ID AlphaPay => код PaymentGateway
     * 
     * Слева: ID банка от AlphaPay (поле 'bank.id' в ответе)
     * Справа: код PaymentGateway в вашей БД
     */
    protected const BANK_MAP = [
        // === Основные банки ===
        1 => 'otp_rub',              // ОТП Банк
        2 => 'tinkoff',              // Т-Банк
        3 => 'bank-sinara',          // Банк Синара
        4 => 'uralsib',              // БАНК УРАЛСИБ
        5 => 'gazprombank',          // Газпромбанк
        6 => 'jandeks-bank',         // Яндекс Банк
        7 => 'rosselhozbank',        // Россельхозбанк
        8 => 'ros_bank',             // РОСБАНК
        9 => 'alfabank',             // АЛЬФА-БАНК
        10 => 'rncb',                // РНКБ Банк
        11 => 'promsvyaz',           // Промсвязьбанк
        12 => 'sberbank',            // Сбербанк
        13 => 'rsb',                 // Банк Русский Стандарт
        14 => 'ak_bars_bank',        // АК БАРС БАНК
        16 => 'vtb',                 // Банк ВТБ
        17 => 'cifra',               // Цифра банк
        18 => 'solid',               // Банк Солидарность
        19 => 'mkb',                 // МОСКОВСКИЙ КРЕДИТНЫЙ БАНК
        20 => 'abr',                 // АБ РОССИЯ
        21 => 'unistream',           // КБ ЮНИСТРИМ
        22 => 'bank-sinara',         // Банк Синара ТЕСТ
        23 => 'sovkom',              // Совкомбанк
        24 => 'ozon',                // Озон Банк
        25 => 'ingo',                // Ингосстрах Банк
        26 => 'sngb',                // БАНК СНГБ
        27 => 'mts_bank',            // МТС-Банк
        28 => 'kb-dolinsk',          // Долинск
        29 => 'zenit',               // Банк ЗЕНИТ
        30 => 'vbrr',                // Банк ВБРР
        31 => 'ubrib',               // КБ УБРиР
        32 => 'domrfbank',           // Банк ДОМ.РФ
        33 => 'novikom',             // Банк НОВИКОМ
        34 => 'rencredit',           // Ренессанс Банк
        35 => 'mtsdengi_rub',        // МТС-Деньги (ЭКСИ-Банк)
        36 => 'bankofkazan',         // КБЭР Банк Казани
        37 => 'bbr-bank',            // ББР Банк
        38 => 'chelinvest',          // ЧЕЛЯБИНВЕСТБАНК
        39 => 'bksbank',             // БКС Банк
        40 => 'kkbank',              // КБ Кубань Кредит
        41 => 'raiffeisenbank',      // Райффайзенбанк
        42 => 'bspb',                // Банк Санкт-Петербург
        43 => 'pochta',              // Почта Банк
        44 => 'pskb',                // СКБ Приморья Примсоцбанк
        45 => 'dvbank',              // Дальневосточный банк
        47 => 'tkbbank',             // Транскапиталбанк
        48 => 'centrinvest',         // КБ Центр-инвест
        49 => 'fora',                // АКБ ФОРА-БАНК
        50 => 'avito',               // Авито Кошелек
        52 => 'kaspi_kzt',           // KASPI BANK JSC
        
        // === Таджикистан / СНГ ===
        51 => 'dushambecity_rub',    // Душанбе сити
        53 => 'alif_rub',            // Алиф Банк
        54 => 'esxata_rub',          // Эсхата банк
        55 => 'norvikbank',          // Норвик Банк
        56 => 'spitamen_rub',        // Спитамен Банк
        
        // === Другие банки ===
        58 => 'aziatsko-tihookeanskij-bank', // Азиатско-Тихоокеанский Банк
        59 => 'absolute_bank',       // АКБ Абсолют Банк
        60 => 'nskbl',               // Банк Левобережный
        61 => 'akcept',              // Банк Акцепт
        62 => 'primbank',            // АКБ Приморье
        63 => 'akibank',             // АКИБАНК
        64 => 'finam',               // Банк ФИНАМ
        65 => 'genbank',             // ГЕНБАНК
        67 => 'cmrbank',             // ЦМРБанк
        68 => 'ns-bank',             // Банк Национальный стандарт
        69 => 'crediteurope',        // Кредит Европа Банк
        70 => 'bank-ekaterinburg',   // Банк Екатеринбург
        71 => 'yoomoney',            // НКО ЮМани
        72 => 'wb_rub',              // Вайлдберриз Банк
        76 => 'cupis_rub',           // Кошелек ЦУПИС
        80 => 'sevnb',               // Северный Народный Банк
    ];

    /**
     * Маппинг DetailType на payment_type AlphaPay
     */
    protected const DETAIL_TYPE_TO_PAYMENT_TYPE = [
        'card' => 'Card',
        'phone' => 'SBP',
        'account_number' => 'Wire',
    ];

    public function getName(): string
    {
        return 'alphapay';
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
            'supported_detail_types' => ['card', 'phone', 'account_number'],
            'supported_gateways' => [],
            'invoice_ttl' => 900, // 15 минут по умолчанию
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
        
        $this->logInfo('Attempting to get AlphaPay requisites', [
            'merchant_id' => $merchant->id,
            'order_id' => $order?->id,
            'order_uuid' => $order?->uuid,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'detail_type' => $detailType?->value,
            'gateway_id' => $gateway?->id,
            'transgran' => $transgran,
        ]);

        return $this->executeWithLogging(
            merchant: $merchant,
            market: $market,
            amount: $amount,
            detailType: $detailType,
            currency: $currency,
            gateway: $gateway,
            transgran: $transgran,
            callback: fn() => $this->makeAlphaPayApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran, $order)
        );
    }

    /**
     * Выполнить API запрос к AlphaPay
     */
    protected function makeAlphaPayApiRequest(
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
            'api_key',
            'secret_key',
            'shop_id',
            'trader_id',
        ]);
        
        try {
            // Используем uuid ордера как operation_id для провайдера
            $operationId = $order?->uuid ?? Str::uuid()->toString();
            $clientId = (string) time();

            // Определяем payment_type на основе типа реквизита
            $paymentType = $this->determinePaymentType($detailType, $transgran);

            // Формируем данные для запроса согласно API v2
            $requestData = [
                'shop' => $this->config['shop_id'],
                'pair' => 'USDT-RUB',
                'amount' => (string) ($amount->toUnitsInt() / 100), // Конвертируем копейки в рубли
                'redirect_url' => $this->config['callback_url'] ?? config('app.url'),
                'client_id' => $clientId,
                'client_type' => 2, // Вторичный трафик
                'operation_id' => $operationId,
                'payment_type' => $paymentType,
            ];

            // Добавляем банк если указан gateway
            if ($gateway) {
                $bankId = $this->mapGatewayToBank($gateway);
                if ($bankId) {
                    $requestData['bank'] = $bankId;
                }
            } else {
                $requestData['bank'] = 0; // Все доступные банки
            }

            // Добавляем параметры для трансграничных переводов
            if ($transgran && $paymentType === 'TPay') {
                // По умолчанию Таджикистан, можно расширить логику
                $requestData['extra_payment_type'] = 'tpay_tajikistan';
            }

            $url = rtrim($this->config['api_url'], '/') . '/api/v2/s2s/invoices/set/';
            $headers = $this->buildAuthHeaders();

            $this->logInfo('Making AlphaPay API request', [
                'url' => $url,
                'operation_id' => $operationId,
                'amount' => $requestData['amount'],
                'payment_type' => $paymentType,
                'bank' => $requestData['bank'] ?? 0,
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
                $this->logError('AlphaPay API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'request_data' => $requestData,
                ]);
                return null;
            }

            $responseData = $response->json();
            
            $this->logInfo('AlphaPay API request successful', [
                'response_data' => $responseData,
                'operation_id' => $operationId,
            ]);

            // Проверяем статус ответа (state должен быть 2 - WAITING_FOR_PAYMENT)
            $state = $responseData['state'] ?? null;
            if ($state !== 2) {
                $this->logWarning('AlphaPay invoice has unexpected state', [
                    'state' => $state,
                    'expected' => 2,
                    'invoice_id' => $responseData['id'] ?? 'unknown',
                ]);
                
                // State 1 = CREATED (реквизиты не выбраны) - тоже ошибка для нас
                if ($state === 1) {
                    $this->logWarning('AlphaPay invoice created but no requisites assigned');
                    return null;
                }
                
                return null;
            }

            // Проверяем наличие реквизитов
            $phoneNumber = $responseData['phone_number'] ?? null;
            $cardNumber = $responseData['card_number'] ?? null;
            $bankAccount = $responseData['bank_account'] ?? null;
            $requisite = $responseData['requisite'] ?? null;

            if (!$phoneNumber && !$cardNumber && !$bankAccount) {
                $this->logWarning('AlphaPay invoice has no payment requisites', [
                    'invoice_id' => $responseData['id'] ?? 'unknown',
                ]);
                return null;
            }

            // Получаем invoice_id из ответа AlphaPay
            $providerOrderId = $responseData['id'] ?? null;

            // Создаем Detail объект
            return $this->createDetailFromResponse($merchant, $responseData, $amount, $currency, $operationId, $providerOrderId);

        } catch (\Exception $e) {
            $this->logError('Exception in AlphaPay API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Построить заголовки авторизации HMAC
     */
    protected function buildAuthHeaders(): array
    {
        $apiKey = $this->config['api_key'];
        $secretKey = $this->config['secret_key'];
        
        // NONCE - timestamp в миллисекундах
        $nonce = (string) round(microtime(true) * 1000);
        
        // SIGNATURE = HMAC-SHA256(api_key + nonce, secret).upper()
        $message = $apiKey . $nonce;
        $signature = strtoupper(hash_hmac('sha256', $message, $secretKey));

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'APIKEY' => $apiKey,
            'SIGNATURE' => $signature,
            'NONCE' => $nonce,
        ];
    }

    /**
     * Проверить обязательные поля конфигурации
     */
    private function assertConfig(array $required): void
    {
        foreach ($required as $field) {
            if (!isset($this->config[$field]) || $this->config[$field] === '') {
                throw new InvalidArgumentException("AlphaPay config field '{$field}' is missing");
            }
        }
    }

    /**
     * Генерировать уникальный operation_id
     */
    protected function generateOperationId(Merchant $merchant): string
    {
        return 'alphapay_' . $merchant->id . '_' . time() . '_' . Str::random(8);
    }

    /**
     * Определить payment_type на основе типа реквизита
     */
    protected function determinePaymentType(?DetailType $detailType, ?bool $transgran): string
    {
        // Если трансграничный - используем TPay
        if ($transgran) {
            return 'TPay';
        }

        if (!$detailType) {
            return 'SBP'; // По умолчанию СБП
        }

        return self::DETAIL_TYPE_TO_PAYMENT_TYPE[$detailType->value] ?? 'SBP';
    }

    /**
     * Маппинг PaymentGateway на ID банка AlphaPay
     */
    protected function mapGatewayToBank(PaymentGateway $gateway): ?int
    {
        // Обратный маппинг: код PaymentGateway -> ID банка AlphaPay
        $gatewayToBank = array_flip(self::BANK_MAP);
        
        return $gatewayToBank[$gateway->code] ?? null;
    }

    /**
     * Создать Detail объект из ответа AlphaPay
     */
    protected function createDetailFromResponse(
        Merchant $merchant,
        array $responseData,
        Money $amount,
        ?Currency $currency,
        string $operationId,
        ?string $providerOrderId = null
    ): Detail {
        // Получаем пользователя AlphaPay
        $alphaPayUser = User::find($this->config['trader_id']);
        if (!$alphaPayUser) {
            $this->logError('AlphaPay user not found', [
                'trader_id' => $this->config['trader_id'],
            ]);
            throw new \Exception('AlphaPay user not found');
        }

        // Проверяем и создаем кошелек если нужно
        if (!$alphaPayUser->wallet) {
            $wallet = Wallet::create([
                'user_id' => $alphaPayUser->id,
                'trust_balance' => Money::fromPrecision(150000, Currency::USDT()),
                'balance' => Money::fromPrecision(0, Currency::USDT()),
                'currency' => Currency::USDT(),
            ]);
            
            $this->logInfo('Created wallet for AlphaPay user', [
                'user_id' => $alphaPayUser->id,
                'wallet_id' => $wallet->id,
            ]);
        }

        // Получаем или создаем устройство AlphaPay
        $alphaPayDevice = UserDevice::firstOrCreate(
            ['user_id' => $alphaPayUser->id, 'name' => 'AlphaPay Device'],
            [
                'user_id' => $alphaPayUser->id,
                'name' => 'AlphaPay Device',
                'token' => UserDevice::generateToken(),
                'android_id' => 'alphapay-device-' . time(),
                'device_model' => 'AlphaPay Platform',
                'android_version' => '1.0',
                'manufacturer' => 'AlphaPay',
                'brand' => 'AlphaPay',
                'connected_at' => now(),
            ]
        );

        // Определяем тип и значение реквизита из ответа
        $detailValue = null;
        $detailType = null;
        
        // Проверяем type из ответа (массив доступных способов оплаты)
        $types = $responseData['type'] ?? [];
        $paymentType = $responseData['payment_type'] ?? null;

        if (!empty($responseData['phone_number'])) {
            $detailValue = $responseData['phone_number'];
            $detailType = DetailType::PHONE;
        } elseif (!empty($responseData['card_number'])) {
            $detailValue = $responseData['card_number'];
            $detailType = DetailType::CARD;
        } elseif (!empty($responseData['bank_account'])) {
            $detailValue = $responseData['bank_account'];
            $detailType = DetailType::ACCOUNT_NUMBER;
        }

        if (!$detailValue || !$detailType) {
            throw new \Exception('No valid requisites in AlphaPay response');
        }

        $initials = $responseData['requisite'] ?? 'AlphaPay';

        // Находим PaymentGateway на основе банка из ответа
        $bank = $responseData['bank'] ?? null;
        $paymentGateway = $this->findPaymentGateway($bank, $currency);

        if (!$paymentGateway) {
            throw new \Exception('No suitable PaymentGateway found for AlphaPay response');
        }

        // Проверяем существующий PaymentDetail
        $existingPaymentDetail = PaymentDetail::where('is_external', true)
            ->where('detail', $detailValue)
            ->where('detail_type', $detailType)
            ->where('user_id', $alphaPayUser->id)
            ->whereHas('paymentGateways', function ($query) use ($paymentGateway) {
                $query->where('payment_gateways.id', $paymentGateway->id);
            })
            ->first();

        if ($existingPaymentDetail) {
            $this->logInfo('Reusing existing AlphaPay payment detail', [
                'payment_detail_id' => $existingPaymentDetail->id,
            ]);
            $paymentDetail = $existingPaymentDetail;
        } else {
            // Создаем новую запись PaymentDetail
            $paymentDetail = PaymentDetail::create([
                'name' => 'AlphaPay: ' . ($detailValue ?: 'N/A'),
                'detail' => $detailValue,
                'detail_type' => $detailType,
                'initials' => $initials,
                'is_active' => true,
                'is_external' => true,
                'daily_limit' => Money::fromPrecision('10000000', $currency ?? Currency::RUB()),
                'current_daily_limit' => Money::fromPrecision('0', $currency ?? Currency::RUB()),
                'max_pending_orders_quantity' => 1,
                'currency' => $currency ?? Currency::RUB(),
                'user_id' => $alphaPayUser->id,
                'user_device_id' => $alphaPayDevice->id,
            ]);

            $paymentDetail->paymentGateways()->attach($paymentGateway->id);
            
            $this->logInfo('Created new AlphaPay payment detail', [
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
            id: $alphaPayUser->id,
            trustBalance: Money::fromPrecision(150000, Currency::USDT()),
            teamLeaderID: null,
            teamLeaderCommissionRate: 0.0,
            traderCommissionRate: $traderCommissionRate,
            additional_team_leader_ids: [],
        );

        // Берём курс из ответа провайдера (price - курс USDT-RUB)
        $alphaPayRate = (float) ($responseData['price'] ?? 0);
        if ($alphaPayRate <= 0) {
            throw new \Exception('Invalid rate from AlphaPay response');
        }

        $exchangePrice = Money::fromPrecision(
            (string) $alphaPayRate,
            $currency ?? Currency::RUB()
        );

        // Финальная сумма из ответа провайдера (amount в рублях)
        $alphaPayAmount = (float) ($responseData['amount'] ?? $amount->toPrecision());
        $finalAmount = Money::fromPrecision(
            (string) $alphaPayAmount,
            $currency ?? Currency::RUB()
        );

        // Рассчитываем сумму в USDT: amount / rate
        $totalProfitValue = $alphaPayAmount / $alphaPayRate;
        $totalProfit = Money::fromPrecision(
            (string) round($totalProfitValue, 6),
            Currency::USDT()
        );

        // Рассчитываем комиссии
        $serviceCommissionRate = $gateway->serviceCommissionRate;
        $totalCommissionRate = $serviceCommissionRate;

        // Общая комиссия в USDT
        $totalCommissionValue = $totalProfitValue * ($totalCommissionRate / 100);
        $totalCommission = Money::fromPrecision(
            (string) round($totalCommissionValue, 6),
            Currency::USDT()
        );

        // Прибыль мерчанта = total_profit - total_commission
        $merchantProfit = $totalProfit->sub($totalCommission);

        // Рассчитываем прибыль трейдера и сервиса из комиссии
        if ($totalCommissionRate > 0 && $traderCommissionRate > 0) {
            $traderProfit = $totalCommission->mul($traderCommissionRate / $totalCommissionRate);
            $serviceProfit = $totalCommission->sub($traderProfit);
        } else {
            $traderProfit = Money::zero(Currency::USDT());
            $serviceProfit = $totalCommission;
        }

        $traderPaidForOrder = $totalProfit->sub($traderProfit);

        $this->logInfo('Calculated profits from AlphaPay response', [
            'alphapay_rate' => $alphaPayRate,
            'alphapay_amount' => $alphaPayAmount,
            'total_profit' => $totalProfit->toBeauty(),
            'merchant_profit' => $merchantProfit->toBeauty(),
            'trader_profit' => $traderProfit->toBeauty(),
            'service_profit' => $serviceProfit->toBeauty(),
            'trader_paid_for_order' => $traderPaidForOrder->toBeauty(),
        ]);

        // Создаем Detail объект
        return new Detail(
            id: $paymentDetail->id,
            userID: $trader->id,
            paymentGatewayID: $gateway->id,
            userDeviceID: $alphaPayDevice->id,
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
     * Найти подходящий PaymentGateway на основе банка из ответа AlphaPay
     * 
     * @throws \Exception если банк не найден в BANK_MAP или PaymentGateway не существует
     */
    protected function findPaymentGateway(?array $bank, ?Currency $currency): PaymentGateway
    {
        $currencyCode = strtolower($currency?->getCode() ?? 'rub');
        $bankId = $bank['id'] ?? null;
        $bankName = $bank['name'] ?? 'N/A';

        $this->logInfo('AlphaPay returned bank', [
            'bank_id' => $bankId,
            'bank_name' => $bankName,
        ]);

        if (!$bankId) {
            $this->logError('AlphaPay returned no bank ID', [
                'bank' => $bank,
            ]);
            throw new \Exception('AlphaPay returned no bank ID');
        }

        $gatewayCode = self::BANK_MAP[$bankId] ?? null;
        
        if (!$gatewayCode) {
            $this->logError('Bank ID not in BANK_MAP', [
                'bank_id' => $bankId,
                'bank_name' => $bankName,
                'hint' => "Добавьте в BANK_MAP: {$bankId} => 'ваш_код_gateway',",
            ]);
            throw new \Exception("AlphaPay bank ID {$bankId} ({$bankName}) not found in BANK_MAP");
        }

        $paymentGateway = PaymentGateway::where('code', $gatewayCode)
            ->where('currency', $currencyCode)
            ->where('is_active', true)
            ->first();

        if (!$paymentGateway) {
            $this->logError('PaymentGateway not found in database', [
                'bank_id' => $bankId,
                'bank_name' => $bankName,
                'gateway_code' => $gatewayCode,
                'currency' => $currencyCode,
            ]);
            throw new \Exception("PaymentGateway '{$gatewayCode}' not found for currency '{$currencyCode}'");
        }

        $this->logInfo('Found PaymentGateway based on AlphaPay bank', [
            'bank_id' => $bankId,
            'bank_name' => $bankName,
            'gateway_code' => $gatewayCode,
            'gateway_id' => $paymentGateway->id,
        ]);

        return $paymentGateway;
    }

    protected function getSupportedCurrencies(): array
    {
        return $this->config['supported_currencies'] ?? ['RUB'];
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
     * Получить баланс из AlphaPay API
     * 
     * GET /api/v1/s2s/balance/<currency>/
     */
    public function getBalance(): ?float
    {
        $apiUrl = $this->config['api_url'] ?? null;
        $apiKey = $this->config['api_key'] ?? null;
        $secretKey = $this->config['secret_key'] ?? null;

        if (!$apiUrl || !$apiKey || !$secretKey) {
            return null;
        }

        try {
            $url = rtrim($apiUrl, '/') . '/api/v1/s2s/balance/USDT/';
            $headers = $this->buildAuthHeaders();

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['balance']['actual'])) {
                    return (float) $data['balance']['actual'];
                }
            }

            $this->logWarning('Failed to get balance from AlphaPay API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            $this->logError('Exception getting AlphaPay balance', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Отменить счет в AlphaPay
     * 
     * POST /api/v1/s2s/invoice/<uuid:id>/cancel/
     */
    public function cancelOrder(\App\Models\Order $order): bool
    {
        $providerOrderId = $order->provider_order_id;
        
        if (!$providerOrderId) {
            $this->logWarning('AlphaPay cancelOrder: no provider_order_id', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        $apiUrl = $this->config['api_url'] ?? null;
        $apiKey = $this->config['api_key'] ?? null;
        $secretKey = $this->config['secret_key'] ?? null;

        if (!$apiUrl || !$apiKey || !$secretKey) {
            $this->logWarning('AlphaPay cancelOrder: missing API credentials');
            return false;
        }

        try {
            $url = rtrim($apiUrl, '/') . '/api/v1/s2s/invoice/' . $providerOrderId . '/cancel/';
            $headers = $this->buildAuthHeaders();

            $this->logInfo('Cancelling invoice in AlphaPay', [
                'order_id' => $order->id,
                'provider_order_id' => $providerOrderId,
                'url' => $url,
            ]);

            $startTime = microtime(true);
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url);
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logHttpRequest(
                'POST',
                $url,
                $headers,
                [],
                $response->status(),
                $response->body(),
                $duration
            );

            if ($response->successful()) {
                $data = $response->json();
                // Проверяем что статус стал CANCELLED (state: 6)
                if (($data['state'] ?? null) === 6) {
                    $this->logInfo('Invoice cancelled successfully in AlphaPay', [
                        'order_id' => $order->id,
                        'provider_order_id' => $providerOrderId,
                    ]);
                    return true;
                }
                
                $this->logInfo('AlphaPay cancel response received', [
                    'order_id' => $order->id,
                    'provider_order_id' => $providerOrderId,
                    'state' => $data['state'] ?? 'unknown',
                ]);
                return true; // Считаем успехом если запрос прошёл
            }

            $this->logWarning('Failed to cancel invoice in AlphaPay', [
                'order_id' => $order->id,
                'provider_order_id' => $providerOrderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            $this->logError('Exception cancelling invoice in AlphaPay', [
                'order_id' => $order->id,
                'provider_order_id' => $providerOrderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

