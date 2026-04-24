<?php

namespace App\Services\RequisiteProviders\Callbacks;

use App\Enums\DisputeStatus;
use App\Enums\OrderSubStatus;
use App\Enums\ProviderIntegrationEnum;
use App\Models\Order;
use App\Models\ProviderTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MethodPayCallbackHandler extends AbstractProviderCallbackHandler
{
    public function integration(): ProviderIntegrationEnum
    {
        return ProviderIntegrationEnum::METHODPAY;
    }

    public function handle(Request $request, ProviderTerminal $terminal): JsonResponse
    {
        try {
            $validated = Validator::make($request->all(), [
                'order_id' => ['required', 'string'],
                'external_id' => ['required', 'string'],
                'merchant_id' => ['required', 'string'],
                'amount' => ['required', 'string'],
                'status' => ['required', 'string', 'in:success,pending,fail'],
                'sub_status' => ['required', 'string'],
                'currency' => ['nullable', 'string'],
                'payment_gateway' => ['nullable', 'string'],
                'payment_detail' => ['nullable', 'array'],
                'finished_at' => ['nullable', 'integer'],
                'expires_at' => ['nullable', 'integer'],
                'created_at' => ['nullable', 'integer'],
            ])->validate();

            // Ищем по uuid (external_id в callback содержит uuid ордера)
            $order = Order::where('uuid', $validated['external_id'])->first();

            if (!$order) {
                Log::warning('[ProviderCallback:MethodPay] Order not found by uuid', [
                    'external_id' => $validated['external_id'],
                    'order_id' => $validated['order_id'],
                    'terminal_uuid' => $terminal->uuid,
                ]);
                $this->logCallback($request, $terminal, null, 'Order not found', 404);
                return response()->json(['error' => 'Order not found'], 404);
            }

            $this->processMethodPayStatus($order, $validated);
            $this->logCallback($request, $terminal, $order->id, null, 200);

            return response()->json(['success' => true]);
        } catch (ValidationException $e) {
            $this->logCallback($request, $terminal, null, 'Validation failed', 422);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[ProviderCallback:MethodPay] Exception', [
                'terminal_uuid' => $terminal->uuid,
                'error' => $e->getMessage(),
            ]);
            $this->logCallback($request, $terminal, null, $e->getMessage(), 500);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function processMethodPayStatus(Order $order, array $data): void
    {
        $status = $data['status'];
        $subStatus = $data['sub_status'];

        switch ($status) {
            case 'success':
                if ($this->orderIsPending($order)) {
                    // Если диспут был принят — принимаем диспут (он сам закроет ордер)
                    if ($subStatus === 'successfully_paid_by_resolved_dispute' && $order->dispute?->status->equals(DisputeStatus::PENDING)) {
                        $this->acceptDispute($order);
                    } else {
                        $orderSubStatus = match ($subStatus) {
                            'accepted' => OrderSubStatus::ACCEPTED,
                            'successfully_paid_by_resolved_dispute' => OrderSubStatus::SUCCESSFULLY_PAID_BY_RESOLVED_DISPUTE,
                            default => OrderSubStatus::SUCCESSFULLY_PAID,
                        };
                        $this->finishOrderAsSuccessful($order, $orderSubStatus);
                    }
                }
                break;

            case 'fail':
                if ($this->orderIsPending($order)) {
                    // Если диспут был отклонён — отменяем диспут (он сам закроет ордер)
                    if ($subStatus === 'canceled_by_dispute' && $order->dispute?->status->equals(DisputeStatus::PENDING)) {
                        $this->cancelDispute($order);
                    } else {
                        $orderSubStatus = match ($subStatus) {
                            'canceled_by_dispute' => OrderSubStatus::CANCELED_BY_DISPUTE,
                            'cancelled' => OrderSubStatus::CANCELED,
                            default => OrderSubStatus::EXPIRED,
                        };
                        $this->finishOrderAsFailed($order, $orderSubStatus);
                    }
                }
                break;

            case 'pending':
                if ($subStatus === 'waiting_for_dispute_to_be_resolved') {
                    // Создаём диспут только если его нет или он был отменён
                    if (!$order->dispute || $order->dispute->status->equals(DisputeStatus::CANCELED)) {
                        $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_DISPUTE_TO_BE_RESOLVED]);
                        $this->createDispute($order);
                    }
                } else {
                    $orderSubStatus = match ($subStatus) {
                        'waiting_details_to_be_selected' => OrderSubStatus::WAITING_FOR_DETAILS_TO_BE_SELECTED,
                        default => OrderSubStatus::WAITING_FOR_PAYMENT,
                    };
                    $order->update(['sub_status' => $orderSubStatus]);
                }
                break;
        }
    }
}

