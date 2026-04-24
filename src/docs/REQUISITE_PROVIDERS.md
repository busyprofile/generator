# 🔗 Система каскадных провайдеров реквизитов

## 📋 Обзор

Система каскадных провайдеров реквизитов позволяет автоматически искать платежные реквизиты у нескольких провайдеров в порядке приоритета. Если основной (внутренний) провайдер не находит подходящие реквизиты, система автоматически обращается к внешним провайдерам.

## 🏗️ Архитектура

### Основные компоненты

1. **RequisiteProviderContract** - интерфейс для всех провайдеров
2. **AbstractRequisiteProvider** - базовый класс с общей логикой
3. **RequisiteProviderChain** - менеджер цепочки провайдеров  
4. **InternalRequisiteProvider** - обертка для внутренней логики
5. **ExternalProvider1/2** - примеры внешних провайдеров

### Порядок работы

```
1. Запрос реквизитов
2. InternalRequisiteProvider (приоритет 1) ← Внутренние реквизиты
3. ExternalProvider1 (приоритет 10) ← Первый внешний API  
4. ExternalProvider2 (приоритет 20) ← Второй внешний API
5. Возврат результата или null
```

## ⚙️ Конфигурация

### Файл config/requisite_providers.php

```php
return [
    'external_provider_1' => [
        'enabled' => env('EXTERNAL_PROVIDER_1_ENABLED', false),
        'api_url' => env('EXTERNAL_PROVIDER_1_API_URL'),
        'api_key' => env('EXTERNAL_PROVIDER_1_API_KEY'),
        'supported_currencies' => ['RUB', 'USD', 'EUR'],
        'supported_detail_types' => ['card', 'phone'],
        'min_amount' => 1000,
        'max_amount' => 500000,
    ],
    // ... другие провайдеры
];
```

### Переменные окружения (.env)

```bash
# Провайдер 1
EXTERNAL_PROVIDER_1_ENABLED=true
EXTERNAL_PROVIDER_1_API_URL=https://api.provider1.com
EXTERNAL_PROVIDER_1_API_KEY=your_api_key_here
EXTERNAL_PROVIDER_1_TIMEOUT=15

# Провайдер 2  
EXTERNAL_PROVIDER_2_ENABLED=true
EXTERNAL_PROVIDER_2_API_URL=https://api.provider2.com
EXTERNAL_PROVIDER_2_API_KEY=your_api_key_here
EXTERNAL_PROVIDER_2_API_SECRET=your_secret_here
```

## 🚀 Использование

### Основное использование

Система автоматически интегрирована в `OrderDetailProvider`. Никаких изменений в существующем коде не требуется.

```php
// Обычный запрос реквизитов - теперь с каскадированием
$provider = new OrderDetailProvider(
    order: $order,
    merchant: $merchant, 
    amount: $amount
);

$details = $provider->provide(); // Автоматически пройдет по цепочке провайдеров
```

### Прямое использование цепочки

```php
$chain = services()->requisiteProviderChain();

$details = $chain->getRequisites(
    merchant: $merchant,
    market: MarketEnum::BYBIT,
    amount: $amount,
    detailType: DetailType::CARD,
    currency: Currency::RUB()
);
```

### Тестирование провайдеров

```bash
# Показать статистику всех провайдеров
php artisan requisite-providers:test --stats

# Тестировать все провайдеры по цепочке
php artisan requisite-providers:test --amount=10000 --currency=RUB --merchant-id=1

# Тестировать конкретного провайдера
php artisan requisite-providers:test --provider=external_provider_1 --amount=5000

# Тестировать с конкретными параметрами
php artisan requisite-providers:test \
  --amount=25000 \
  --currency=USD \
  --merchant-id=2 \
  --detail-type=card \
  --gateway-id=3
```

## 🔧 Создание нового провайдера

### 1. Создайте класс провайдера

```php
<?php

namespace App\Services\RequisiteProviders;

use App\Services\Order\Features\OrderDetailProvider\Values\Detail;

class YourCustomProvider extends AbstractRequisiteProvider
{
    public function getName(): string
    {
        return 'your_custom_provider';
    }

    public function getPriority(): int
    {
        return 30; // Приоритет (меньше = выше)
    }

    public function supports(Money $amount, ...): bool
    {
        // Логика проверки поддержки параметров
        return true;
    }

    public function getRequisites(...): ?Detail
    {
        // Логика получения реквизитов от вашего API
        $response = Http::post('your-api-endpoint', $data);
        
        if ($response->successful()) {
            return $this->createDetailFromResponse($response->json());
        }
        
        return null;
    }

    protected function getSupportedCurrencies(): array
    {
        return ['RUB', 'USD'];
    }

    protected function getSupportedDetailTypes(): array
    {
        return ['card', 'phone'];
    }

    protected function getSupportedGateways(): array
    {
        return [1, 2, 3];
    }
}
```

