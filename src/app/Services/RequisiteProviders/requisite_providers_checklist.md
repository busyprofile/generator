# Алгоритм интеграции нового провайдера реквизитов

## Обзор архитектуры

```
ProviderIntegrationEnum        → Перечисление типов интеграций
AbstractRequisiteProvider      → Базовый класс провайдера
ProviderSelector               → Выбор и создание провайдера по терминалу
RequisiteProviderChain         → Цепочка провайдеров (internal → external)
ProviderCallbackController     → Роутинг callback'ов к обработчикам
AbstractProviderCallbackHandler → Базовый класс callback handler'а
IntegrationFieldsConfig        → Конфигурация полей настроек в UI
```

## Что происходит автоматически (не нужно трогать)

- **Валидация в Request** — используют `ProviderIntegrationEnum::cases()`
- **Кэш** — инвалидируется через Observer'ы при изменении Provider/ProviderTerminal
- **UI** — автоматически показывает поля из `IntegrationFieldsConfig`
- **providerId/providerTerminalId** — проставляются в `RequisiteProviderChain` после получения Detail

---

## Шаг 1. Добавить тип интеграции в Enum

**Файл:** `app/Enums/ProviderIntegrationEnum.php`

```php
enum ProviderIntegrationEnum: string
{
    // ...существующие
    case NEW_PROVIDER = 'NEW_PROVIDER'; // добавить
}
```

---

## Шаг 2. Создать класс провайдера

**Файл:** `app/Services/RequisiteProviders/NewProvider.php`

Наследовать от `AbstractRequisiteProvider` и реализовать:

```php
class NewProvider extends AbstractRequisiteProvider
{
    // ОБЯЗАТЕЛЬНЫЕ МЕТОДЫ:
    
    public function getName(): string;           // Имя для логов
    public function getPriority(): int;          // Приоритет (меньше = выше)
    
    public function getRequisites(               // Основной метод получения реквизитов
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null
    ): ?Detail;
    
    protected function getSupportedCurrencies(): array;
    protected function getSupportedDetailTypes(): array;
    protected function getSupportedGateways(): array;
    
    // ОПЦИОНАЛЬНЫЕ МЕТОДЫ:
    
    public function getBalance(): ?float;        // Баланс у провайдера
    public function cancelOrder(Order $order): bool; // Отмена сделки
    protected function getDefaultConfig(): array; // Дефолтные настройки
}
```

### Ключевые моменты в `getRequisites()`:

1. **Проверка конфига:**
   ```php
   $this->assertConfig(['api_url', 'api_token', 'merchant_id']);
   ```

2. **Использовать обёртку для логирования:**
   ```php
   return $this->executeWithLogging(
       merchant: $merchant,
       market: $market,
       amount: $amount,
       // ...
       callback: fn() => $this->makeApiRequest(...)
   );
   ```

3. **Возвращать `Detail` объект:**
   ```php
   use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
   use App\Services\Order\Features\OrderDetailProvider\Values\Gateway;
   use App\Services\Order\Features\OrderDetailProvider\Values\Trader;
   
   return new Detail(
       id: $paymentDetail->id,              // ID PaymentDetail (или null для внешних)
       userID: $trader->id,                 // ID трейдера
       paymentGatewayID: $gateway->id,
       userDeviceID: $device->id,
       dailyLimit: $paymentDetail->daily_limit,
       currentDailyLimit: $paymentDetail->current_daily_limit,
       currency: Currency::RUB(),
       exchangePrice: $rate,                // Курс из ответа провайдера
       totalProfit: $totalProfit,           // Сумма в USDT
       serviceProfit: $serviceProfit,
       merchantProfit: $merchantProfit,
       traderProfit: $traderProfit,
       teamLeaderProfit: Money::zero(Currency::USDT()),
       traderCommissionRate: $traderRate,
       teamLeaderCommissionRate: 0.0,
       traderPaidForOrder: $traderPaid,
       gateway: new Gateway(...),
       trader: new Trader(...),
       amount: $finalAmount,                // Финальная сумма в рублях
       externalRequisites: null,            // (опц.) реквизиты если нет PaymentDetail
       providerOrderId: $responseData['id'], // ⚠️ ID сделки у провайдера!
   );
   ```
   
   **Важно:** `providerOrderId` используется для отмены сделки через `cancelOrder()`

