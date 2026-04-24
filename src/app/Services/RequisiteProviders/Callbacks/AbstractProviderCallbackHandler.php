<?php

namespace App\Services\RequisiteProviders\Callbacks;

use App\Contracts\RequisiteProviderCallbackLogServiceContract;
use App\Enums\DisputeCancelReason;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Models\Order;
use App\Models\ProviderTerminal;
use App\Services\Order\Features\OrderOperator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class AbstractProviderCallbackHandler implements ProviderCallbackHandlerContract
{
    public function __construct(
        protected RequisiteProviderCallbackLogServiceContract $callbackLogService
    ) {}

    protected function finishOrderAsSuccessful(Order $order, OrderSubStatus $subStatus): void
    {
        try {
            $operator = new OrderOperator($order->id);
            $operator->finishOrderAsSuccessful($subStatus);

            Log::info('[ProviderCallback] Order marked as successful', [
                'order_id' => $order->id,
                'sub_status' => $subStatus->value,
                'integration' => $this->integration()->value,
            ]);
        } catch (\Exception $e) {
            Log::error('[ProviderCallback] Failed to mark order as successful', [
                'order_id' => $order->id,
                'integration' => $this->integration()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function finishOrderAsFailed(Order $order, OrderSubStatus $subStatus): void
    {
        try {
            $operator = new OrderOperator($order->id);
            $operator->finishOrderAsFailed($subStatus);

            Log::info('[ProviderCallback] Order marked as failed', [
                'order_id' => $order->id,
                'sub_status' => $subStatus->value,
                'integration' => $this->integration()->value,
            ]);
        } catch (\Exception $e) {
            Log::error('[ProviderCallback] Failed to mark order as failed', [
                'order_id' => $order->id,
                'integration' => $this->integration()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function createDispute(Order $order): void
    {
        try {
            if ($order->dispute) {
                Log::info('[ProviderCallback] Dispute already exists', [
                    'order_id' => $order->id,
                    'dispute_id' => $order->dispute->id,
                    'integration' => $this->integration()->value,
                ]);
                return;
            }

            services()->dispute()->create($order->id);
            Log::info('[ProviderCallback] Dispute created', [
                'order_id' => $order->id,
                'integration' => $this->integration()->value,
            ]);
        } catch (\Exception $e) {
            Log::error('[ProviderCallback] Failed to create dispute', [
                'order_id' => $order->id,
                'integration' => $this->integration()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function acceptDispute(Order $order): void
    {
        try {
            if (!$order->dispute) {
                Log::warning('[ProviderCallback] No dispute to accept', [
                    'order_id' => $order->id,
                    'integration' => $this->integration()->value,
                ]);
                return;
            }

            services()->dispute()->accept($order->dispute->id);
            Log::info('[ProviderCallback] Dispute accepted', [
                'order_id' => $order->id,
                'dispute_id' => $order->dispute->id,
                'integration' => $this->integration()->value,
            ]);
        } catch (\Exception $e) {
            Log::error('[ProviderCallback] Failed to accept dispute', [
                'order_id' => $order->id,
                'integration' => $this->integration()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function cancelDispute(Order $order): void
    {
        try {
            if (!$order->dispute) {
                Log::warning('[ProviderCallback] No dispute to cancel', [
                    'order_id' => $order->id,
                    'integration' => $this->integration()->value,
                ]);
                return;
            }

            services()->dispute()->cancel(
                $order->dispute->id,
                DisputeCancelReason::CANCELLED_BY_PROVIDER->value,
                'Отклонён по колбэку от провайдера'
            );
            Log::info('[ProviderCallback] Dispute cancelled', [
                'order_id' => $order->id,
                'dispute_id' => $order->dispute->id,
                'integration' => $this->integration()->value,
            ]);
        } catch (\Exception $e) {
            Log::error('[ProviderCallback] Failed to cancel dispute', [
                'order_id' => $order->id,
                'integration' => $this->integration()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function logCallback(
        Request $request,
        ProviderTerminal $terminal,
        ?int $orderId,
        ?string $error,
        int $statusCode
    ): void {
        try {
            $this->callbackLogService->log(
                providerName: 'provider_terminal:' . $terminal->uuid,
                orderId: $orderId,
                requestData: $request->all(),
                responseData: $error ? ['error' => $error] : ['success' => true],
                statusCode: $statusCode,
                providerTerminalId: $terminal->id,
            );
        } catch (\Exception $e) {
            Log::warning('[ProviderCallback] Failed to log callback', [
                'integration' => $this->integration()->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function orderIsPending(Order $order): bool
    {
        return $order->status->equals(OrderStatus::PENDING);
    }
}

