<?php

namespace App\Jobs;

use App\Models\RequisiteProviderCallbackLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateRequisiteProviderCallbackLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 10;

    public function __construct(
        private string $requestId,
        private array $responseData,
        private int $statusCode,
        private bool $isSuccessful,
        private ?string $errorMessage = null,
        private ?int $orderId = null,
        private ?int $merchantId = null,
        private ?string $exceptionClass = null,
        private ?string $exceptionMessage = null,
        private ?float $executionTime = null
    ) {
        $this->afterCommit();
        $this->onQueue('logging');
    }

    public function handle(): void
    {
        $log = RequisiteProviderCallbackLog::where('request_id', $this->requestId)->first();

        if (!$log) {
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff()[$this->attempts() - 1]);
            }
            return;
        }

        $log->update([
            'response_data' => $this->responseData,
            'status_code' => $this->statusCode,
            'is_successful' => $this->isSuccessful,
            'error_message' => $this->errorMessage,
            'order_id' => $this->orderId,
            'merchant_id' => $this->merchantId,
            'exception_class' => $this->exceptionClass,
            'exception_message' => $this->exceptionMessage,
            'execution_time' => $this->executionTime,
        ]);
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }
}
