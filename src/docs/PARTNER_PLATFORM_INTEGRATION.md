Шаг 1. Протестируй API

```bash
# Тестовый запрос к API партнера
curl -X POST https://api.partner-platform.com/requisites \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "amount": 10000,
    "currency": "RUB",
    "detail_type": "card",
    "merchant_id": "test_merchant"
  }'
```

Шаг 2: Создание класса провайдера

Создайте файл `app/Services/RequisiteProviders/PartnerPlatformProvider.php`:

```php
<?php

namespace App\Services\RequisiteProviders;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\PaymentGateway;
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
    /**
     * Уникальное название провайдера
     */
    public function getName(): string
    {
        return 'partner_platform';
    }

    /**
     * Приоритет провайдера (ниже внутреннего, но выше внешних)
     */
    public function getPriority(): int
    {
        return 15;
    }

    /**
     * Конфигурация по умолчанию
     */
    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'enabled' => env('PARTNER_PLATFORM_ENABLED', false),
            'api_url' => env('PARTNER_PLATFORM_API_URL', 'https://api.partner-platform.com'),
            'api_key' => env('PARTNER_PLATFORM_API_KEY'),
            'api_secret' => env('PARTNER_PLATFORM_API_SECRET'),
            'timeout' => (int) env('PARTNER_PLATFORM_TIMEOUT', 30),
            'retry_attempts' => (int) env('PARTNER_PLATFORM_RETRY_ATTEMPTS', 3),
            'retry_delay' => (int) env('PARTNER_PLATFORM_RETRY_DELAY', 1000),
            
            // Поддерживаемые валюты
            'supported_currencies' => ['RUB', 'USD', 'EUR'],
            
            // Поддерживаемые типы реквизитов
            'supported_detail_types' => ['card', 'phone', 'account_number', 'qr_code'],
            
            // Поддерживаемые платежные шлюзы
            'supported_gateways' => [], // Пустой массив = поддерживаем все
            
            // Лимиты по суммам (в копейках)
            'min_amount' => (int) env('PARTNER_PLATFORM_MIN_AMOUNT', 100),
            'max_amount' => (int) env('PARTNER_PLATFORM_MAX_AMOUNT', 50000000),
            
            // Маппинг мерчантов (ID вашей системы => ID партнера)
            'merchant_mapping' => [
                // 1 => 'partner_merchant_1',
                // 2 => 'partner_merchant_2',
            ],
            
            // Маппинг платежных шлюзов
            'gateway_mapping' => [
                // 1 => 'partner_gateway_1',
                // 2 => 'partner_gateway_2',
            ],
            
            // ID пользователя-партнера в вашей системе
            'partner_user_id' => (int) env('PARTNER_PLATFORM_USER_ID', 51),
            
            // Базовый URL для callback
            'callback_base_url' => env('PARTNER_PLATFORM_CALLBACK_BASE_URL', config('app.url')),
            
            // Токен устройства партнера
            'device_token' => env('PARTNER_PLATFORM_DEVICE_TOKEN'),
        ]);
    }

    /**
     * Проверка поддержки параметров
     */
    public function supports(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?bool $transgran = null
    ): bool {
        // Детальная диагностика для debugging
        $this->log('debug', 'Checking partner platform support', [
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'gateway_id' => $gateway?->id,
            'detail_type' => $detailType?->value,
            'transgran' => $transgran,
            'provider_enabled' => $this->isAvailable(),
        ]);

        // Проверяем что провайдер включен
        if (!$this->isAvailable()) {
            $this->log('warning', 'Partner platform not available');
            return false;
        }

        // КРИТИЧЕСКИ ВАЖНО: Проверяем онлайн статус партнерского пользователя
        try {
            $partnerUser = $this->getPartnerUser();
            if (!$partnerUser) {
                $this->log('warning', 'Partner user not found', [
                    'partner_user_id' => $this->config['partner_user_id'],
                ]);
                return false;
            }

            if (!$partnerUser->is_online) {
                $this->log('info', 'Partner user is offline, skipping provider', [
                    'partner_user_id' => $partnerUser->id,
                    'partner_email' => $partnerUser->email,
                    'is_online' => $partnerUser->is_online,
                ]);
                return false;
            }

            // Проверяем баланс партнерского пользователя
            if ($partnerUser->wallet && $partnerUser->wallet->merchant_balance->toUnitsInt() < 1000) {
                $this->log('warning', 'Partner user has insufficient balance', [
                    'balance' => $partnerUser->wallet->merchant_balance->toBeauty(),
                ]);
                return false;
            }

        } catch (\Exception $e) {
            $this->log('error', 'Error checking partner user status', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        // Проверяем базовые параметры
        if (!$this->validateParameters($amount, $currency, $gateway, $detailType)) {
            return false;
        }

        // Проверяем валюту
        if ($currency && !in_array($currency->getCode(), $this->getSupportedCurrencies())) {
            $this->log('info', 'Currency not supported by partner platform', [
                'currency' => $currency->getCode(),
                'supported_currencies' => $this->getSupportedCurrencies(),
            ]);
            return false;
        }

        // Проверяем тип реквизита
        if ($detailType && !in_array($detailType->value, $this->getSupportedDetailTypes())) {
            $this->log('info', 'Detail type not supported by partner platform', [
                'detail_type' => $detailType->value,
                'supported_types' => $this->getSupportedDetailTypes(),
            ]);
            return false;
        }

        // Проверяем сумму
        $amountValue = $amount->toUnitsInt();
        if ($amountValue < $this->config['min_amount'] || $amountValue > $this->config['max_amount']) {
            $this->log('info', 'Amount out of partner platform limits', [
                'amount' => $amountValue,
                'min_amount' => $this->config['min_amount'],
                'max_amount' => $this->config['max_amount'],
            ]);
            return false;
        }

        return true;
    }

    /**
     * Получение реквизитов от партнерской платформы
     */
    public function getRequisites(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null
    ): ?Detail {
        
        return $this->executeWithLogging(
            merchant: $merchant,
            market: $market,
            amount: $amount,
            detailType: $detailType,
            currency: $currency,
            gateway: $gateway,
            transgran: $transgran,
            callback: function() use ($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran) {
                return $this->makeApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran);
            }
        );
    }

    /**
     * Выполнение API запроса к партнерской платформе
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
        
        $this->logInfo('Making API request to partner platform', [
            'merchant_id' => $merchant->id,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'gateway_id' => $gateway?->id,
            'detail_type' => $detailType?->value,
        ]);

        try {
            // Получаем партнерского пользователя
            $partnerUser = $this->getPartnerUser();
            if (!$partnerUser) {
                $this->logError('Partner user not found');
                return null;
            }

            // Получаем устройство партнера
            $partnerDevice = $this->getPartnerUserDevice($partnerUser);
            if (!$partnerDevice) {
                $this->logError('Partner device not found');
                return null;
            }

            // Подготовка данных для запроса
            $requestData = $this->prepareRequestData($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran, $partnerUser, $partnerDevice);
            
            // Выполнение HTTP запроса
            $response = Http::timeout($this->config['timeout'])
                ->withHeaders($this->getRequestHeaders())
                ->post($this->config['api_url'] . '/requisites', $requestData);

            $this->logInfo('Partner platform API response received', [
                'status_code' => $response->status(),
                'response_size' => strlen($response->body()),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                return $this->createDetailFromApiResponse($responseData, $gateway, $amount, $partnerUser);
            } else {
                $this->logError('Partner platform API request failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                return null;
            }

        } catch (\Exception $e) {
            $this->logError('Exception during partner platform API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Подготовка данных для API запроса
     */
    protected function prepareRequestData(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null,
        User $partnerUser = null,
        UserDevice $partnerDevice = null
    ): array {
        return [
            'merchant_id' => $this->getPartnerMerchantId($merchant),
            'amount' => $amount->toUnitsInt(),
            'currency' => $currency?->getCode() ?? 'RUB',
            'detail_type' => $detailType?->value ?? 'card',
            'gateway_code' => $this->mapGatewayToPartner($gateway),
            'market' => $market->value,
            'callback_url' => $this->getCallbackUrl($merchant),
            'partner_user_id' => $partnerUser?->id,
            'device_token' => $partnerDevice?->token ?? $this->config['device_token'],
            'timestamp' => time(),
            'signature' => $this->generateSignature($amount, $currency, $merchant),
        ];
    }

    /**
     * Получение заголовков для API запроса
     */
    protected function getRequestHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Hill2-Partner-Platform/1.0',
        ];

        // Добавление API ключа
        if (!empty($this->config['api_key'])) {
            $headers['X-API-Key'] = $this->config['api_key'];
        }

        // Добавление подписи (если требуется)
        if (!empty($this->config['api_secret'])) {
            $headers['X-Signature'] = $this->generateRequestSignature();
        }

        return $headers;
    }

    /**
     * Создание объекта Detail из ответа API
     */
    protected function createDetailFromApiResponse(array $data, ?PaymentGateway $gateway, Money $amount, User $partnerUser): Detail
    {
        $this->logInfo('Creating detail from partner platform response', [
            'response_data' => $data,
        ]);

        // Валидация обязательных полей
        $requiredFields = ['id', 'detail', 'user_id', 'gateway_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Создание объекта Gateway
        $gatewayValue = new Gateway(
            id: $data['gateway_id'],
            name: $data['gateway_name'] ?? 'Partner Gateway',
            code: $data['gateway_code'] ?? 'partner'
        );

        // Создание объекта Trader (партнерский пользователь)
        $traderValue = new Trader(
            id: $partnerUser->id,
            name: $partnerUser->name,
            email: $partnerUser->email,
            phone: $partnerUser->phone ?? ''
        );

        // Создание объекта Detail
        return new Detail(
            id: $data['id'],
            detail: $data['detail'],
            detailType: DetailType::from($data['detail_type'] ?? 'card'),
            gateway: $gatewayValue,
            trader: $traderValue,
            amount: $amount,
            isExternal: true,
            providerName: $this->getName()
        );
    }

    /**
     * Получение партнерского пользователя
     */
    protected function getPartnerUser(): ?User
    {
        $userId = $this->config['partner_user_id'] ?? 51;
        $user = User::with('wallet')->find($userId);
        
        if (!$user) {
            $this->logError('Partner user not found', [
                'user_id' => $userId,
            ]);
            return null;
        }

        return $user;
    }

    /**
     * Получение устройства партнерского пользователя
     */
    protected function getPartnerUserDevice(User $user): ?UserDevice
    {
        $device = $user->devices()->where('is_active', true)->first();
        
        if (!$device) {
            $this->logWarning('Partner user device not found', [
                'user_id' => $user->id,
            ]);
            return null;
        }

        return $device;
    }

    /**
     * Получение ID мерчанта для партнерской платформы
     */
    protected function getPartnerMerchantId(Merchant $merchant): string
    {
        $mapping = $this->config['merchant_mapping'] ?? [];
        return $mapping[$merchant->id] ?? $merchant->uuid;
    }

    /**
     * Маппинг платежного шлюза для партнерской платформы
     */
    protected function mapGatewayToPartner(?PaymentGateway $gateway): ?string
    {
        if (!$gateway) {
            return null;
        }

        $mapping = $this->config['gateway_mapping'] ?? [];
        return $mapping[$gateway->id] ?? $gateway->code;
    }

    /**
     * Получение URL для callback
     */
    protected function getCallbackUrl(Merchant $merchant): string
    {
        $baseUrl = $this->config['callback_base_url'];
        return "{$baseUrl}/api/callbacks/partner-platform/{$merchant->uuid}";
    }

    /**
     * Генерация подписи для запроса
     */
    protected function generateSignature(Money $amount, ?Currency $currency, Merchant $merchant): string
    {
        $data = [
            'amount' => $amount->toUnitsInt(),
            'currency' => $currency?->getCode() ?? 'RUB',
            'merchant_id' => $this->getPartnerMerchantId($merchant),
            'timestamp' => time(),
        ];
        
        return hash_hmac('sha256', json_encode($data), $this->config['api_secret']);
    }

    /**
     * Генерация подписи для заголовка запроса
     */
    protected function generateRequestSignature(): string
    {
        $data = [
            'timestamp' => time(),
            'api_key' => $this->config['api_key'],
        ];
        
        return hash_hmac('sha256', json_encode($data), $this->config['api_secret']);
    }

    /**
     * Поддерживаемые валюты
     */
    protected function getSupportedCurrencies(): array
    {
        return $this->config['supported_currencies'] ?? ['RUB'];
    }

    /**
     * Поддерживаемые типы реквизитов
     */
    protected function getSupportedDetailTypes(): array
    {
        return $this->config['supported_detail_types'] ?? ['card'];
    }

    /**
     * Поддерживаемые платежные шлюзы
     */
    protected function getSupportedGateways(): array
    {
        return $this->config['supported_gateways'] ?? [];
    }
}
```

