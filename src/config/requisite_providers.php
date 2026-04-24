<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Конфигурация провайдеров реквизитов
    |--------------------------------------------------------------------------
    |
    | Этот файл содержит настройки для всех провайдеров реквизитов.
    | Провайдеры выполняются в порядке приоритета (меньше число = выше приоритет).
    |
    */

    'external_provider_1' => [
        'enabled' => env('EXTERNAL_PROVIDER_1_ENABLED', false),
        'api_url' => env('EXTERNAL_PROVIDER_1_API_URL', 'https://api.provider1.com'),
        'api_key' => env('EXTERNAL_PROVIDER_1_API_KEY'),
        'timeout' => env('EXTERNAL_PROVIDER_1_TIMEOUT', 15),
        'retry_attempts' => env('EXTERNAL_PROVIDER_1_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('EXTERNAL_PROVIDER_1_RETRY_DELAY', 1000),
        'supported_currencies' => ['RUB', 'USD', 'EUR'],
        'supported_detail_types' => ['card', 'phone', 'sim'],
        'supported_gateways' => [1, 2, 3], // ID платежных шлюзов
        'min_amount' => 1000,
        'max_amount' => 500000,
    ],

    'external_provider_2' => [
        'enabled' => env('EXTERNAL_PROVIDER_2_ENABLED', false),
        'api_url' => env('EXTERNAL_PROVIDER_2_API_URL', 'https://api.provider2.com'),
        'api_key' => env('EXTERNAL_PROVIDER_2_API_KEY'),
        'timeout' => env('EXTERNAL_PROVIDER_2_TIMEOUT', 20),
        'retry_attempts' => env('EXTERNAL_PROVIDER_2_RETRY_ATTEMPTS', 2),
        'retry_delay' => env('EXTERNAL_PROVIDER_2_RETRY_DELAY', 1500),
        'supported_currencies' => ['RUB', 'KZT', 'TJS', 'UZS'],
        'supported_detail_types' => ['card', 'account_number', 'sim'],
        'supported_gateways' => [4, 5, 6],
        'min_amount' => 500,
        'max_amount' => 1000000,
    ],

    'partner_platform' => [
        'class' => \App\Services\RequisiteProviders\PartnerPlatformProvider::class,
        'enabled' => env('PARTNER_PLATFORM_ENABLED', false),
        'priority' => 15,
        'timeout' => env('PARTNER_PLATFORM_TIMEOUT', 30),
        'retry_attempts' => env('PARTNER_PLATFORM_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('PARTNER_PLATFORM_RETRY_DELAY', 1000),
        
        // API партнерской платформы
        'api_url' => env('PARTNER_PLATFORM_API_URL', 'https://partner.hill2.com'),
        'api_key' => env('PARTNER_PLATFORM_API_KEY'),
        
        // URL для получения callback от партнерской платформы
        'callback_base_url' => env('PARTNER_PLATFORM_CALLBACK_BASE_URL', config('app.url')),
        
        // Лимиты
        'min_amount' => env('PARTNER_PLATFORM_MIN_AMOUNT', 100), // 1 рубль в копейках
        'max_amount' => env('PARTNER_PLATFORM_MAX_AMOUNT', 500000),
        
        // Поддерживаемые параметры
        'supported_currencies' => ['RUB', 'USD', 'EUR'],
        'supported_detail_types' => ['card', 'phone', 'account_number', 'qr_code', 'sim'],
        'supported_gateways' => [], // Пустой массив = поддерживаем все
        
        // Маппинг мерчантов (ID в нашей системе => ID в партнерской)
        'merchant_mapping' => [
            // 1 => 'partner_merchant_123',
            // 2 => 'partner_merchant_456',
        ],
        'default_partner_merchant_id' => env('PARTNER_PLATFORM_DEFAULT_MERCHANT_ID'),
        
        // Маппинг шлюзов (ID в нашей системе => код в партнерской)
        'gateway_mapping' => [
            // 1 => 'sberbank',
            // 2 => 'tinkoff',
            // 3 => 'vtb',
        ],
    ],

    'garex' => [
        'enabled' => env('GAREX_ENABLED', false),
        'api_url' => env('GAREX_API_URL', 'https://garex.one'),
        'api_token' => env('GAREX_API_TOKEN'),
        'merchant_id' => env('GAREX_MERCHANT_ID', '138_JNreYVjp77dg'),
        'trader_id' => env('GAREX_TRADER_ID', 50), // ID трейдера Garex
        'timeout' => env('GAREX_TIMEOUT', 30),
        'retry_attempts' => env('GAREX_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('GAREX_RETRY_DELAY', 1000),
        
        // URL для получения callback от Garex
        'callback_base_url' => env('GAREX_CALLBACK_BASE_URL', config('app.url')),
        
        // Лимиты
        'min_amount' => env('GAREX_MIN_AMOUNT', 100), // 1 рубль в копейках
        'max_amount' => env('GAREX_MAX_AMOUNT', 50000000), // 500k рублей в копейках
        
        // Поддерживаемые параметры
        'supported_currencies' => ['RUB', 'AZN'],
        'supported_detail_types' => ['card', 'phone', 'account_number', 'sim'],
        'supported_gateways' => [], // Пустой массив = поддерживаем все
        
        // Маппинг методов платежа
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
        
        // Маппинг типов реквизитов на методы
        'method_mapping' => [
            'card' => 'c2c',
            'phone' => 'sbp',
            'account_number' => 'bank-account',
        ],
        
        // Маппинг гейтвеев на коды банков
        'gateway_to_bank_mapping' => [
            'sber' => 'sber',
            't-bank' => 't-bank',
            'alfa-bank' => 'alfa-bank',
            'vtb' => 'vtb',
            'gazprombank' => 'gazprombank',
            'sovcombank' => 'sovcombank',
            'psbank' => 'psbank',
            'pochtabank' => 'pochtabank',
            'ozonbank' => 'ozonbank',
            'wb-bank' => 'wb-bank',
            'raiffeisen' => 'raiffeisen',
            'mts-bank' => 'mts-bank',
            'rshb' => 'rshb',
            'zenit' => 'zenit',
            'open' => 'open',
            'avangard' => 'avangard',
        ],
    ],

    'methodpay' => [
        'enabled' => env('METHODPAY_ENABLED', false),
        'api_url' => env('METHODPAY_API_URL', 'https://methodpay.example.com'),
        'access_token' => env('METHODPAY_ACCESS_TOKEN'),
        'merchant_id' => env('METHODPAY_MERCHANT_ID'), // UUID мерчанта в MethodPay
        'trader_id' => env('METHODPAY_TRADER_ID', 0), // ID внутреннего пользователя-трейдера
        'timeout' => env('METHODPAY_TIMEOUT', 30),
        'max_wait_ms' => env('METHODPAY_MAX_WAIT_MS', 25000), // X-Max-Wait-Ms заголовок
        'retry_attempts' => env('METHODPAY_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('METHODPAY_RETRY_DELAY', 1000),
        'priority' => env('METHODPAY_PRIORITY', 25),
        
        // URL для получения callback от MethodPay
        'callback_base_url' => env('METHODPAY_CALLBACK_BASE_URL', config('app.url')),
        
        // Лимиты (в копейках)
        'min_amount' => env('METHODPAY_MIN_AMOUNT', 100000), // 1000 рублей
        'max_amount' => env('METHODPAY_MAX_AMOUNT', 10000000), // 100k рублей
        
        // Поддерживаемые параметры
        'supported_currencies' => ['RUB', 'USD', 'EUR'],
        'supported_detail_types' => ['card', 'phone', 'account_number'],
        'supported_gateways' => [], // Пустой массив = поддерживаем все
        
        // Маппинг платежных методов (наш код => код MethodPay)
        'gateway_mapping' => [
            'sberbank' => 'sberbank',
            'tinkoff' => 'tinkoff',
            'alfabank' => 'alfabank',
            'vtb' => 'vtb',
            'gazprombank' => 'gazprombank',
            'raiffeisenbank' => 'raiffeisenbank',
            'sovkom' => 'sovkom',
            'promsvyaz' => 'promsvyaz',
        ],
    ],

    'cashout' => [
        'enabled'               => env('CASHOUT_ENABLED', false),
        'api_url'               => env('CASHOUT_API_URL', 'https://admin.cashout.cx'),
        'api_key'               => env('CASHOUT_API_KEY'),
        'trader_id'             => env('CASHOUT_TRADER_ID', 0),
        'timeout'               => env('CASHOUT_TIMEOUT', 30),
        'retry_attempts'        => env('CASHOUT_RETRY_ATTEMPTS', 3),
        'retry_delay'           => env('CASHOUT_RETRY_DELAY', 1000),

        // Лимиты (в рублях)
        'min_amount'            => env('CASHOUT_MIN_AMOUNT', 100),
        'max_amount'            => env('CASHOUT_MAX_AMOUNT', 500000),

        'supported_currencies'  => ['RUB'],
        'supported_detail_types'=> ['card', 'phone'],
        'supported_gateways'    => [],

        // Маппинг DetailType → метод CashOut API (переопределяется в additional_settings терминала)
        'method_card'           => env('CASHOUT_METHOD_CARD', 'CARD'),
        'method_phone'          => env('CASHOUT_METHOD_PHONE', 'SBP'),
    ],

    'paygatecore' => [
        'enabled'               => env('PAYGATECORE_ENABLED', false),
        'api_url'               => env('PAYGATECORE_API_URL', 'https://api.paygatecore.com'),
        'api_token'             => env('PAYGATECORE_API_TOKEN'),
        'secret_key'            => env('PAYGATECORE_SECRET_KEY'),
        'trader_id'             => env('PAYGATECORE_TRADER_ID', 0),
        'timeout'               => env('PAYGATECORE_TIMEOUT', 30),
        'retry_attempts'        => env('PAYGATECORE_RETRY_ATTEMPTS', 3),
        'retry_delay'           => env('PAYGATECORE_RETRY_DELAY', 1000),

        // Лимиты (в рублях)
        'min_amount'            => env('PAYGATECORE_MIN_AMOUNT', 100),
        'max_amount'            => env('PAYGATECORE_MAX_AMOUNT', 500000),

        'supported_currencies'  => ['RUB'],
        'supported_detail_types'=> ['card', 'phone', 'sim'],
        'supported_gateways'    => [],

        // Маппинг DetailType → эндпоинт PayGateCore (переопределяется в additional_settings терминала)
        'method_card'           => env('PAYGATECORE_METHOD_CARD', 'card'),
        'method_phone'          => env('PAYGATECORE_METHOD_PHONE', 'phone'),
        'method_sim'            => env('PAYGATECORE_METHOD_SIM', 'sim'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Глобальные настройки
    |--------------------------------------------------------------------------
    */

    'global' => [
        'circuit_breaker_enabled' => env('REQUISITE_PROVIDERS_CIRCUIT_BREAKER', true),
        'circuit_breaker_failure_threshold' => env('REQUISITE_PROVIDERS_FAILURE_THRESHOLD', 5),
        'circuit_breaker_timeout' => env('REQUISITE_PROVIDERS_CIRCUIT_TIMEOUT', 60), // seconds
        'log_all_requests' => env('REQUISITE_PROVIDERS_LOG_REQUESTS', false),
        'fallback_to_internal_only' => env('REQUISITE_PROVIDERS_FALLBACK_INTERNAL', false),
    ],
]; 