<?php

namespace App\Jobs;

use App\Models\RequisiteProviderLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogRequisiteProviderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    protected string $providerName;
    protected bool $success;
    protected int $merchantId;
    protected array $requestParams;
    protected ?string $errorMessage;
    protected float $responseTimeMs;
    protected ?array $responseData;
    protected ?int $orderId;
    protected ?string $detailId;
    protected ?int $providerId;
    protected ?int $providerTerminalId;

    public function __construct(
        string $providerName,
        bool $success,
        int $merchantId,
        array $requestParams,
        ?string $errorMessage,
        float $responseTimeMs,
        ?array $responseData = null,
        ?int $orderId = null,
        ?string $detailId = null,
        ?int $providerId = null,
        ?int $providerTerminalId = null
    ) {
        $this->providerName = $providerName;
        $this->success = $success;
        $this->merchantId = $merchantId;
        $this->requestParams = $requestParams;
        $this->errorMessage = $errorMessage;
        $this->responseTimeMs = $responseTimeMs;
        $this->responseData = $responseData;
        $this->orderId = $orderId;
        $this->detailId = $detailId;
        $this->providerId = $providerId;
        $this->providerTerminalId = $providerTerminalId;
    }

    public function handle(): void
    {
        try {
            Log::debug("[LogRequisiteProviderJob] Starting async logging", [
                'provider' => $this->providerName,
                'success' => $this->success,
                'merchant_id' => $this->merchantId,
                'error_message_length' => strlen($this->errorMessage ?? ''),
            ]);

            // Используем отдельное подключение для полной изоляции
            $connectionName = 'mysql';
            
            // Создаем и сохраняем лог запись вне любых транзакций
            $log = new RequisiteProviderLog();
            $log->setConnection($connectionName);

            $log->provider_name = $this->providerName;
            $log->merchant_id = $this->merchantId;
            $log->order_id = $this->orderId;
            $log->request_type = 'getRequisites';
            $log->request_params = $this->requestParams;
            $log->response_data = $this->responseData;
            $log->success = $this->success;
            $log->error_message = $this->errorMessage;
            $log->response_time_ms = (int)$this->responseTimeMs;
            $log->retry_attempt = 0;
            $log->detail_id = $this->detailId;
            $log->provider_id = $this->providerId;
            $log->provider_terminal_id = $this->providerTerminalId;

            // Сохраняем в отдельной транзакции для полной изоляции
            DB::connection($connectionName)->transaction(function () use ($log) {
                $log->saveOrFail();
            });

            Log::info("[LogRequisiteProviderJob] Successfully saved log record", [
                'provider' => $this->providerName,
                'success' => $this->success,
                'log_id' => $log->id,
                'merchant_id' => $this->merchantId,
            ]);

        } catch (\Exception $e) {
            Log::error("[LogRequisiteProviderJob] Failed to save log record", [
                'provider' => $this->providerName,
                'success' => $this->success,
                'merchant_id' => $this->merchantId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // При критической ошибке логирования failed запросов
            if (!$this->success) {
                Log::critical("[LogRequisiteProviderJob] CRITICAL: Failed request not logged", [
                    'provider' => $this->providerName,
                    'merchant_id' => $this->merchantId,
                    'error_message_preview' => substr($this->errorMessage ?? '', 0, 100),
                    'logging_error' => $e->getMessage(),
                    'this_breaks_statistics' => true,
                ]);
            }

            throw $e; // Re-throw для retry механизма
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("[LogRequisiteProviderJob] Job completely failed after all retries", [
            'provider' => $this->providerName,
            'success' => $this->success,
            'merchant_id' => $this->merchantId,
            'final_error' => $exception->getMessage(),
            'attempts_made' => $this->tries,
        ]);
    }
} 