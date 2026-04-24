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

/**
 * AlphaPay Callback Handler
 * 
 * Обрабатывает callback'и от AlphaPay (app.cash)
 * 
 * Статусы счетов (state):
 * 1 - CREATED: инвойс создан, реквизиты не выбраны
 * 2 - WAITING_FOR_PAYMENT: ожидание оплаты
 * 3 - WAITING_FOR_PAYMENT_CONFIRMATION: ожидание подтверждения трейдером
 * 4 - PAID: успешно завершен
 * 5 - TIMEOUT: время истекло
 * 6 - CANCELLED: отменен
 * 7 - INCORRECT_AMOUNT: изменена сумма
 * 8 - RESTORED: восстановлен
 * 9 - REVERTED: откат из PAID
 * 
 * Статусы апелляций (appeal_state):
 * 1 - NOT_SET: апелляция не создана
 * 2 - APPEALED: на рассмотрении
 * 3 - User success: решена в пользу клиента
 * 4 - Trader success: решена в пользу трейдера
 */
class AlphaPayCallbackHandler extends AbstractProviderCallbackHandler
{
    /**
     * Статусы счетов AlphaPay
     */
    private const STATE_CREATED = 1;
    private const STATE_WAITING_FOR_PAYMENT = 2;
    private const STATE_WAITING_FOR_CONFIRMATION = 3;
    private const STATE_PAID = 4;
    private const STATE_TIMEOUT = 5;
    private const STATE_CANCELLED = 6;
    private const STATE_INCORRECT_AMOUNT = 7;
    private const STATE_RESTORED = 8;
    private const STATE_REVERTED = 9;

    /**
     * Статусы апелляций AlphaPay
     */
    private const APPEAL_NOT_SET = 1;
    private const APPEAL_APPEALED = 2;
    private const APPEAL_USER_SUCCESS = 3;
    private const APPEAL_TRADER_SUCCESS = 4;

    public function integration(): ProviderIntegrationEnum
    {
        return ProviderIntegrationEnum::ALPHAPAY;
    }

    public function handle(Request $request, ProviderTerminal $terminal): JsonResponse
    {
        try {
            // Валидация входящих данных
            $validated = Validator::make($request->all(), [
                'invoice_id' => ['required', 'string'],
                'state' => ['required', 'integer'],
                'amount' => ['required'],
                'operation_id' => ['nullable', 'string'],
                'pair' => ['nullable', 'string'],
                'price' => ['nullable'],
                'new_amount' => ['nullable'],
                'appeal_state' => ['nullable', 'integer'],
                'appeal_reason' => ['nullable', 'integer'],
                'redirect_url' => ['nullable', 'string'],
            ])->validate();

            Log::info('[ProviderCallback:AlphaPay] Processing callback', [
                'terminal_uuid' => $terminal->uuid,
                'invoice_id' => $validated['invoice_id'],
                'state' => $validated['state'],
                'operation_id' => $validated['operation_id'] ?? null,
                'appeal_state' => $validated['appeal_state'] ?? null,
            ]);

            // Ищем заказ по uuid (operation_id содержит uuid)
            $order = Order::where('uuid', $validated['operation_id'])->first();

            if (!$order) {
                // Fallback: пробуем найти по provider_order_id (invoice_id)
                $order = Order::where('provider_order_id', $validated['invoice_id'])->first();
            }

            if (!$order) {
                Log::warning('[ProviderCallback:AlphaPay] Order not found by uuid or provider_order_id', [
                    'operation_id' => $validated['operation_id'] ?? null,
                    'invoice_id' => $validated['invoice_id'],
                    'terminal_uuid' => $terminal->uuid,
                ]);
                $this->logCallback($request, $terminal, null, 'Order not found', 404);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Обрабатываем статус
            $this->processAlphaPayStatus($order, $validated);
            
            // Логируем успешный callback
            $this->logCallback($request, $terminal, $order->id, null, 200);

            return response()->json(['success' => true]);

        } catch (ValidationException $e) {
            Log::warning('[ProviderCallback:AlphaPay] Validation failed', [
                'terminal_uuid' => $terminal->uuid,
                'errors' => $e->errors(),
            ]);
            $this->logCallback($request, $terminal, null, 'Validation failed', 422);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);

        } catch (\Exception $e) {
            Log::error('[ProviderCallback:AlphaPay] Exception', [
                'terminal_uuid' => $terminal->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->logCallback($request, $terminal, null, $e->getMessage(), 500);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Обработка статуса от AlphaPay
     */
    protected function processAlphaPayStatus(Order $order, array $data): void
    {
        $state = (int) $data['state'];
        $appealState = (int) ($data['appeal_state'] ?? self::APPEAL_NOT_SET);

        Log::info('[ProviderCallback:AlphaPay] Processing status', [
            'order_id' => $order->id,
            'state' => $state,
            'appeal_state' => $appealState,
            'current_order_status' => $order->status->value,
        ]);

        // Сначала проверяем апелляции
        if ($appealState === self::APPEAL_APPEALED) {
            // Апелляция на рассмотрении - создаём диспут
            $this->handleAppealOpened($order);
            return;
        }

        if ($appealState === self::APPEAL_USER_SUCCESS) {
            // Апелляция решена в пользу клиента - принимаем диспут
            $this->handleAppealUserSuccess($order);
            return;
        }

        if ($appealState === self::APPEAL_TRADER_SUCCESS) {
            // Апелляция решена в пользу трейдера - отклоняем диспут
            $this->handleAppealTraderSuccess($order);
            return;
        }

        // Обрабатываем основной статус
        switch ($state) {
            case self::STATE_CREATED:
                // Создан, реквизиты не выданы — не меняем sub_status,
                // так как заказ ещё в процессе назначения реквизитов
                Log::info('[ProviderCallback:AlphaPay] Invoice created, waiting for requisites', [
                    'order_id' => $order->id,
                ]);
                break;

            case self::STATE_WAITING_FOR_PAYMENT:
                // Ожидает оплаты
                $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_PAYMENT]);
                break;

            case self::STATE_WAITING_FOR_CONFIRMATION:
                // Ожидает подтверждения трейдером — это всё ещё ожидание оплаты с нашей стороны
                $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_PAYMENT]);
                break;

            case self::STATE_PAID:
                // Успешно оплачен
                $this->handlePaid($order);
                break;

            case self::STATE_TIMEOUT:
                // Время истекло
                $this->handleTimeout($order);
                break;

            case self::STATE_CANCELLED:
                // Отменён
                $this->handleCancelled($order);
                break;

            case self::STATE_INCORRECT_AMOUNT:
                // Изменена сумма - обновляем если есть new_amount
                $this->handleIncorrectAmount($order, $data);
                break;

            case self::STATE_RESTORED:
                // Восстановлен - переоткрываем заказ если он был закрыт
                $this->handleRestored($order);
                break;

            case self::STATE_REVERTED:
                // Откат из PAID - создаём возврат
                $this->handleReverted($order);
                break;

            default:
                Log::warning('[ProviderCallback:AlphaPay] Unknown state', [
                    'order_id' => $order->id,
                    'state' => $state,
                ]);
        }
    }

