<?php

namespace App\Services\RequisiteProviders;

use App\Enums\ProviderIntegrationEnum;

/**
 * Конфигурация полей для каждого типа интеграции
 * 
 * Определяет какие поля нужны для настройки каждой интеграции.
 * Эти поля хранятся в additional_settings терминала.
 */
class IntegrationFieldsConfig
{
    /**
     * Получить поля для интеграции
     */
    public static function getFields(ProviderIntegrationEnum $integration): array
    {
        return match ($integration) {
            ProviderIntegrationEnum::GAREX => self::getGarexFields(),
            ProviderIntegrationEnum::METHODPAY => self::getMethodPayFields(),
            ProviderIntegrationEnum::ALPHAPAY => self::getAlphaPayFields(),
            ProviderIntegrationEnum::X023 => self::getX023Fields(),
        };
    }

    /**
     * Получить все интеграции с их полями
     */
    public static function getAllIntegrations(): array
    {
        $result = [];
        
        foreach (ProviderIntegrationEnum::cases() as $integration) {
            $result[$integration->value] = [
                'name' => self::getIntegrationName($integration),
                'fields' => self::getFields($integration),
            ];
        }
        
        return $result;
    }

    /**
     * Получить название интеграции
     */
    public static function getIntegrationName(ProviderIntegrationEnum $integration): string
    {
        return match ($integration) {
            ProviderIntegrationEnum::GAREX => 'Garex',
            ProviderIntegrationEnum::METHODPAY => 'MethodPay (H2H)',
            ProviderIntegrationEnum::ALPHAPAY => 'AlphaPay',
            ProviderIntegrationEnum::X023 => 'X023',
        };
    }

    /**
     * Валидировать настройки терминала для интеграции
     */
    public static function validateSettings(ProviderIntegrationEnum $integration, array $settings): array
    {
        $errors = [];
        $fields = self::getFields($integration);
        
        foreach ($fields as $field) {
            $key = $field['key'];
            $required = $field['required'] ?? false;
            
            if ($required && (empty($settings[$key]) || $settings[$key] === '')) {
                $errors[$key] = "Поле '{$field['label']}' обязательно для заполнения";
            }
        }
        
        return $errors;
    }

    /**
     * Получить обязательные поля для интеграции
     */
    public static function getRequiredFields(ProviderIntegrationEnum $integration): array
    {
        $fields = self::getFields($integration);
        
        return array_values(array_filter($fields, fn($field) => $field['required'] ?? false));
    }

    // ==================== Поля для каждой интеграции ====================

    /**
     * Поля для Garex
     * 
     * Примечание: trader_id берётся из настроек провайдера (providers.trader_id),
     * поэтому не нужно указывать его в конфиге терминала
     */
    private static function getGarexFields(): array
    {
        return [
            [
                'key' => 'api_url',
                'label' => 'API URL',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'https://garex.one',
                'default' => 'https://garex.one',
                'description' => 'Базовый URL API Garex',
            ],
            [
                'key' => 'api_token',
                'label' => 'API Token',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Введите API токен',
                'description' => 'Токен авторизации для API',
            ],
            [
                'key' => 'merchant_id',
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => '138_JNreYVjp77dg',
                'description' => 'Идентификатор мерчанта в системе Garex',
            ],
        ];
    }

    /**
     * Поля для MethodPay (H2H API)
     * 
     * Примечание: trader_id берётся из настроек провайдера (providers.trader_id),
     * поэтому не нужно указывать его в конфиге терминала
     */
    private static function getMethodPayFields(): array
    {
        return [
            [
                'key' => 'api_url',
                'label' => 'API URL',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'https://methodpay.example.com',
                'description' => 'Базовый URL API MethodPay',
            ],
            [
                'key' => 'access_token',
                'label' => 'Access Token',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Введите Access Token',
                'description' => 'Токен авторизации (заголовок Access-Token)',
            ],
            [
                'key' => 'merchant_id',
                'label' => 'Merchant UUID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'uuid мерчанта',
                'description' => 'UUID мерчанта в системе MethodPay',
            ],
        ];
    }

    /**
     * Поля для AlphaPay (app.cash)
     * 
     * API документация: https://app.cash
     * Авторизация: HMAC (APIKEY, SIGNATURE, NONCE заголовки)
     * 
     * Примечание: trader_id берётся из настроек провайдера (providers.trader_id),
     * поэтому не нужно указывать его в конфиге терминала
     */
    private static function getAlphaPayFields(): array
    {
        return [
            [
                'key' => 'api_url',
                'label' => 'API URL',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'https://app.cash',
                'default' => 'https://app.cash',
                'description' => 'Базовый URL API AlphaPay (app.cash)',
            ],
            [
                'key' => 'api_key',
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'gEzDXCaLh4odhLX0JoT5ZhDxhpgT1FtS',
                'description' => 'Публичный ключ API (APIKEY заголовок)',
            ],
            [
                'key' => 'secret_key',
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Введите секретный ключ',
                'description' => 'Секретный ключ для HMAC подписи запросов',
            ],
            [
                'key' => 'shop_id',
                'label' => 'Shop ID (UUID)',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'e81965c9-9a8b-4f30-8a66-e10c1d6dd34b',
                'description' => 'UUID магазина в системе AlphaPay',
            ],
        ];
    }

    /**
     * Поля для X023
     * 
     * API: POST /api/v1/order/
     * Авторизация: Bearer token в заголовке Authorization
     * 
     * Статусы: ACTIVE, CLOSED, EXPIRED, APPEAL, DECLINED
     */
    private static function getX023Fields(): array
    {
        return [
            [
                'key' => 'api_url',
                'label' => 'API URL',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'https://x023.example.com',
                'description' => 'Базовый URL API X023',
            ],
            [
                'key' => 'api_token',
                'label' => 'API Token (Bearer)',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Введите Bearer токен',
                'description' => 'Токен авторизации для заголовка Authorization: Bearer {token}',
            ],
        ];
    }
}