Шаг 3: Создание контроллера для callback

Создайте файл `app/Http/Controllers/API/PartnerPlatform/CallbackController.php`:

```php
<?php

namespace App\Http\Controllers\API\PartnerPlatform;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Merchant;
use App\Services\Order\OrderService;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Обработка callback от партнерской платформы
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('Partner platform callback received', [
            'data' => $request->all(),
        ]);

        try {
            // Валидация данных
            $validated = $request->validate([
                'order_id' => 'required|string',
                'status' => 'required|string',
                'amount' => 'required|integer',
                'currency' => 'required|string',
                'signature' => 'required|string',
            ]);

            // Проверка подписи
            if (!$this->verifySignature($request)) {
                Log::warning('Partner platform callback signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Поиск заказа
            $order = Order::where('uuid', $validated['order_id'])->first();
            if (!$order) {
                Log::warning('Order not found in partner platform callback', [
                    'order_id' => $validated['order_id'],
                ]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Обработка статуса
            $this->processOrderStatus($order, $validated['status']);

            Log::info('Partner platform callback processed successfully', [
                'order_id' => $order->id,
                'status' => $validated['status'],
            ]);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Error processing partner platform callback', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Проверка статуса callback
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'active',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Обработка статуса заказа
     */
    private function processOrderStatus(Order $order, string $status): void
    {
        switch ($status) {
            case 'success':
                $this->orderService->finishOrderAsSuccessful($order->id, OrderSubStatus::SUCCESSFUL);
                break;
                
            case 'failed':
                $this->orderService->finishOrderAsFailed($order->id, OrderSubStatus::FAILED);
                break;
                
            case 'pending':
                // Заказ уже в процессе обработки
                break;
                
            default:
                Log::warning('Unknown partner platform status', [
                    'order_id' => $order->id,
                    'status' => $status,
                ]);
        }
    }

    /**
     * Проверка подписи callback
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Signature');
        $data = $request->all();
        
        // Удаляем подпись из данных для проверки
        unset($data['signature']);
        
        $expectedSignature = hash_hmac('sha256', json_encode($data), config('partner_platform.api_secret'));
        
        return hash_equals($expectedSignature, $signature);
    }
}
```

