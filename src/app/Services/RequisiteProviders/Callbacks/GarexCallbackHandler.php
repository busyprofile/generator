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

class GarexCallbackHandler extends AbstractProviderCallbackHandler
{
    public function integration(): ProviderIntegrationEnum
    {
        return ProviderIntegrationEnum::GAREX;
    }

    public function handle(Request $request, ProviderTerminal $terminal): JsonResponse
    {
        try {
            $validated = Validator::make($request->all(), [
                'id' => ['required', 'string'],
                'state' => ['required', 'string'],
                'amount' => ['required', 'numeric'],
                'orderId' => ['required', 'string'],
                'rate' => ['nullable', 'numeric'],
                'address' => ['nullable', 'string'],
                'recipient' => ['nullable', 'string'],
                'bank' => ['nullable', 'string'],
                'bankName' => ['nullable', 'string'],
                'sign' => ['nullable', 'string'],
            ])->validate();

            // Ищем по uuid (orderId содержит uuid ордера)
            $order = Order::where('uuid', $validated['orderId'])->first();

            if (!$order) {
                Log::warning('[ProviderCallback:Garex] Order not found by uuid', [
                    'orderId' => $validated['orderId'],
                    'terminal_uuid' => $terminal->uuid,
                ]);
                $this->logCallback($request, $terminal, null, 'Order not found', 404);
                return response()->json(['error' => 'Order not found'], 404);
            }

            $this->processGarexStatus($order, $validated);
            $this->logCallback($request, $terminal, $order->id, null, 200);

            return response()->json(['success' => true]);
        } catch (ValidationException $e) {
            $this->logCallback($request, $terminal, null, 'Validation failed', 422);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('[ProviderCallback:Garex] Exception', [
                'terminal_uuid' => $terminal->uuid,
                'error' => $e->getMessage(),
            ]);
            $this->logCallback($request, $terminal, null, $e->getMessage(), 500);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function processGarexStatus(Order $order, array $data): void
    {
        $state = $data['state'];

        switch ($state) {
            case 'created':
                // Создан, реквизиты не выданы
                $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_DETAILS_TO_BE_SELECTED]);
                break;

            case 'pending':
                // Реквизиты выданы, ожидает оплаты
                $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_PAYMENT]);
                break;

            case 'paid':
            case 'finished':
                // Оплачен / успешно завершен
                if ($this->orderIsPending($order)) {
                    // Если был диспут — принимаем его (он сам закроет ордер)
                    if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
                        $this->acceptDispute($order);
                    } else {
                        $this->finishOrderAsSuccessful($order, OrderSubStatus::SUCCESSFULLY_PAID);
                    }
                }
                break;

            case 'canceled':
                // Отменен
                if ($this->orderIsPending($order)) {
                    // Если был диспут — отменяем его (он сам закроет ордер)
                    if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
                        $this->cancelDispute($order);
                    } else {
                        $this->finishOrderAsFailed($order, OrderSubStatus::CANCELED);
                    }
                }
                break;

            case 'dispute':
                // В споре — создаём диспут только если его нет или он был отменён
                if (!$order->dispute || $order->dispute->status->equals(DisputeStatus::CANCELED)) {
                    $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_DISPUTE_TO_BE_RESOLVED]);
                    $this->createDispute($order);
                }
                break;

            case 'failed':
                // Ошибка создания
                if ($this->orderIsPending($order)) {
                    $this->finishOrderAsFailed($order, OrderSubStatus::EXPIRED);
                }
                break;
        }
    }
}

