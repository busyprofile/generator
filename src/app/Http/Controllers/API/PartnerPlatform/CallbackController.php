<?php

namespace App\Http\Controllers\API\PartnerPlatform;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderCallback\CallbackService;
use App\Contracts\OrderServiceContract;
use App\Contracts\RequisiteProviderCallbackLogServiceContract;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function __construct(
        private readonly CallbackService $callbackService,
        private readonly OrderServiceContract $orderService,
        private readonly RequisiteProviderCallbackLogServiceContract $callbackLogService
    ) {}

    /**
     * Обработка callback от партнерской платформы
     */
    public function handle(Request $request): JsonResponse
    {
        $data = $request->all();
        $requestId = $this->callbackLogService->logRequest($request, 'partner_platform', $data);

        try {
            Log::info('[PartnerPlatformCallback] Получен callback от партнерской платформы', [
                'data' => $data,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Валидируем обязательные поля
            if (!isset($data['external_id']) || !isset($data['status'])) {
                Log::warning('[PartnerPlatformCallback] Отсутствуют обязательные поля', [
                    'received_data' => $data
                ]);

                $response = response()->json([
                    'success' => false,
                    'error' => 'Missing required fields: external_id, status'
                ], 400);
                $this->callbackLogService->updateWithResponse($requestId, $response);
                return $response;
            }

            // Ищем ордер по external_id
            $order = Order::withoutGlobalScopes()->where('external_id', $data['external_id'])->first();

            if (!$order) {
                Log::warning('[PartnerPlatformCallback] Ордер не найден', [
                    'external_id' => $data['external_id']
                ]);

                $response = response()->json([
                    'success' => false,
                    'error' => 'Order not found'
                ], 404);
                $this->callbackLogService->updateWithResponse($requestId, $response);
                return $response;
            }

            Log::info('[PartnerPlatformCallback] Ордер найден', [
                'order_id' => $order->id,
                'external_id' => $order->external_id,
                'current_status' => $order->status->value,
                'new_status' => $data['status']
            ]);

            // Маппинг статусов партнерской платформы в наши статусы
            $statusMapping = $this->getStatusMapping();
            $mappedStatus = $statusMapping[$data['status']] ?? null;

            if (!$mappedStatus) {
                Log::warning('[PartnerPlatformCallback] Неизвестный статус от партнера', [
                    'partner_status' => $data['status'],
                    'available_statuses' => array_keys($statusMapping)
                ]);

                $response = response()->json([
                    'success' => false,
                    'error' => 'Unknown status from partner platform'
                ], 400);
                $this->callbackLogService->updateWithResponse($requestId, $response, $order->id, $order->merchant?->id);
                return $response;
            }

            // Обновляем статус ордера если нужно
            if ($order->status->value !== $mappedStatus) {
                $oldStatus = $order->status->value;

                // Используем OrderService для правильного изменения статуса
                if ($mappedStatus === OrderStatus::SUCCESS->value) {
                    $this->orderService->finishOrderAsSuccessful($order->id, OrderSubStatus::ACCEPTED);
                } elseif ($mappedStatus === OrderStatus::FAIL->value) {
                    $this->orderService->finishOrderAsFailed($order->id, OrderSubStatus::CANCELED);
                } else {
                    // Для pending статуса просто обновляем напрямую
                    $order->update(['status' => $mappedStatus]);
                }

                // Обновляем объект ордера после изменения статуса
                $order->refresh();

                Log::info('[PartnerPlatformCallback] Статус ордера обновлен', [
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $order->status->value,
                    'sub_status' => $order->sub_status->value ?? 'none'
                ]);

                // Если есть мерчант callback URL, отправляем уведомление
                if ($order->merchant && $order->merchant->callback_url) {
                    $this->callbackService->sendCallback($order);

                    Log::info('[PartnerPlatformCallback] Отправлен callback мерчанту', [
                        'order_id' => $order->id,
                        'merchant_callback_url' => $order->merchant->callback_url
                    ]);
                }
            } else {
                Log::info('[PartnerPlatformCallback] Статус не изменился, пропускаем обновление', [
                    'order_id' => $order->id,
                    'status' => $mappedStatus
                ]);
            }

            $response = response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
                'order_id' => $order->id,
                'status' => $order->status->value
            ]);
            $this->callbackLogService->updateWithResponse($requestId, $response, $order->id, $order->merchant?->id);
            return $response;

        } catch (\Exception $e) {
            Log::error('[PartnerPlatformCallback] Ошибка при обработке callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            $response = response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
            $this->callbackLogService->updateWithResponse($requestId, $response, null, null, $e::class, $e->getMessage());
            return $response;
        }
    }

    /**
     * Маппинг статусов партнерской платформы в наши статусы
     */
    private function getStatusMapping(): array
    {
        return [
            'pending' => 'pending',           // В ожидании
            'paid' => 'success',              // Оплачен -> SUCCESS
            'success' => 'success',           // Успешно оплачен (от партнера) -> SUCCESS
            'failed' => 'fail',               // Неудачен -> FAIL
            'fail' => 'fail',                 // Неудачен (альтернативный формат) -> FAIL
            'cancelled' => 'fail',            // Отменен -> FAIL
            'expired' => 'fail',              // Истек -> FAIL
            'processing' => 'pending',        // В обработке -> PENDING
            'confirmed' => 'success',         // Подтвержден -> SUCCESS
            'dispute' => 'pending',           // Спор -> PENDING (остается в ожидании)
        ];
    }

    /**
     * Проверка статуса доступности callback endpoint
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Partner platform callback endpoint is working',
            'timestamp' => now()->toISOString(),
            'supported_statuses' => array_keys($this->getStatusMapping())
        ]);
    }
} 