Шаг 4: Добавление переменных окружения

Добавьте в файл `.env`:

```bash
# Партнерская платформа
PARTNER_PLATFORM_ENABLED=true
PARTNER_PLATFORM_API_URL=https://api.partner-platform.com
PARTNER_PLATFORM_API_KEY=your_api_key_here
PARTNER_PLATFORM_API_SECRET=your_secret_here
PARTNER_PLATFORM_TIMEOUT=30
PARTNER_PLATFORM_RETRY_ATTEMPTS=3
PARTNER_PLATFORM_RETRY_DELAY=1000
PARTNER_PLATFORM_MIN_AMOUNT=100
PARTNER_PLATFORM_MAX_AMOUNT=50000000
PARTNER_PLATFORM_USER_ID=51
PARTNER_PLATFORM_CALLBACK_BASE_URL=https://your-domain.com
PARTNER_PLATFORM_DEVICE_TOKEN=your_device_token
```

Шаг 5: Создание конфигурационного файла если его нет

Создайте файл `config/partner_platform.php`:

```php
<?php

return [
    'enabled' => env('PARTNER_PLATFORM_ENABLED', false),
    'api_url' => env('PARTNER_PLATFORM_API_URL'),
    'api_key' => env('PARTNER_PLATFORM_API_KEY'),
    'api_secret' => env('PARTNER_PLATFORM_API_SECRET'),
    'timeout' => (int) env('PARTNER_PLATFORM_TIMEOUT', 30),
    'retry_attempts' => (int) env('PARTNER_PLATFORM_RETRY_ATTEMPTS', 3),
    'retry_delay' => (int) env('PARTNER_PLATFORM_RETRY_DELAY', 1000),
    'min_amount' => (int) env('PARTNER_PLATFORM_MIN_AMOUNT', 100),
    'max_amount' => (int) env('PARTNER_PLATFORM_MAX_AMOUNT', 50000000),
    'user_id' => (int) env('PARTNER_PLATFORM_USER_ID', 51),
    'callback_base_url' => env('PARTNER_PLATFORM_CALLBACK_BASE_URL', config('app.url')),
    'device_token' => env('PARTNER_PLATFORM_DEVICE_TOKEN'),
    
    // Маппинг мерчантов
    'merchant_mapping' => [
        // 1 => 'partner_merchant_1',
        // 2 => 'partner_merchant_2',
    ],
    
    // Маппинг платежных шлюзов
    'gateway_mapping' => [
        // 1 => 'partner_gateway_1',
        // 2 => 'partner_gateway_2',
    ],
    
    // Поддерживаемые валюты
    'supported_currencies' => ['RUB', 'USD', 'EUR'],
    
    // Поддерживаемые типы реквизитов
    'supported_detail_types' => ['card', 'phone', 'account_number', 'qr_code'],
];
```

