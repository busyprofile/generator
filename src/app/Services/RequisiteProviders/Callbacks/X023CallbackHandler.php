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

/**
 * Обработчик колбэков от X023
 * 
 * Статусы X023:
 * - ACTIVE: ордер активен, ожидает оплаты
 * - CLOSED: ордер успешно завершён (оплачен)
 * - EXPIRED: время на оплату истекло
 * - APPEAL: открыт спор
 * - DECLINED: ордер отклонён
 * 
 * Авторизация: Bearer token в заголовке Authorization
 */
class X023CallbackHandler extends AbstractProviderCallbackHandler
{
    /**
     * Маппинг статусов X023 на внутренние статусы
     */
    private const STATUS_MAPPING = [
        'ACTIVE' => 'pending',
        'CLOSED' => 'success',
        'EXPIRED' => 'expired',
        'APPEAL' => 'dispute',
        'DECLINED' => 'declined',
    ];

    public function integration(): ProviderIntegrationEnum
    {
        return ProviderIntegrationEnum::X023;
    }

    public function handle(Request $request, ProviderTerminal $terminal): JsonResponse
    {
        try {
            $data = $request->all();

            Log::info('[ProviderCallback:X023] Received callback', [
                'terminal_uuid' => $terminal->uuid,
                'headers' => $request->headers->all(),
                'data' => $data,
            ]);

            // Валидация Authorization header
            $authValidation = $this->validateAuthorization($request, $terminal);
            if ($authValidation !== true) {
                $this->logCallback($request, $terminal, null, $authValidation, 401);
                return response()->json(['error' => $authValidation], 401);
            }

            // Получаем идентификатор ордера (outside_order_id - наш uuid)
            $orderIdentifier = $data['outside_order_id'] ?? $data['orderId'] ?? null;

            if (!$orderIdentifier) {
                Log::warning('[ProviderCallback:X023] Missing order identifier', [
                    'terminal_uuid' => $terminal->uuid,
                ]);
                $this->logCallback($request, $terminal, null, 'Missing order identifier', 400);
                return response()->json(['error' => 'Missing order identifier'], 400);
            }

            // Ищем ордер по uuid (outside_order_id содержит uuid)
            $order = Order::where('uuid', $orderIdentifier)->first();

            if (!$order) {
                Log::warning('[ProviderCallback:X023] Order not found by uuid', [
                    'order_identifier' => $orderIdentifier,
                    'terminal_uuid' => $terminal->uuid,
                ]);
                $this->logCallback($request, $terminal, null, 'Order not found', 404);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Валидация amount (опционально)
            $amount = $data['amount'] ?? null;
            if ($amount === null) {
                Log::warning('[ProviderCallback:X023] Missing amount in callback', [
                    'order_id' => $order->id,
                ]);
            }

            // Обрабатываем статус
            $this->processX023Status($order, $data);
            
            $this->logCallback($request, $terminal, $order->id, null, 200);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('[ProviderCallback:X023] Exception', [
                'terminal_uuid' => $terminal->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->logCallback($request, $terminal, null, $e->getMessage(), 500);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Валидация Authorization header
     */
    protected function validateAuthorization(Request $request, ProviderTerminal $terminal): bool|string
    {
        $settings = $terminal->additional_settings ?? [];
        $apiToken = $settings['api_token'] ?? null;

        if (!$apiToken) {
            Log::error('[ProviderCallback:X023] API token not configured for terminal', [
                'terminal_uuid' => $terminal->uuid,
            ]);
            return 'Provider credentials misconfiguration';
        }

        $expectedToken = 'Bearer ' . $apiToken;
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            Log::warning('[ProviderCallback:X023] Missing Authorization header', [
                'terminal_uuid' => $terminal->uuid,
            ]);
            return 'Authorization header missing';
        }

        if ($authHeader !== $expectedToken) {
            Log::warning('[ProviderCallback:X023] Invalid Authorization token', [
                'terminal_uuid' => $terminal->uuid,
                'expected' => substr($expectedToken, 0, 20) . '...',
                'got' => substr($authHeader, 0, 20) . '...',
            ]);
            return 'Invalid authorization token';
        }

        return true;
    }

    /**
     * Обработка статуса X023
     */
    protected function processX023Status(Order $order, array $data): void
    {
        $providerStatus = $data['status'] ?? $data['state'] ?? null;

        if (!$providerStatus) {
            Log::warning('[ProviderCallback:X023] Missing status field', [
                'order_id' => $order->id,
            ]);
            return;
        }

        // Приводим к верхнему регистру для маппинга
        $providerStatus = strtoupper($providerStatus);

        Log::info('[ProviderCallback:X023] Processing status', [
            'order_id' => $order->id,
            'provider_status' => $providerStatus,
            'current_order_status' => $order->status->value,
            'current_order_sub_status' => $order->sub_status->value,
        ]);

        switch ($providerStatus) {
            case 'ACTIVE':
                // Ордер активен, ожидает оплаты
                if ($this->orderIsPending($order)) {
                    if ($order->sub_status->notEquals(OrderSubStatus::WAITING_FOR_PAYMENT)) {
                        $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_PAYMENT]);
                    }
                }
                break;

            case 'CLOSED':
                // Ордер успешно завершён
                if ($this->orderIsPending($order)) {
                    // Если был открыт диспут - принимаем его
                    if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
                        $this->acceptDispute($order);
                    } else {
                        $this->finishOrderAsSuccessful($order, OrderSubStatus::SUCCESSFULLY_PAID);
                    }
                }
                break;

            case 'EXPIRED':
                // Время на оплату истекло
                if ($this->orderIsPending($order)) {
                    // Если был открыт диспут - отменяем его
                    if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
                        $this->cancelDispute($order);
                    } else {
                        $this->finishOrderAsFailed($order, OrderSubStatus::EXPIRED);
                    }
                }
                break;

            case 'APPEAL':
                // Открыт спор
                if ($this->orderIsPending($order)) {
                    // Создаём диспут только если его нет или он был отменён
                    if (!$order->dispute || $order->dispute->status->equals(DisputeStatus::CANCELED)) {
                        $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_DISPUTE_TO_BE_RESOLVED]);
                        $this->createDispute($order);
                    }
                }
                break;

            case 'DECLINED':
                // Ордер отклонён
                if ($this->orderIsPending($order)) {
                    // Если был открыт диспут - отменяем его
                    if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
                        $this->cancelDispute($order);
                    } else {
                        $this->finishOrderAsFailed($order, OrderSubStatus::CANCELED);
                    }
                }
                break;

            default:
                Log::warning('[ProviderCallback:X023] Unknown status received', [
                    'order_id' => $order->id,
                    'status' => $providerStatus,
                ]);
                break;
        }
    }
}

