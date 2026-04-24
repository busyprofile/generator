# Интеграция по API

---

- [Введение](#about)
- [Основы](#base)
- [Базовые методы](#base-methods)
- [Описание API](#about-api)
- [Мерчант API](#merchant-api)
- [H2H API](#h2h-api)
- [Работа со спорами](#disputes)
- [Авто вывод с баланса](#auto-withdrawals)
- [Описание статусов сделок](#order-statuses)
- [Общее дополнение к описанию API](#addition)
- [Уведомление об изменении статуса платежа](#callback)
- [API для выплат](#payouts)

<a name="about"></a>
## Введение
Ниже представлено описание того как работает API, с помощью которого вы сможете сделать интеграцию с вашим проектом.

<a name="base"></a>
## Основы
Все запросы к API должны содержать обязательные заголовки
- **Accept: application/json** - нужно чтобы сервер знал в каком формате вернуть ответ.
- **Access-Token: token** - нужно для авторизации. Находится в админке, раздел "Интеграция".

### Ответы сервера

#### Успех HTTP 200
```json
{
    "success": true, 
    "data": [ //тело ответа
        //...
    ]
}
```

#### Ошибка валидации запроса HTTP 422
```json
{
    "message": "Общее описание ошибки",
    "errors": {
        "название параметра": [
            "Описание ошибки поля"
        ],
        //...
    }
}
```

#### Ошибки в бизнес логике HTTP 400
```json
{
    "success": false,
    "message": "Описание ошибки"
}
```

#### Ошибка сервера HTTP 500
```json
{
    "message": "Internal Server Error"
}
```

<a name="base-methods"></a>
## Базовые методы
### Доступные валюты
#### GET `/api/currencies`


**Ответ сервера**
```json
{
        "success": true,
        "data": [
            {
                "currency": "rub",
                "precision": 2,
                "symbol": "₽",
                "name": "Российский рубль"
            }
            //...
        ]
}
```

### Доступные платежные методы
#### GET `/api/payment-gateways`


**Ответ сервера**
```json
{
        "success": true,
        "data": [
            {
                "name": "Сбербанк", //название метода
                "code": "sberbank", //код метода
                "schema": "100000000111", //nspk code
                "currency": "rub", //валюта
                "min_limit": "1000", // минимальная сумма сделки (1000 rub)
                "max_limit": "100000", // максимальная сумма сделки (100000 rub)
                "reservation_time": 10, // Время на оплату. (в минутах)
                "detail_types": [ // типы реквизитов которые доступны для данного метода.
                    "card", // номер карты
                    "phone", // номер телефона
                    "account_number" // номер счета
                ]
                //ответ может содержать другие параметры, но они являются устаревшими и не рекамандуются для использования.
            }
            //..
        ]
}
```

<a name="about-api"></a>
## Описание API

- В системе есть два вида API - **Merchant API** и **H2H API**
- **Merchant API** - упрощенная версия api для более простой интеграции.
- **H2H API** - версия api дающая полную возможность управления сделками, спорами. Выдает больше информации.
- Важное замечание - для сделок созданных через **H2H API** не предоставляется платежная ссылка.

<a name="merchant-api"></a>
## Мерчант API

### Создать сделку
#### POST `/api/merchant/order`

**Заголовки**
- **X-Max-Wait-Ms: 30000** - (не обязательный) Этот заголовок указывает в миллисекундах, как долго вы готовы ждать выдачу сделки. 1000 = 1сек. Минимальный срок — это одна секунда. Если вы укажете этот параметр, то при высокой нагрузке когда система за указанное время не успеет выдать сделку, то она вернет ошибку, что не успела обработать запрос HTTP 504. Таким образом, вам не нужно будет ждать долгий зависший запрос, не нужно будет обрывать запрос, и мы не будем создавать псевдо-сделку, которую вы никогда не обработаете. 
- По умолчанию система ждет полминуты прежде чем вернуть ошибку ниже.

```json 
{
  "success": false,
  "message": "Не удалось обработать запрос вовремя. Повторите попытку позже."
}
```

**Параметры запроса**
  - **external_id**<span style="color:red;">*</span> - id сделки на стороне внешнего сервиса. Должен быть уникальным для мерчанта.
  - **amount**<span style="color:red;">*</span> - сумма сделки. (целое число)
  - **payment_gateway** - код платежного метода. Не обязательно если указан **currency**.
  - **currency** - код валюты. Не обязательно если указан **payment_gateway**.
  - **payment_detail_type** - тип реквизита с которым будет создана сделка **card, phone, account_number**.
  - **merchant_id**<span style="color:red;">*</span> - uuid мерчанта. Можно найти на странице мерчанта в разделе настройки. 
  - **callback_url** - POST ссылка на которую будет направлена информация об изменении статуса сделки.
  - **success_url** - GET ссылка на которую перенаправлен пользователь в случае успешной оплаты.
  - **fail_url** - GET ссылка на которую будет перенаправлен пользователь в случае если оплата не была произведена.
  - **manually** - если передано со значением "1", то клиенту будет дана возможность самому выбрать платежный метод.
  - **transgran** - фильтр по трансграничным платежным методам (true/false). Необязательный параметр.

**Ответ сервера**
```json
{
        "success": true,
        "data": {
            "order_id": "4b3a163b...", // uuid сделки внутри системы.
            "external_id": "...", 
            "merchant_id": "...",
            "amount": "1000",
            "currency": "rub",
            "status": "pending", // статус сделки. success, fail, pending.
            "sub_status": "pending", //accepted, successfully_paid, successfully_paid_by_resolved_dispute, waiting_details_to_be_selected, waiting_for_payment, waiting_for_dispute_to_be_resolved, canceled_by_dispute, expired, cancelled
            "callback_url": null,
            "success_url": null,
            "fail_url": null, 
            "payment_gateway": "sberbank", // код платежного метода
            "payment_gateway_schema": "100000000111", // nspk code 
            "payment_gateway_name": "Сбербанк", // название платежного метода
            "finished_at": null, // время закрытия сделки
            "expires_at": 1731375451, // время когда сделка будет автоматически закрыта.
            "created_at": 1731375391, // время создания сделки.
            "payment_link": "https://example.com/payment/4b3a163b..." // ссылка на оплату.
        }
}
```

### Получить сделку
#### GET `/api/merchant/order/{order_id}`
- Содержит тоже самое, что объект при создании сделки.
- Альтернатива: `/api/merchant/order/{merchant_id}/{external_id}`

<a name="h2h-api"></a>
## H2H API

### Создать сделку
#### POST `/api/h2h/order`

**Заголовки**
- **X-Max-Wait-Ms: 30000** - (не обязательный) Этот заголовок указывает в миллисекундах, как долго вы готовы ждать выдачу сделки. 1000 = 1сек. Минимальный срок — это одна секунда. Если вы укажете этот параметр, то при высокой нагрузке когда система за указанное время не успеет выдать сделку, то она вернет ошибку, что не успела обработать запрос HTTP 504. Таким образом, вам не нужно будет ждать долгий зависший запрос, не нужно будет обрывать запрос, и мы не будем создавать псевдо-сделку, которую вы никогда не обработаете. 
- По умолчанию система ждет полминуты прежде чем вернуть ошибку ниже.

```json 
{
  "success": false,
  "message": "Не удалось обработать запрос вовремя. Повторите попытку позже."
}
```

**Описание параметров запроса**
  - **external_id**<span style="color:red;">*</span> - id сделки на стороне внешнего сервиса. Должен быть уникальным для мерчанта.
  - **amount**<span style="color:red;">*</span> - сумма сделки. (целое число)
  - **payment_gateway** - код платежного метода. Не обязательно если указан **currency**.
  - **currency** - код валюты. Не обязательно если указан **payment_gateway**.
  - **payment_detail_type** - тип реквизита с которым будет создана сделка **card, phone, account_number**.
  - **merchant_id**<span style="color:red;">*</span> - uuid мерчанта. Можно найти на странице мерчанта в разделе настройки.
  - **callback_url** - POST ссылка на которую будет направлена информация об изменении статуса сделки.
  - **transgran** - фильтр по трансграничным платежным методам (true/false). Необязательный параметр.

**Ответ сервера**
```json
{
        "success": true,
        "data": {
            "order_id": "3db07a16...", // uuid сделки внутри системы.
            "external_id": "...",
            "merchant_id": "3db07a16...",
            "base_amount": "1000", // начальная сумма при создании сделки.
            "amount": "1040", // сумма к оплате, конечная оплаченная клиентом сумма. содержит в себе комиссию клиента, если указана в настройках мерчанта.  в случае спора с изменением суммы callback будет содержать новую сумму в поле amount
            "profit": "9.94", // amount in usdt
            "merchant_profit": "9.05", // доход мерчанта в usdt
            "currency": "rub",
            "profit_currency": "usdt",
            "conversion_price_currency": "rub",
            "conversion_price": "100.77", // цена конвертации RUB in USDT with trader's commission.
            "status": "pending", // success, pending, fail
            "sub_status": "pending", // accepted, successfully_paid, successfully_paid_by_resolved_dispute, waiting_details_to_be_selected, waiting_for_payment, waiting_for_dispute_to_be_resolved, canceled_by_dispute, expired, cancelled
            "callback_url": "...", // POST запрос
            "payment_gateway": "sberbank", // код платежного метода
            "payment_gateway_schema": "100000000111", // nspk code
            "payment_gateway_name": "Сбербанк", // название платежного метода
            "payment_gateway_is_transgran": true, // трансгран (true/false)
            "payment_detail": {
                "detail": "1000200030004000", // реквизит для перевода
                "detail_type": "card", // тип реквизита
                "initials": "Пол Атрейдес" // владелец реквизита
            },
            "merchant": {
                "name": "...",
                "description": "..."
            },
            "finished_at": null, // время закрытия сделки
            "expires_at": 1731375451, // время когда сделка будет автоматически закрыта.
            "created_at": 1731375391, // время создания сделки.
            "current_server_time": 1731655862 // текущие время сервера
        }
}
```

### Получить сделку
#### GET `/api/h2h/order/{order_id}`
- Возвращает такой же объект как при создании сделки.
- Альтернатива: `/api/h2h/order/{merchant_id}/{external_id}`

### Закрыть сделку
#### PATCH `/api/h2h/order/{order_id}/cancel`
- Досрочно закрывает сделку если она находится в статусе pending и не имеет открытых споров.

### Открыть спор
#### POST `/api/h2h/order/{order_id}/dispute`
- Если сделка все еще открыта, то она будет закрыта перед открытием спора, так если бы вы вызвали предыдущий метод выше.

**Описание параметров запроса**
    - **receipt**<span style="color:red;">*</span> - изображение **jpeg,jpg,png,pdf** преобразованное в **base64**. Размер файла до **5МБ**.

**Ответ сервера**
```json
{
        "success": true,
        "data": {
            "order_id": "3db07a16...",
            "status": "pending",
            "cancel_reason": null, // причина отказа (если спор был отклонен)
            "reason": null // комментарий к отказу (если спор был отклонен)
        }
}
```

### Получить спор
#### GET `/api/h2h/order/{order_id}/dispute`
- Ответ такой же как при открытии спора.

<a name="disputes"></a>
## Работа со спорами

### Получить список причин отказа диспута (H2H API)
#### GET `/api/h2h/dispute/cancel-reasons`

**Ответ сервера**
```json
{
    "success": true,
    "data": {
        "cancel_reasons": [
            {
                "value": "requires_bank_statement",
                "label": "Требуется выписка с банка"
            },
            {
                "value": "requires_video_proof",
                "label": "Требуется видеодоказательство платежа"
            },
            {
                "value": "wrong_payment_refund_required",
                "label": "Неправильный платеж, требуется возврат"
            },
            {
                "value": "incorrect_amount_received",
                "label": "Получена неправильная сумма"
            }
        ]
    }
}
```

### Описание причин отказа диспута

| Значение | Описание |
|----------|----------|
| `requires_bank_statement` | Требуется выписка с банка |
| `requires_video_proof` | Требуется видеодоказательство платежа |
| `wrong_payment_refund_required` | Неправильный платеж, требуется возврат |
| `incorrect_amount_received` | Получена неправильная сумма |

<a name="auto-withdrawals"></a>
## Авто вывод с баланса

### Получить доступный баланс 
#### GET `/api/wallet/balance`

**Ответ сервера**
```json
{
    "success": true,
    "data": {
        "balance": "10000.00"
    }
}
```

### Создать запрос на вывод
#### POST `/api/wallet/withdraw`

**Описание параметров запроса**
- **amount**<span style="color:red;">*</span> - сумма вывода (целое число)
- **address**<span style="color:red;">*</span> - адрес куда сделать вывод средств. 
- **network**<span style="color:red;">*</span> - USDT сеть ***bsc, arb, trx***

**Ответ сервера**
```json
{
    "success": true,
    "data": {
        "invoice_id": "...",
        "tx_hash": "..."
    }
}
```

<a name="order-statuses"></a>
## Описание статусов сделок
### Status
| Значение | Описание |
|----------|----------|
| `success` | Операция успешно завершена. |
| `pending` | Операция находится в ожидании обработки. |
| `fail` | Операция завершилась неудачно. |
### Sub Status
| Значение | Описание |
|----------|----------|
| `accepted` | Закрыт вручную. |
| `successfully_paid` | Закрыт автоматически. |
| `successfully_paid_by_resolved_dispute` | Закрыт в результате принятого спора. |
| `waiting_details_to_be_selected` | Ждет выбора реквизитов. |
| `waiting_for_payment` | Ждет платежа. |
| `waiting_for_dispute_to_be_resolved` | Ждет решения спора. |
| `canceled_by_dispute` | Отменен в результате спора. |
| `expired` | Отменен по истечению времени. |
| `cancelled` | Отменен вручную. |

<a name="addition"></a>
## Общее дополнение к описанию API
- Не для всех вариантов параметров может быть доступный реквизит. Поэтому сервер вернет сообщение, что реквизит не найден.
- Параметр payment_gateway - создаст сделку только для этого платежного метода. В то время как параметр currency создаст сделку в рамках это валюты, но для любого платежного метода.
- Параметры payment_gateway и currency взаимоисключающие и не могут быть использованы одновременно.
- Если сумма сделки выходит за лимиты указанного платежного метода или всех доступных методов указанных в **/api/payment-gateways** то сервер вернет ошибку что подходящий платежный метод не найден.
- Параметр callback_url также есть в настройках мерчанта, но если параметр передать в запросе к API, то он будет старше. Т.е. уведомление будет отправлено по url указанного в запросе, но не в админке.
- Параметр payment_detail_type нужно использовать аккуратно. И чаще всего в связке с параметров currency. Так как не для всех платежных методов если реквизиты всех типов.
- Cумма сделки может изменить после ее создания. Вернется в параметре amount. Так как существует наценка в виде комиссии клиента. Указывается в настройках мерчанта. Например при комиссии клиента в 4%, сумма 1000 будет изменена на 1040.
- Сделкой можно управлять только через соответствующий API

<a name="callback"></a>
## Уведомление об изменении статуса платежа
- По ссылке указанной в настройках мерчанта, или переданной в параметре callback_url при создании сделки, будет отправлено уведомление (POST запрос) если сделка изменит свой статус.
- Уведомление содержит данные соответствующие данным которы возвращает метод **GET /api/h2h/order/{order_id}** или **GET /api/merchant/order/{order_id}** в зависимости от используемого API. Ниже пример для H2H API.

<a name="payouts"></a>
## API для выплат
- Доступ нужно запросить у администратора.

## Методы

### Получить список предложений на выплату
#### GET `/api/payout/offers`

**Ответ сервера**
```json
{
    "success": true,
    "data": {
        "rub": {
            "sberbank_rub": {
                "max_amount": 100000,
                "min_amount": 1000,
                "currency": "rub",
                "detail_type": "card",
                "payment_gateway": {
                    "name": "Сбербанк",
                    "name_with_currency": "Сбербанк RUB",
                    "code": "sberbank_rub",
                },
                "offers_count": 1,
                "recommended_max_amount": 100000,
                "recommended_min_amount": 1000
            },
            //...
        },
        //...
    }
}
```

### Создать выплату
#### POST `/api/payout`

**Описание параметров запроса**
    - **payout_gateway_id** - id направления, находится в админке.
    - **external_id** - id сделки на стороне внешнего сервиса. Должен быть уникальным для направления.
    - **detail** - реквизиты на которые будут отправлены средства.
    - **detail_type** - тип реквизитов.
    - **detail_initials** - держатель реквизитов.
    - **amount** - сумма выплаты (в валюте платежного метода).
    - **payment_gateway** - платежный метод на который оформлен реквизит.
    - **callback_url** - ссылка на которую будет направлена информация об изменении статуса выплаты. Не обязательный параметр.

**Ответ сервера**
```json
{
    "success": true,
    "data": {
        "uuid": "...",
        "external_id": "...",
        "detail": "1000200030004000",
        "detail_type": "card",
        "detail_initials": "Петр К.",
        "payout_amount": "1000",
        "currency": "rub", 
        "base_liquidity_amount": "9.31",
        "liquidity_amount": "10.14",
        "liquidity_currency": "usdt",
        "service_commission_rate": 9,
        "service_commission_amount": "0.83",
        "trader_profit_amount": "9.31",
        "trader_exchange_markup_rate": 2.5,
        "trader_exchange_markup_amount": "0.23",
        "base_exchange_price": "110.07",
        "exchange_price": "107.32",
        "status": "pending",
        "sub_status": "processing_by_trader",
        "callback_url": "https://example.com/callback",
        "payment_gateway": "sberbank_rub",
        "payment_gateway_name": "Сбербанк",
        "finished_at": null,
        "expires_at": 1736145380,
        "created_at": 1736144380
    }
}
```

### Получить выплату
#### GET `/api/payout/{uuid}`
- Ответ какой же, как при создании выплаты.