Шаг 6: Добавление маршрутов

Добавьте в файл `routes/api.php`:

```php
// Роуты для callback от партнерской платформы
Route::group(['prefix' => 'callbacks/partner-platform'], function () {
    Route::post('/', [\App\Http\Controllers\API\PartnerPlatform\CallbackController::class, 'handle'])
        ->name('api.callbacks.partner-platform');
    Route::get('/status', [\App\Http\Controllers\API\PartnerPlatform\CallbackController::class, 'status'])
        ->name('api.callbacks.partner-platform.status');
});
```

Шаг 7: Регистрация в AppServiceProvider

Обновите файл `app/Providers/AppServiceProvider.php`:

```php
// Регистрация цепочки провайдеров реквизитов
$this->app->singleton(\App\Services\RequisiteProviders\RequisiteProviderChain::class, function () {
    $chain = new \App\Services\RequisiteProviders\RequisiteProviderChain();
    
    // Добавляем провайдеры в порядке приоритета
    $chain->addProvider(new \App\Services\RequisiteProviders\InternalRequisiteProvider());
    
    // 🆕 Добавляем партнерскую платформу
    if (config('partner_platform.enabled', false)) {
        $chain->addProvider(new \App\Services\RequisiteProviders\PartnerPlatformProvider(
            config('partner_platform', [])
        ));
    }
    
    // Добавляем внешние провайдеры
    if (config('requisite_providers.external_provider_1.enabled', false)) {
        $chain->addProvider(new \App\Services\RequisiteProviders\ExternalProvider1(
            config('requisite_providers.external_provider_1', [])
        ));
    }
    
    return $chain;
});
```

