<?php

namespace App\Jobs;

use App\Contracts\OrderServiceContract;
use App\DTO\Order\CreateOrderDTO;
use App\Exceptions\OrderException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;
use Illuminate\Support\Facades\Log;

class OrderPoolingJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 5;
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $jobID,
        public int $createdAt,
        public array $payload,
        public int $maxWaitMs,
    )
    {
        $this->afterCommit();
        $this->onQueue('order-pooling');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[OrderPoolingJob] Job started.', ['jobID' => $this->jobID, 'payload_keys' => array_keys($this->payload)]);
        try {
            $jobCache = cache()->get("order:create:$this->jobID");

            if (!$jobCache) {
                Log::warning('[OrderPoolingJob] No cache entry found for job. Exiting.', ['jobID' => $this->jobID]);
                return;
            }

            $jobCacheData = json_decode($jobCache, true);

            if (empty($jobCacheData['status']) || $jobCacheData['status'] !== 'queued') {
                Log::warning('[OrderPoolingJob] Job status is not queued or empty. Exiting.', ['jobID' => $this->jobID, 'status' => $jobCacheData['status'] ?? 'unknown']);
                return;
            }

            if (now()->getTimestampMs() - $this->createdAt > $this->maxWaitMs) {
                Log::warning('[OrderPoolingJob] Max wait time exceeded. Marking as expired.', ['jobID' => $this->jobID, 'createdAt' => $this->createdAt, 'maxWaitMs' => $this->maxWaitMs]);
                cache()->put("order:create:$this->jobID", json_encode([
                    'status' => 'expired',
                ]), 60);
                return;
            }

            Log::info('[OrderPoolingJob] Setting status to processing.', ['jobID' => $this->jobID]);
            cache()->put("order:create:$this->jobID", json_encode([
                'status' => 'processing',
            ]), 60);

            $merchantIdFromPayload = $this->payload['merchant_id'] ?? 'unknown';
            Log::info('[OrderPoolingJob] Finding merchant.', ['jobID' => $this->jobID, 'merchant_uuid_from_payload' => $merchantIdFromPayload]);
            $merchant = queries()->merchant()->findByUUID($this->payload['merchant_id']);
            if (!$merchant) {
                Log::error('[OrderPoolingJob] Merchant not found by UUID from payload. Cannot proceed.', ['jobID' => $this->jobID, 'merchant_uuid' => $merchantIdFromPayload]);
                throw new OrderException('Merchant not found for H2H order creation.');
            }
            Log::info('[OrderPoolingJob] Merchant found.', ['jobID' => $this->jobID, 'merchant_id' => $merchant->id]);

            Log::error('[OrderPoolingJob] Preparing CreateOrderDTO.', ['jobID' => $this->jobID]);
            $dto = CreateOrderDTO::makeFromRequest($this->payload + ['merchant' => $merchant]);
            Log::error('[OrderPoolingJob] CreateOrderDTO prepared. Calling OrderService->create().', [
                'jobID' => $this->jobID,
                'merchant_id' => $merchant->id,
                'payload_gateway' => $this->payload['payment_gateway'] ?? null,
                'payload_detail_type' => $this->payload['payment_detail_type'] ?? null,
            ]);
            
            $order = make(OrderServiceContract::class)->create($dto);
            
            Log::info('[OrderPoolingJob] OrderService->create() finished. Order created.', ['jobID' => $this->jobID, 'order_id' => $order->id, 'order_uuid' => $order->uuid]);

            cache()->put("order:create:$this->jobID", json_encode([
                'status' => 'done',
                'order_id' => $order->id,
            ]), 60);
            Log::info('[OrderPoolingJob] Status set to done.', ['jobID' => $this->jobID]);

        } catch (OrderException | Throwable $e) {
            Log::error('[OrderPoolingJob] Exception caught.', [
                'jobID' => $this->jobID,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString()
            ]);
            cache()->put("order:create:$this->jobID", json_encode([
                'status' => 'failed',
                'exception' => [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                ],
            ]), 60);
        }
        Log::info('[OrderPoolingJob] Job finished.', ['jobID' => $this->jobID]);
    }
}
