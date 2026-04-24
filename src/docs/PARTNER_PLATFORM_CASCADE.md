# Каскадирование между платформами Hill2.com

## 🎯 Описание

Система каскадирования позволяет автоматически перенаправлять запросы на поиск реквизитов между двумя одинаковыми платформами Hill2.com.

### Схема работы:
```
Платформа A (основная) → нет реквизитов → Платформа B (партнерская) → возвращает реквизиты
```

### Схема callback'ов:
```
Партнерская платформа → изменение статуса → Основная платформа → уведомление мерчанта
```

## ⚙️ Настройка

### 1. Переменные окружения (.env)

```bash
# =====================================
# PARTNER PLATFORM (КАСКАДИРОВАНИЕ)
# =====================================

# Включить каскадирование на партнерскую платформу
PARTNER_PLATFORM_ENABLED=true

# API партнерской платформы hill2.com
PARTNER_PLATFORM_API_URL=https://app.hillcard.net
PARTNER_PLATFORM_API_KEY=your_partner_api_key_here

# URL для получения callback'ов от партнерской платформы
PARTNER_PLATFORM_CALLBACK_BASE_URL=https://your-domain.com

# Таймауты и retry логика
PARTNER_PLATFORM_TIMEOUT=30
PARTNER_PLATFORM_RETRY_ATTEMPTS=3
PARTNER_PLATFORM_RETRY_DELAY=1000

# Лимиты сумм для каскадирования (в копейках)
PARTNER_PLATFORM_MIN_AMOUNT=10000      # 100 рублей минимум
PARTNER_PLATFORM_MAX_AMOUNT=50000000   # 500,000 рублей максимум

# ID мерчанта по умолчанию в партнерской системе
PARTNER_PLATFORM_DEFAULT_MERCHANT_ID=partner_merchant_default
```

### 2. Callback URL для партнерской платформы

**🎯 ВАЖНО:** При создании мерчанта на партнерской платформе (app.hillcard.net) в поле **"Callback URL"** указывайте:

```
https://your-domain.com/api/callbacks/partner-platform
```

**Замените `your-domain.com` на ваш реальный домен!**

### 3. Маппинг мерчантов

Если у вас разные ID мерчантов в двух системах, настройте маппинг в `config/requisite_providers.php`:

```php
'merchant_mapping' => [
    1 => 'partner_merchant_123',    // Мерчант ID=1 у нас = partner_merchant_123 у партнера
    2 => 'partner_merchant_456',    // Мерчант ID=2 у нас = partner_merchant_456 у партнера
    5 => 'partner_merchant_789',    // и т.д.
],
```

### 4. Маппинг платежных шлюзов

Если коды шлюзов отличаются между платформами:

```php
'gateway_mapping' => [
    1 => 'sberbank',     // Шлюз ID=1 у нас = код 'sberbank' у партнера
    2 => 'tinkoff',      // Шлюз ID=2 у нас = код 'tinkoff' у партнера  
    3 => 'vtb',          // Шлюз ID=3 у нас = код 'vtb' у партнера
],
```

## 🔄 Логика работы

1. **Мерчант делает запрос** на создание ордера
2. **Внутренний поиск** (приоритет 1) - ищем реквизиты в базе
3. **Каскадирование** (приоритет 15) - если не найдено, обращаемся к партнерской платформе
4. **Партнерская платформа** создает ордер и возвращает реквизиты
5. **Конвертация формата** - преобразуем ответ партнера в наш Detail объект
6. **Мерчант получает** готовые реквизиты (не знает о каскадировании)
7. **Callback уведомления** - партнер уведомляет о изменениях статуса
8. **Пересылка мерчанту** - отправляем callback конечному мерчанту

## 📡 API Endpoints

### Создание ордера на партнерской платформе

```http
POST /api/payments
Authorization: Bearer {API_KEY}
Content-Type: application/json

{
    "amount": 50000,
    "currency": "RUB", 
    "merchant_id": "partner_merchant_123",
    "market": "market_value",
    "manually": false,
    "h2h": false,
    "payment_detail_type": "card",
    "payment_gateway": "sberbank",
    "callback_url": "https://your-domain.com/api/callbacks/partner-platform"
}
```

### Callback от партнерской платформы

```http
POST /api/callbacks/partner-platform
Content-Type: application/json

{
    "external_id": "d8961c40-cf32-4120-b046-ead50da3c1c2",
    "status": "paid",
    "amount": 50000,
    "currency": "RUB"
}
```

**Поддерживаемые статусы:**
- `pending` - В ожидании
- `paid` - Оплачен
- `success` - Успешно оплачен (маппится в `paid`)
- `failed` - Неудачен
- `cancelled` - Отменен
- `expired` - Истек
- `processing` - В обработке
- `confirmed` - Подтвержден
- `dispute` - Спор

### Проверка callback endpoint

```http
GET /api/callbacks/partner-platform/status
```

**Ответ:**
```json
{
    "success": true,
    "message": "Partner platform callback endpoint is working",
    "timestamp": "2025-06-30T13:31:25.000Z",
    "supported_statuses": ["pending", "paid", "failed", "cancelled", "expired", "processing", "confirmed", "dispute"]
}
```

## 🔍 Мониторинг

### Логи каскадирования
```bash
# Смотрим логи партнерского провайдера
tail -f storage/logs/laravel.log | grep "PartnerPlatformProvider"
```

### Проверка статуса в админке
```
/admin/requisite-providers
```

### Тестирование провайдера
```bash
php artisan requisite-providers:test partner_platform --amount=50000 --currency=RUB
```

## 🚀 Преимущества каскадирования

✅ **Увеличение успешности** - больше доступных реквизитов  
✅ **Прозрачность** - мерчант не знает об источнике реквизитов  
✅ **Fallback** - автоматическое переключение при недоступности  
✅ **Flexibility** - легко добавить новые партнерские платформы  
✅ **Monitoring** - полная отслеживаемость через логи и админку  

## ⚠️ Важные моменты

1. **API ключи** - убедитесь, что API ключ партнерской платформы активен
2. **Лимиты** - настройте корректные лимиты для каскадирования  
3. **Таймауты** - партнерские запросы могут быть медленнее локальных
4. **Валюты** - убедитесь что партнер поддерживает нужные валюты
5. **Monitoring** - следите за успешностью каскадирования в реальном времени

## 🔧 Troubleshooting

### Каскадирование не работает
```bash
# Проверяем что провайдер включен
php artisan config:show requisite_providers.partner_platform.enabled

# Тестируем подключение к партнеру
curl -H "Authorization: Bearer YOUR_API_KEY" \
     -H "Content-Type: application/json" \
     https://partner.hill2.com/api/payments
```

### Ошибки аутентификации
- Проверьте API ключ в .env
- Убедитесь что у API ключа есть права на создание платежей

### Некорректные данные
- Проверьте маппинг мерчантов и шлюзов
- Убедитесь что партнерская платформа поддерживает переданные параметры

### Медленная работа
- Увеличьте таймауты в .env
- Проверьте скорость ответа партнерской платформы
- Настройте retry логику под ваши нужды 