Шаг 8: Создание партнерского пользователя

Создайте пользователя-партнера в базе данных:

```sql
-- Создание партнерского пользователя
INSERT INTO users (name, email, password, is_online, created_at, updated_at) 
VALUES ('Partner Platform', 'partner@platform.com', '$2y$10$...', 1, NOW(), NOW());

-- Создание кошелька для партнера
INSERT INTO wallets (user_id, merchant_balance, trust_balance, reserve_balance, commission_balance, teamleader_balance, created_at, updated_at)
VALUES (51, 1000000, 0, 0, 0, 0, NOW(), NOW());

-- Создание устройства для партнера
INSERT INTO user_devices (user_id, token, is_active, created_at, updated_at)
VALUES (51, 'partner_device_token', 1, NOW(), NOW());
```

Шаг 9: Тестирование интеграции

```bash
# Тестировать партнерскую платформу
php artisan requisite-providers:test --provider=partner_platform --amount=10000 --currency=RUB

# Тестировать всю цепочку
php artisan requisite-providers:test --amount=10000 --currency=RUB --merchant-id=1

# Показать статистику
php artisan requisite-providers:test --stats
```

Тестирование callback

```bash
# Тестовый callback запрос
curl -X POST https://your-domain.com/api/callbacks/partner-platform \
  -H "Content-Type: application/json" \
  -H "X-Signature: your_signature" \
  -d '{
    "order_id": "test-order-uuid",
    "status": "success",
    "amount": 10000,
    "currency": "RUB",
    "signature": "test_signature"
  }'
```

