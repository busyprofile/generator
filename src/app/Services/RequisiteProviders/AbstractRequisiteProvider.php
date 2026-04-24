<?php

namespace App\Services\RequisiteProviders;

use App\Contracts\RequisiteProviderContract;
use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
use App\Jobs\LogRequisiteProviderJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\RequisiteProviderLog;

abstract class AbstractRequisiteProvider implements RequisiteProviderContract
{
    protected array $config = [];
    protected array $httpRequestMeta = [];
    protected array $httpResponseMeta = [];
    protected array $lastAttemptMeta = [];
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Получить конфигурацию по умолчанию
     */
    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'max_response_time_ms' => 10000, // 10 секунд в миллисекундах
            'number_of_retries' => 3,
            'retry_delay' => 1000, // milliseconds
        ];
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Быстрые геттеры, чтобы не таскать весь config.
     * (Используются для записи provider_id/provider_terminal_id в orders)
     */
    public function getProviderId(): ?int
    {
        return $this->config['provider_id'] ?? null;
    }

    public function getProviderTerminalId(): ?int
    {
        return $this->config['provider_terminal_id'] ?? null;
    }

    /**
     * Последняя попытка получения реквизитов (для диагностики).
     * Не полагайтесь на структуру как на публичный API — только для дебага.
     */
    public function getLastAttemptMeta(): array
    {
        return $this->lastAttemptMeta;
    }

    public function isAvailable(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Получить текущий баланс провайдера.
     * Переопределите в конкретном провайдере для реализации.
     * 
     * @return float|null Баланс в USDT или null если не поддерживается/ошибка
     */
    public function getBalance(): ?float
    {
        return null;
    }

    /**
     * Отменить сделку у провайдера.
     * Переопределите в конкретном провайдере для реализации.
     * 
     * @param \App\Models\Order $order Ордер для отмены
     * @return bool Успешность операции
     */
    public function cancelOrder(\App\Models\Order $order): bool
    {
        return false;
    }

    /**
     * Логирование с префиксом провайдера
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $context['provider'] = $this->getName();
        Log::log($level, "[{$this->getName()}] {$message}", $context);
    }

    /**
     * Детальное логирование HTTP запроса к внешнему провайдеру (Production)
     */
    protected function logHttpRequest(
        string $method,
        string $url,
        array $headers,
        $body,
        ?int $statusCode = null,
        $responseBody = null,
        ?float $durationMs = null
    ): void {
        // Сохраняем метаданные для последующей записи в БД (requisite_provider_logs)
        if ($statusCode === null) {
            $this->httpRequestMeta = [
                'method' => $method,
                'url' => $url,
                'headers' => $headers,
                'body' => $body,
            ];
        } else {
            $this->httpResponseMeta = [
                'status_code' => $statusCode,
                'body' => $responseBody,
                'duration_ms' => $durationMs ? round($durationMs, 2) : null,
            ];
        }

        $logData = [
            'provider' => $this->getName(),
            'provider_id' => $this->getProviderId(),
            'provider_terminal_id' => $this->getProviderTerminalId(),
            'request' => [
                'method' => $method,
                'url' => $url,
                'headers' => $headers,
                'body' => $body,
            ],
        ];

        if ($statusCode !== null) {
            $logData['response'] = [
                'status_code' => $statusCode,
                'body' => $responseBody,
                'duration_ms' => $durationMs ? round($durationMs, 2) : null,
            ];
        }

        // Логируем детально в debug-уровне (требование заказчика)
        Log::error("[{$this->getName()}] HTTP Request to External Provider", $logData);
    }

    /**
     * Логирование статистики провайдера в базу данных
     */
    protected function logProviderStats(
        string $requestType,
        bool $success,
        int $responseTimeMs,
        ?string $errorMessage = null,
        ?array $requestParams = null,
        ?array $responseData = null,
        ?int $merchantId = null,
        ?int $orderId = null,
        ?string $detailId = null,
        int $retryAttempt = 1
    ): void {
        try {
            RequisiteProviderLog::createLog(
                providerName: $this->getName(),
                providerId: $this->getProviderId(),
                providerTerminalId: $this->getProviderTerminalId(),
                requestType: $requestType,
                success: $success,
                responseTimeMs: $responseTimeMs,
                errorMessage: $errorMessage,
                requestParams: $requestParams,
                responseData: $responseData,
                merchantId: $merchantId,
                orderId: $orderId,
                detailId: $detailId,
                retryAttempt: $retryAttempt
            );
        } catch (\Exception $e) {
            // Если не удалось записать лог - не ломаем основную логику
            Log::warning("Failed to log provider stats: " . $e->getMessage(), [
                'provider' => $this->getName(),
                'request_type' => $requestType,
            ]);
        }
    }

    /**
     * Обёртка для getRequisites с автоматическим логированием
     */
    protected function executeWithLogging(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null,
        ?int $orderId = null,
        ?callable $callback = null
    ): ?Detail {
        $startTime = microtime(true);
        $success = false;
        $errorMessage = null;
        $detail = null;
        $responseData = null;

        $this->log('debug', 'executeWithLogging() started', ['provider' => $this->getName()]);

        try {
            $this->log('debug', 'executeWithLogging() calling callback/getRequisites', ['provider' => $this->getName()]);
            
            // Вызываем основной метод получения реквизитов
            $detail = $callback ? $callback() : $this->getRequisites($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran);
            
            $success = $detail !== null;
            
            $this->log('debug', 'executeWithLogging() callback completed', [
                'provider' => $this->getName(),
                'detail_returned' => $detail !== null,
                'success' => $success
            ]);
            
            if ($detail) {
                $responseData = [
                    'detail_id' => $detail->id,
                    'user_id' => $detail->userID,
                    'amount' => $detail->amount->toBeauty(),
                    'currency' => $detail->currency->getCode(),
                ];
            }

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            $this->log('debug', 'executeWithLogging() caught exception', [
                'provider' => $this->getName(),
                'exception_class' => get_class($e),
                'exception_message' => $errorMessage,
                'will_log_to_db' => true
            ]);
            
            $this->log('error', 'Failed to get requisites: ' . $errorMessage, [
                'merchant_id' => $merchant->id,
                'amount' => $amount->toBeauty(),
                'exception' => get_class($e),
            ]);
        }

        $responseTime = round((microtime(true) - $startTime) * 1000);

        $this->log('debug', 'executeWithLogging() preparing to log to DB', [
            'provider' => $this->getName(),
            'success' => $success,
            'error_message' => $errorMessage,
            'response_time_ms' => $responseTime
        ]);

        // Готовим HTTP метаданные заранее (нужны и для БД логов, и для lastAttemptMeta)
        $httpRequestRaw = !empty($this->httpRequestMeta) ? $this->httpRequestMeta : null;
        $httpResponseRaw = !empty($this->httpResponseMeta) ? $this->httpResponseMeta : null;
        $httpRequest = $this->redactHttpMeta($httpRequestRaw);
        $httpResponse = $this->truncateHttpResponse($httpResponseRaw);

        // Логируем в БД асинхронно через диспетчер задач для избежания конфликтов транзакций
        try {
            $requestParams = [
                'merchant_id' => $merchant->id,
                'amount' => $amount->toBeauty(),
                'currency' => $currency?->getCode(),
                'gateway_id' => $gateway?->id,
                'detail_type' => $detailType?->value,
                'transgran' => $transgran,
                'order_id' => $orderId,
            ];

            if ($httpRequest) {
                $requestParams['http_request'] = $httpRequest;
            }

            // Безопасная обработка error_message
            $processedErrorMessage = $this->sanitizeErrorMessage($errorMessage);

            // Диспетчим асинхронную задачу для логирования
                $responseDataForLog = $responseData ?? [];
                if ($httpResponse) {
                    $responseDataForLog['http_response'] = $httpResponse;
                }

                LogRequisiteProviderJob::dispatch(
                providerName: $this->getName(),
                success: $success,
                merchantId: $merchant->id,
                    requestParams: $requestParams,
                errorMessage: $processedErrorMessage,
                responseTimeMs: $responseTime,
                    responseData: $responseDataForLog,
                orderId: $orderId,
                detailId: $responseData['detail_id'] ?? null,
                providerId: $this->getProviderId(),
                providerTerminalId: $this->getProviderTerminalId(),
            );

            $this->log('info', 'executeWithLogging() dispatched async logging job', [
                'provider' => $this->getName(),
                'success_logged' => $success,
                'error_message_length' => strlen($processedErrorMessage ?? ''),
                'original_error_length' => strlen($errorMessage ?? ''),
                'was_truncated' => strlen($errorMessage ?? '') !== strlen($processedErrorMessage ?? ''),
                'async_dispatch' => true
            ]);

        } catch (\Exception $e) {
            $this->log('error', 'executeWithLogging() async dispatch failed', [
                'provider' => $this->getName(),
                'exception' => $e->getMessage(),
                'fallback' => 'async_logging_failed'
            ]);
        }

        $this->log('debug', 'executeWithLogging() finished', ['provider' => $this->getName()]);

        // Сохраняем диагностическую информацию (с редактированием секретов)
        $this->lastAttemptMeta = [
            'provider' => $this->getName(),
            'provider_id' => $this->getProviderId(),
            'provider_terminal_id' => $this->getProviderTerminalId(),
            'success' => $success,
            'error_message' => $errorMessage,
            'response_time_ms' => $responseTime,
            'request_params' => [
                'merchant_id' => $merchant->id,
                'amount' => $amount->toBeauty(),
                'amount_toPrecision' => $amount->toPrecision(),
                'currency' => $currency?->getCode(),
                'gateway_id' => $gateway?->id,
                'detail_type' => $detailType?->value,
                'transgran' => $transgran,
                'order_id' => $orderId,
            ],
            'http' => [
                'request' => $httpRequest,
                'response' => $httpResponse,
            ],
        ];

        // Сбрасываем после использования, чтобы не утекают между вызовами
        $this->httpRequestMeta = [];
        $this->httpResponseMeta = [];

        return $detail;
    }

    protected function redactHttpMeta(?array $httpRequest): ?array
    {
        if ($httpRequest === null) {
            return null;
        }

        $headers = $httpRequest['headers'] ?? null;
        if (is_array($headers)) {
            foreach (['Access-Token', 'Authorization', 'authorization', 'access-token', 'Access-token', 'X-Api-Key', 'x-api-key'] as $key) {
                if (array_key_exists($key, $headers) && is_string($headers[$key]) && $headers[$key] !== '') {
                    $headers[$key] = '***REDACTED***';
                }
            }
            $httpRequest['headers'] = $headers;
        }

        return $httpRequest;
    }

    protected function truncateHttpResponse(?array $httpResponse): ?array
    {
        if ($httpResponse === null) {
            return null;
        }

        $body = $httpResponse['body'] ?? null;
        if (is_string($body) && strlen($body) > 4000) {
            $httpResponse['body'] = substr($body, 0, 4000) . '...[TRUNCATED]';
        }

        return $httpResponse;
    }

    /**
     * Удобные методы для логирования
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Выполнить операцию с повторными попытками
     */
    protected function withRetry(callable $callback, ?int $attempts = null, ?int $delay = null)
    {
        $attempts = $attempts ?? $this->config['number_of_retries'];
        $delay = $delay ?? $this->config['retry_delay'];
        
        $lastException = null;
        
        for ($i = 0; $i < $attempts; $i++) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $lastException = $e;
                $this->log('warning', "Попытка " . ($i + 1) . " из {$attempts} не удалась: " . $e->getMessage());
                
                if ($i < $attempts - 1) {
                    usleep($delay * 1000); // convert to microseconds
                }
            }
        }
        
        throw $lastException;
    }

    /**
     * Валидация входных параметров
     */
    protected function validateParameters(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null
    ): bool {
        // Базовые проверки - сумма должна быть больше нуля
        if ($amount->lessOrEquals(Money::zero($amount->getCurrency()->getCode()))) {
            return false;
        }

        return true;
    }

    public function supports(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?bool $transgran = null,
        ?Merchant $merchant = null
    ): bool {
        if (!$this->isAvailable()) {
            return false;
        }

        if (!$this->validateParameters($amount, $currency, $gateway, $detailType)) {
            return false;
        }

        if (isset($this->config['provider_terminal_id'])) {
            return $this->validateExternalProviderRequirements($amount, $currency, $gateway, $detailType, $merchant);
        }

        return $this->validateConfigBasedRequirements($amount, $currency, $gateway, $detailType);
    }

    protected function validateExternalProviderRequirements(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?Merchant $merchant = null
    ): bool {
        $amountValue = $this->moneyToFloat($amount);

        if (isset($this->config['min_sum']) && $this->config['min_sum'] !== null && $amountValue < (float)$this->config['min_sum']) {
            return false;
        }

        if (isset($this->config['max_sum']) && $this->config['max_sum'] !== null && $amountValue > (float)$this->config['max_sum']) {
            return false;
        }

        if ($currency && !empty($this->config['supported_currencies']) && !in_array(strtoupper($currency->getCode()), array_map('strtoupper', $this->config['supported_currencies']))) {
            return false;
        }

        if ($detailType && !empty($this->config['enabled_detail_types']) && !in_array($detailType->value, $this->config['enabled_detail_types'])) {
            return false;
        }

        if ($merchant && !empty($this->config['allowed_merchant_ids']) && !in_array($merchant->id, $this->config['allowed_merchant_ids'])) {
            return false;
        }

        return true;
    }

    protected function validateConfigBasedRequirements(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null
    ): bool {
        // Legacy/config-based providers keep their own rules; assume ok after validateParameters
        return true;
    }

    protected function moneyToFloat(Money $amount): float
    {
        return (float)$amount->toPrecision();
    }

    // protected getProviderId/getProviderTerminalId были заменены на public версии выше

    /**
     * Получить поддерживаемые валюты
     */
    abstract protected function getSupportedCurrencies(): array;

    /**
     * Получить поддерживаемые типы реквизитов
     */
    abstract protected function getSupportedDetailTypes(): array;

    /**
     * Получить поддерживаемые платежные шлюзы (ID)
     */
    abstract protected function getSupportedGateways(): array;

    /**
     * Очистка и безопасная обработка сообщения об ошибке
     */
    protected function sanitizeErrorMessage(?string $errorMessage): ?string
    {
        if ($errorMessage === null) {
            return null;
        }

        try {
            // Удаляем нулевые байты и другие контрольные символы
            $cleanedMessage = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $errorMessage);
            
            // Проверяем валидность UTF-8 и исправляем если нужно
            if (!mb_check_encoding($cleanedMessage, 'UTF-8')) {
                $cleanedMessage = mb_convert_encoding($cleanedMessage, 'UTF-8', 'UTF-8');
                // Если и после конвертации не валидный UTF-8, делаем fallback
                if (!mb_check_encoding($cleanedMessage, 'UTF-8')) {
                    $cleanedMessage = preg_replace('/[^\x20-\x7E]/', '?', $cleanedMessage);
                }
            }
            
            // Экранируем потенциально опасные символы для безопасности
            $cleanedMessage = htmlspecialchars($cleanedMessage, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
            
            // Ограничиваем длину (TEXT поле в MySQL = 65,535 символов, оставляем запас)
            $maxLength = 65000;
            if (strlen($cleanedMessage) > $maxLength) {
                // Умное сокращение - пытаемся оставить начало и конец сообщения
                $truncateLength = $maxLength - 50; // Оставляем место для маркера
                $halfLength = (int)($truncateLength / 2);
                
                $beginning = substr($cleanedMessage, 0, $halfLength);
                $ending = substr($cleanedMessage, -$halfLength);
                $cleanedMessage = $beginning . ' ... [TRUNCATED ' . (strlen($cleanedMessage) - $truncateLength) . ' chars] ... ' . $ending;
            }
            
            return $cleanedMessage;
            
        } catch (\Exception $e) {
            // Если не удалось обработать - возвращаем максимально безопасную заглушку
            $safeErrorInfo = [
                'error' => 'Error message processing failed',
                'original_length' => strlen($errorMessage ?? ''),
                'encoding_issue' => !mb_check_encoding($errorMessage ?? '', 'UTF-8'),
                'preview' => substr(preg_replace('/[^\x20-\x7E]/', '?', $errorMessage ?? ''), 0, 100),
                'processing_error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s'),
            ];
            
            return json_encode($safeErrorInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
} 