4. **Gateway и Trader объекты:**
   ```php
   // Gateway — данные платежного шлюза
   $gateway = new Gateway(
       id: $paymentGateway->id,
       code: $paymentGateway->code,
       reservationTime: 30, // минут на оплату
       serviceCommissionRate: 2.5, // % комиссии сервиса
       traderCommissionRate: $this->config['rate'] ?? 3.0,
       partnerExternalId: $orderId, // ID для идентификации в callback'е → сохранится в orders.external_id
   );
   
   // Trader — данные трейдера
   $trader = new Trader(
       id: $traderUser->id,
       trustBalance: $traderUser->wallet->trust_balance,
       teamLeaderID: null,
       teamLeaderCommissionRate: 0.0,
       traderCommissionRate: $this->config['rate'] ?? 3.0,
       additional_team_leader_ids: [],
   );
   ```

---

## Шаг 3. Создать обработчик callback'ов

**Файл:** `app/Services/RequisiteProviders/Callbacks/NewProviderCallbackHandler.php`

```php
use App\Enums\OrderSubStatus;
use App\Enums\ProviderIntegrationEnum;
use App\Models\Order;
use App\Models\ProviderTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NewProviderCallbackHandler extends AbstractProviderCallbackHandler
{
    public function integration(): ProviderIntegrationEnum
    {
        return ProviderIntegrationEnum::NEW_PROVIDER;
    }

    public function handle(Request $request, ProviderTerminal $terminal): JsonResponse
    {
        try {
            // 1. Валидация входящих данных
            $validated = Validator::make($request->all(), [
                'order_id' => ['required', 'string'],
                'status' => ['required', 'string'],
                // ...другие поля
            ])->validate();

            // 2. Найти Order по external_id (сохранённому при создании сделки)
            $order = Order::where('external_id', $validated['order_id'])->first();

            if (!$order) {
                $this->logCallback($request, $terminal, null, 'Order not found', 404);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // 3. Обработать статус
            $this->processStatus($order, $validated);
            
            // 4. Залогировать callback (успех)
            $this->logCallback($request, $terminal, $order->id, null, 200);

            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('[ProviderCallback:NewProvider] Exception', [
                'terminal_uuid' => $terminal->uuid,
                'error' => $e->getMessage(),
            ]);
            $this->logCallback($request, $terminal, null, $e->getMessage(), 500);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function processStatus(Order $order, array $data): void
    {
        $status = $data['status'];

        switch ($status) {
            case 'success':
            case 'paid':
                if ($this->orderIsPending($order)) {
                    $this->finishOrderAsSuccessful($order, OrderSubStatus::SUCCESSFULLY_PAID);
                }
                break;

            case 'failed':
            case 'canceled':
                if ($this->orderIsPending($order)) {
                    $this->finishOrderAsFailed($order, OrderSubStatus::CANCELED);
                }
                break;

            case 'dispute':
                $this->createDispute($order);
                break;
        }
    }
}
```

### Методы AbstractProviderCallbackHandler:

| Метод | Описание |
|-------|----------|
| `finishOrderAsSuccessful($order, $subStatus)` | Завершить заказ как успешный |
| `finishOrderAsFailed($order, $subStatus)` | Завершить заказ как неуспешный |
| `createDispute($order)` | Создать диспут |
| `acceptDispute($order)` | Принять диспут (в пользу клиента) |
| `cancelDispute($order)` | Отклонить диспут |
| `logCallback($request, $terminal, $orderId, $error, $statusCode)` | Логировать callback |
| `orderIsPending($order)` | Проверить что заказ ещё в статусе PENDING |

---

## Шаг 4. Зарегистрировать провайдера в ProviderSelector

**Файл:** `app/Services/RequisiteProviders/ProviderSelector.php`

В методе `createProviderFromTerminal()` добавить case:

```php
return match ($integrationEnum) {
    ProviderIntegrationEnum::GAREX => new GarexProvider($config),
    ProviderIntegrationEnum::METHODPAY => new MethodPayProvider($config),
    ProviderIntegrationEnum::NEW_PROVIDER => new NewProvider($config), // добавить
    default => null,
};
```

---

## Шаг 5. Зарегистрировать callback handler