### 2. Добавьте конфигурацию

В `config/requisite_providers.php`:

```php
'your_custom_provider' => [
    'enabled' => env('YOUR_PROVIDER_ENABLED', false),
    'api_url' => env('YOUR_PROVIDER_API_URL'),
    'api_key' => env('YOUR_PROVIDER_API_KEY'),
    // ... другие настройки
],
```

### 3. Зарегистрируйте в AppServiceProvider

В `app/Providers/AppServiceProvider.php`:

```php
if (config('requisite_providers.your_custom_provider.enabled', false)) {
    $chain->addProvider(new \App\Services\RequisiteProviders\YourCustomProvider(
        config('requisite_providers.your_custom_provider', [])
    ));
}
```

## 🔍 Мониторинг и логирование

### Логи

Все операции провайдеров логируются с префиксом провайдера:

```
[internal] Attempting to get internal requisites
[external_provider_1] Failed to get requisites: Connection timeout
[external_provider_2] Successfully got requisites
```

### База данных логов

Таблица `requisite_provider_logs` содержит детальную статистику:

```sql
SELECT provider_name, 
       COUNT(*) as total_requests,
       AVG(response_time_ms) as avg_response_time,
       COUNT(CASE WHEN success = 1 THEN 1 END) as successful_requests
FROM requisite_provider_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY provider_name;
```

## 🛠️ Расширенные возможности

### Circuit Breaker Pattern

```php
// В конфигурации
'global' => [
    'circuit_breaker_enabled' => true,
    'circuit_breaker_failure_threshold' => 5,
    'circuit_breaker_timeout' => 60,
]
```

### Retry механизм

```php
protected function getDefaultConfig(): array
{
    return [
        'retry_attempts' => 3,
        'retry_delay' => 1000, // миллисекунды
    ];
}
```

### Кастомная валидация

```php
public function supports(Money $amount, ...): bool
{
    // Проверяем время работы
    if (!$this->isWorkingHours()) {
        return false;
    }
    
    // Проверяем лимиты мерчанта
    if (!$this->checkMerchantLimits($merchant)) {
        return false;
    }
    
    return parent::supports($amount, ...);
}
```

## 🚨 Обработка ошибок

### Автоматическое переключение

Система автоматически переключается на следующего провайдера при:
- Таймауте соединения
- Ошибке API
- Возврате `null` от провайдера
- Исключении в коде провайдера

### Graceful Degradation

Если все провайдеры недоступны, система вернет исходное сообщение об ошибке:

```
"Подходящие платежные реквизиты не найдены во всех провайдерах."
```

## 📊 Мониторинг производительности

### Метрики

- Время ответа каждого провайдера
- Количество успешных/неуспешных запросов
- Процент использования провайдеров
- Среднее время каскадирования

### Команды мониторинга

```bash
# Статистика провайдеров
php artisan requisite-providers:test --stats

# Проверка конфигурации
php artisan config:show requisite_providers

# Просмотр логов
tail -f storage/logs/laravel.log | grep "RequisiteProvider"
```

## 🔄 Миграция с существующей системы

Система полностью обратно совместима. Существующий код продолжит работать без изменений, но теперь с поддержкой каскадирования.

### Постепенное внедрение

1. Добавьте внешних провайдеров с `enabled=false`
2. Протестируйте через команду `requisite-providers:test`
3. Включите провайдеров поочередно
4. Мониторьте метрики и логи

## ❓ FAQ

**Q: Как изменить порядок провайдеров?**  
A: Измените метод `getPriority()` - меньше число = выше приоритет.

**Q: Можно ли отключить внутреннего провайдера?**  
A: Нет, внутренний провайдер всегда активен и имеет наивысший приоритет.

**Q: Как добавить аутентификацию к провайдеру?**  
A: Переопределите методы запросов и добавьте нужные заголовки/подписи.

**Q: Влияет ли каскадирование на производительность?**  
A: Минимально. Внутренний провайдер выполняется первым, внешние - только при необходимости. 