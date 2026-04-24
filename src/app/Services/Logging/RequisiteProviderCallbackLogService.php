<?php

namespace App\Services\Logging;

use App\Contracts\RequisiteProviderCallbackLogServiceContract;
use App\Jobs\CreateRequisiteProviderCallbackLogJob;
use App\Jobs\UpdateRequisiteProviderCallbackLogJob;
use App\Models\Order;
use App\Models\RequisiteProviderCallbackLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequisiteProviderCallbackLogService implements RequisiteProviderCallbackLogServiceContract
{
    /**
     * Хранение времени начала обработки для каждого request_id
     *
     * @var array<string, float>
     */
    private array $requestStartTime = [];

    public function logRequest(Request $request, string $providerName, array $requestData): string
    {
        $requestId = (string) Str::uuid();

        $this->requestStartTime[$requestId] = microtime(true);

        CreateRequisiteProviderCallbackLogJob::dispatch(
            $requestId,
            $providerName,
            $requestData,
            $request->ip(),
            $request->userAgent()
        );

        return $requestId;
    }

    public function updateWithResponse(
        string $requestId,
        JsonResponse $response,
        ?int $orderId = null,
        ?int $merchantId = null,
        ?string $exceptionClass = null,
        ?string $exceptionMessage = null
    ): void {
        $responseData = json_decode($response->getContent(), true);
        $isSuccessful = $response->getStatusCode() === 200 && ($responseData['success'] ?? false) === true;
        $errorMessage = $isSuccessful ? null : ($responseData['error'] ?? $responseData['message'] ?? null);

        $executionTime = null;
        if (isset($this->requestStartTime[$requestId])) {
            $executionTime = (microtime(true) - $this->requestStartTime[$requestId]) * 1000;
            unset($this->requestStartTime[$requestId]);
        }

        UpdateRequisiteProviderCallbackLogJob::dispatch(
            $requestId,
            $responseData,
            $response->getStatusCode(),
            $isSuccessful,
            $errorMessage,
            $orderId,
            $merchantId,
            $exceptionClass,
            $exceptionMessage,
            $executionTime
        );
    }

    /**
     * Упрощённое логирование callback'а в один вызов (sync).
     *
     * Нужен для совместимости со старым кодом (ProviderCallbackController/handlers),
     * где логирование производится после обработки без пары logRequest/updateWithResponse.
     *
     * ВАЖНО: метод не в контракте, но его вызывают обработчики.
     * Ошибки логирования НЕ должны ломать обработку callback.
     */
    public function log(
        string $providerName,
        ?int $orderId,
        array $requestData,
        array $responseData,
        int $statusCode,
        ?int $providerTerminalId = null
    ): void {
        try {
            $merchantId = null;
            if ($orderId !== null) {
                $merchantId = Order::query()->whereKey($orderId)->value('merchant_id');
            }

            // Прячем служебные поля в request_data (таблица не имеет provider_terminal_id)
            if ($providerTerminalId !== null) {
                $requestData['_provider_terminal_id'] = $providerTerminalId;
            }

            $isSuccessful = $statusCode >= 200 && $statusCode < 300;
            $errorMessage = null;
            if (!$isSuccessful) {
                $errorMessage = $responseData['error']
                    ?? $responseData['message']
                    ?? $responseData['details']
                    ?? null;
                if (is_array($errorMessage)) {
                    $errorMessage = json_encode($errorMessage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            RequisiteProviderCallbackLog::create([
                'request_id' => (string) Str::uuid(),
                'provider_name' => $providerName,
                'merchant_id' => $merchantId,
                'order_id' => $orderId,
                'request_data' => $requestData,
                'response_data' => $responseData,
                'status_code' => $statusCode,
                'is_successful' => $isSuccessful,
                'error_message' => $errorMessage,
                'execution_time' => null,
            ]);
        } catch (\Throwable $e) {
            // Никогда не ломаем callback-обработку из-за проблем логирования
        }
    }
}