    /**
     * Обработка успешной оплаты
     */
    protected function handlePaid(Order $order): void
    {
        if (!$this->orderIsPending($order)) {
            Log::info('[ProviderCallback:AlphaPay] Order not pending, skipping paid handler', [
                'order_id' => $order->id,
                'status' => $order->status->value,
            ]);
            return;
        }

        // Если есть активный диспут - принимаем его
        if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
            $this->acceptDispute($order);
        } else {
            $this->finishOrderAsSuccessful($order, OrderSubStatus::SUCCESSFULLY_PAID);
        }
    }

    /**
     * Обработка таймаута
     */
    protected function handleTimeout(Order $order): void
    {
        if (!$this->orderIsPending($order)) {
            return;
        }

        // Если есть активный диспут - отклоняем его
        if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
            $this->cancelDispute($order);
        } else {
            $this->finishOrderAsFailed($order, OrderSubStatus::EXPIRED);
        }
    }

    /**
     * Обработка отмены
     */
    protected function handleCancelled(Order $order): void
    {
        if (!$this->orderIsPending($order)) {
            return;
        }

        // Если есть активный диспут - отклоняем его
        if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
            $this->cancelDispute($order);
        } else {
            $this->finishOrderAsFailed($order, OrderSubStatus::CANCELED);
        }
    }

    /**
     * Обработка изменения суммы
     */
    protected function handleIncorrectAmount(Order $order, array $data): void
    {
        $newAmount = $data['new_amount'] ?? null;
        
        if ($newAmount !== null) {
            Log::info('[ProviderCallback:AlphaPay] Amount changed', [
                'order_id' => $order->id,
                'original_amount' => $order->amount->toBeauty(),
                'new_amount' => $newAmount,
            ]);
            
            // Можно обновить сумму заказа если это необходимо
            // $order->update(['amount' => Money::fromPrecision($newAmount, $order->currency)]);
        }

        // Обновляем sub_status
        $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_PAYMENT]);
    }

    /**
     * Обработка восстановления
     */
    protected function handleRestored(Order $order): void
    {
        Log::info('[ProviderCallback:AlphaPay] Invoice restored', [
            'order_id' => $order->id,
        ]);

        // Если заказ был закрыт как неуспешный, можно его переоткрыть
        // Это зависит от бизнес-логики
        $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_PAYMENT]);
    }

    /**
     * Обработка отката (REVERTED)
     */
    protected function handleReverted(Order $order): void
    {
        Log::warning('[ProviderCallback:AlphaPay] Invoice reverted from PAID', [
            'order_id' => $order->id,
        ]);

        // Это серьёзная ситуация - откат успешного платежа
        // Нужно создать диспут или уведомить администратора
        if (!$order->dispute) {
            $this->createDispute($order);
        }
    }

    /**
     * Обработка открытия апелляции
     */
    protected function handleAppealOpened(Order $order): void
    {
        Log::info('[ProviderCallback:AlphaPay] Appeal opened', [
            'order_id' => $order->id,
        ]);

        $order->update(['sub_status' => OrderSubStatus::WAITING_FOR_DISPUTE_TO_BE_RESOLVED]);
        
        if (!$order->dispute || $order->dispute->status->equals(DisputeStatus::CANCELED)) {
            $this->createDispute($order);
        }
    }

    /**
     * Обработка решения апелляции в пользу клиента
     */
    protected function handleAppealUserSuccess(Order $order): void
    {
        Log::info('[ProviderCallback:AlphaPay] Appeal resolved in favor of user', [
            'order_id' => $order->id,
        ]);

        if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
            $this->acceptDispute($order);
        } elseif ($this->orderIsPending($order)) {
            $this->finishOrderAsSuccessful($order, OrderSubStatus::SUCCESSFULLY_PAID);
        }
    }

    /**
     * Обработка решения апелляции в пользу трейдера
     */
    protected function handleAppealTraderSuccess(Order $order): void
    {
        Log::info('[ProviderCallback:AlphaPay] Appeal resolved in favor of trader', [
            'order_id' => $order->id,
        ]);

        if ($order->dispute?->status->equals(DisputeStatus::PENDING)) {
            $this->cancelDispute($order);
        } elseif ($this->orderIsPending($order)) {
            $this->finishOrderAsFailed($order, OrderSubStatus::CANCELED);
        }
    }
}