**Файл:** `app/Http/Controllers/API/ProviderCallbackController.php`

1. Добавить import:
   ```php
   use App\Services\RequisiteProviders\Callbacks\NewProviderCallbackHandler;
   ```

2. В методе `resolveHandler()` добавить case:
   ```php
   return match ($integration) {
       ProviderIntegrationEnum::GAREX => app(GarexCallbackHandler::class),
       ProviderIntegrationEnum::METHODPAY => app(MethodPayCallbackHandler::class),
       ProviderIntegrationEnum::NEW_PROVIDER => app(NewProviderCallbackHandler::class), // добавить
       default => null,
   };
   ```

---

## Шаг 6. Добавить конфигурацию полей для UI

**Файл:** `app/Services/RequisiteProviders/IntegrationFieldsConfig.php`

1. Добавить метод с полями:
   ```php
   private static function getNewProviderFields(): array
   {
       return [
           [
               'key' => 'api_url',
               'label' => 'API URL',
               'type' => 'text',
               'required' => true,
               'placeholder' => 'https://api.newprovider.com',
               'description' => 'Базовый URL API',
           ],
           [
               'key' => 'api_token',
               'label' => 'API Token',
               'type' => 'password',
               'required' => true,
               'placeholder' => 'Введите токен',
               'description' => 'Токен авторизации',
           ],
           // ...другие поля
       ];
   }
   ```

2. Добавить в `getFields()`:
   ```php
   return match ($integration) {
       // ...
       ProviderIntegrationEnum::NEW_PROVIDER => self::getNewProviderFields(),
   };
   ```

3. Добавить в `getIntegrationName()`:
   ```php
   return match ($integration) {
       // ...
       ProviderIntegrationEnum::NEW_PROVIDER => 'New Provider',
   };
   ```

---

## Шаг 7. (Опционально) Добавить глобальный конфиг

**Файл:** `config/requisite_providers.php`

Если нужны ENV-переменные или дефолтные настройки на уровне приложения:

```php
'new_provider' => [
    'enabled' => env('NEW_PROVIDER_ENABLED', false),
    'api_url' => env('NEW_PROVIDER_API_URL'),
    'timeout' => env('NEW_PROVIDER_TIMEOUT', 30),
    // ...
],
```

---

## Шаг 8. Создать провайдера и терминал в БД

### Через админку:
1. **Провайдеры** (`/admin/providers`) → Создать провайдера с `integration = NEW_PROVIDER`
2. **Терминалы** (`/admin/provider-terminals`) → Создать терминал, заполнить настройки
3. **Привязка к мерчантам** → На странице терминала привязать к нужным мерчантам

### Или через миграцию/seeder:

```php
use Illuminate\Support\Str;
use App\Enums\ProviderIntegrationEnum;
use App\Models\Provider;
use App\Models\ProviderTerminal;

// 1. Создать провайдера
$provider = Provider::create([
    'uuid' => Str::uuid(),
    'name' => 'New Provider',
    'integration' => ProviderIntegrationEnum::NEW_PROVIDER,
    'trader_id' => $traderId, // ID пользователя для учёта баланса
    'is_active' => true,
]);

// 2. Создать терминал
$terminal = ProviderTerminal::create([
    'uuid' => Str::uuid(),
    'provider_id' => $provider->id,
    'name' => 'New Provider Terminal #1',
    'min_sum' => 1000,        // минимальная сумма (в базовой валюте)
    'max_sum' => 100000,      // максимальная сумма
    'rate' => 3.5,            // комиссия трейдера % (влияет на приоритет)
    'time_for_order' => 1800, // время на оплату (секунды)
    'max_response_time_ms' => 30000, // таймаут HTTP
    'number_of_retries' => 3,
    'enabled_detail_types' => ['card', 'phone'], // важно!
    'additional_settings' => [
        'api_url' => 'https://api.newprovider.com',
        'api_token' => 'secret_token',
        'merchant_id' => 'merchant_123',
    ],
    'is_active' => true,
]);

// 3. Привязать к мерчантам
$terminal->merchants()->attach($merchantId, ['is_active' => true]);
```

### Важные поля терминала:

| Поле | Описание |
|------|----------|
| `min_sum` / `max_sum` | Ограничения по сумме (в рублях) |
| `rate` | Комиссия трейдера %. Приоритет = rate × 10 (меньше rate → выше приоритет) |
| `enabled_detail_types` | Массив `['card', 'phone', 'account_number']` — какие типы реквизитов поддерживает |
| `additional_settings` | JSON с настройками API (api_url, api_token и т.д.) |
| `trader_id` в Provider | Сделки открываются только если у трейдера положительный `trust_balance` |

---

## Контрольный чеклист

### Код (6 файлов):
- [ ] `ProviderIntegrationEnum.php` — добавлен case `NEW_PROVIDER`
- [ ] `NewProvider.php` — создан класс провайдера
- [ ] `NewProviderCallbackHandler.php` — создан обработчик callback'ов
- [ ] `ProviderSelector.php` — добавлен match case в `createProviderFromTerminal()`
- [ ] `ProviderCallbackController.php` — добавлен match case в `resolveHandler()` + import
- [ ] `IntegrationFieldsConfig.php` — добавлены в 3 местах: поля, имя, match

### Данные (БД):
- [ ] Создан Provider с `integration = NEW_PROVIDER`, `trader_id`, `is_active = true`
- [ ] Создан ProviderTerminal с `additional_settings`, `enabled_detail_types`, лимитами
- [ ] Терминал привязан к мерчантам (`provider_terminal_merchant.is_active = true`)
- [ ] У трейдера положительный `trust_balance` в кошельке

### Тестирование:
- [ ] Терминал выбирается (`ProviderSelector` возвращает терминал)
- [ ] Получение реквизитов работает (возвращает Detail)
- [ ] `provider_order_id`, `provider_id`, `provider_terminal_id` сохраняются в заказе
- [ ] `external_id` в заказе = идентификатор для callback'а
- [ ] Callback'и обрабатываются корректно (проверить все статусы)
- [ ] Логи callback'ов в `requisite_provider_callback_logs`
- [ ] Логи запросов в `requisite_provider_logs`
- [ ] Диспуты создаются/принимаются/отклоняются по callback'у
- [ ] Отмена сделки `cancelOrder()` работает (если реализована)

---

## Структура файлов интеграции

```
app/
├── Enums/
│   └── ProviderIntegrationEnum.php           # [Шаг 1] Добавить case
├── Services/RequisiteProviders/
│   ├── NewProvider.php                       # [Шаг 2] Создать провайдер
│   ├── ProviderSelector.php                  # [Шаг 4] Добавить match case
│   ├── IntegrationFieldsConfig.php           # [Шаг 6] Добавить конфиг полей (3 места)
│   └── Callbacks/
│       └── NewProviderCallbackHandler.php    # [Шаг 3] Создать handler
├── Http/Controllers/API/
│   └── ProviderCallbackController.php        # [Шаг 5] Добавить handler
config/
└── requisite_providers.php                   # [Шаг 7] (опционально) ENV-конфиг

Таблицы БД:                                   # [Шаг 8]
├── providers                                 # Провайдер (integration, trader_id)
├── provider_terminals                        # Терминал (additional_settings, limits)
└── provider_terminal_merchant                # Связь терминал ↔ мерчант
```

---

## URL callback'а

Провайдер должен слать callback'и на URL:
```
POST {APP_URL}/api/callback/{provider_terminal_uuid}
```

UUID терминала генерируется автоматически при создании и виден в админке.

---

## Диагностика проблем

### Терминал не выбирается:
1. Проверить `providers.is_active = true`
2. Проверить `provider_terminals.is_active = true`
3. Проверить `provider_terminal_merchant.is_active = true` для нужного мерчанта
4. Проверить что сумма в пределах `min_sum` / `max_sum`
5. Проверить что `enabled_detail_types` включает нужный тип
6. Проверить что у трейдера (`providers.trader_id`) есть кошелёк с `trust_balance > 0`

### Callback не обрабатывается:
1. Проверить что UUID терминала правильный
2. Проверить логи в `requisite_provider_callback_logs`
3. Проверить что заказ найден по `external_id`

### Логи:
- Логи запросов: `requisite_provider_logs`
- Логи callback'ов: `requisite_provider_callback_logs`
- Общие логи: `storage/logs/laravel.log` с тегом `[ProviderCallback]` или `[RequisiteProviderChain]`