Проверка логов

```bash
# Просмотр логов партнерской платформы
tail -f storage/logs/laravel.log | grep "partner_platform"

# Проверка логов в базе данных
php artisan tinker
>>> \App\Models\RequisiteProviderLog::where('provider_name', 'partner_platform')->latest()->first()
```

Настройка маппинга мерчантов

В файле `config/partner_platform.php`:

```php
'merchant_mapping' => [
    1 => 'partner_merchant_1', // ID 1 в вашей системе = ID 1 у партнера
    2 => 'partner_merchant_2', // ID 2 в вашей системе = ID 2 у партнера
],
```

Настройка маппинга платежных шлюзов

```php
'gateway_mapping' => [
    1 => 'card_gateway',    // ID 1 в вашей системе = card_gateway у партнера
    2 => 'phone_gateway',   // ID 2 в вашей системе = phone_gateway у партнера
],
```

Кастомизация callback обработки

В файле `CallbackController.php`:

```php
private function processOrderStatus(Order $order, string $status): void
{
    switch ($status) {
        case 'success':
            // Ваша логика успешного завершения
            $this->orderService->finishOrderAsSuccessful($order->id, OrderSubStatus::SUCCESSFUL);
            break;
            
        case 'failed':
            // Ваша логика неуспешного завершения
            $this->orderService->finishOrderAsFailed($order->id, OrderSubStatus::FAILED);
            break;
            
        case 'pending':
            // Логика для заказов в процессе
            break;
            
        default:
            Log::warning('Unknown partner platform status', [
                'order_id' => $order->id,
                'status' => $status,
            ]);
    }
}
```

Обработка ошибок

Партнерский пользователь не найден
```bash
# Проверьте ID пользователя в .env
PARTNER_PLATFORM_USER_ID=51

# Создайте пользователя если не существует
php artisan tinker
>>> \App\Models\User::create(['name' => 'Partner', 'email' => 'partner@platform.com', 'password' => Hash::make('password')])
```

API запросы не проходят
```bash
# Проверьте переменные окружения
php artisan config:show partner_platform

# Проверьте логи
tail -f storage/logs/laravel.log | grep "partner_platform"
```

Callback не работает
```bash
# Проверьте URL callback
echo $PARTNER_PLATFORM_CALLBACK_BASE_URL

# Проверьте маршруты
php artisan route:list | grep "partner-platform"
```

Отладка

```php
// В PartnerPlatformProvider добавьте детальное логирование
protected function makeApiRequest(...): ?Detail
{
    $this->logInfo('Starting partner platform API request', [
        'url' => $this->config['api_url'],
        'data' => $requestData,
    ]);
    
    // ... ваш код
    
    $this->logInfo('Partner platform API response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);
}
```

Мониторинг

```bash
# Статистика партнерской платформы
php artisan requisite-providers:test --provider=partner_platform --stats

# Тестирование с конкретными параметрами
php artisan requisite-providers:test \
  --provider=partner_platform \
  --amount=25000 \
  --currency=USD \
  --merchant-id=2 \
  --detail-type=card

# Проверка конфигурации
php artisan config:show partner_platform
```

SQL запросы для анализа

```sql
-- Статистика партнерской платформы за последние 24 часа
SELECT 
    COUNT(*) as total_requests,
    COUNT(CASE WHEN success = 1 THEN 1 END) as successful_requests,
    AVG(response_time_ms) as avg_response_time,
    MIN(response_time_ms) as min_response_time,
    MAX(response_time_ms) as max_response_time
FROM requisite_provider_logs 
WHERE provider_name = 'partner_platform'
  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Ошибки партнерской платформы
SELECT 
    error_message,
    COUNT(*) as error_count,
    MAX(created_at) as last_error
FROM requisite_provider_logs 
WHERE provider_name = 'partner_platform'
  AND success = 0
  AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY error_message
ORDER BY error_count DESC;

-- Статус партнерского пользователя
SELECT 
    u.id,
    u.name,
    u.email,
    u.is_online,
    w.merchant_balance,
    w.trust_balance
FROM users u
LEFT JOIN wallets w ON u.id = w.user_id
WHERE u.id = 51;
```