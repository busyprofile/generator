<?php

namespace App\Services\Dispute;

use App\Contracts\DisputeServiceContract;
use App\Enums\DisputeStatus;
use App\Enums\DisputeCancelReason;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Exceptions\DisputeException;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use App\Utils\Transaction;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DisputeService implements DisputeServiceContract
{
    /**
     * @throws DisputeException
     */
    public function create(int $orderID, ?UploadedFile $receipt = null): Dispute
    {
        return Transaction::run(function () use ($orderID, $receipt) {
            

            //$order = Order::where('id', $orderID)->with('dispute')->lockForUpdate()->first();
            $order = Order::where('id', $orderID)->with(['dispute', 'paymentDetail'])->lockForUpdate()->first();

            if ($order->dispute) {
                // Если диспут отменен, откатываем его к статусу PENDING
                if ($order->dispute->status->equals(DisputeStatus::CANCELED)) {
                    // Обрабатываем новый receipt если передан
                    if ($receipt) {
                        $receipt_name = 'receipt_'.strtolower(Str::random(32)).'.'.$receipt->extension();
                        $receipt->move(storage_path('receipts'), $receipt_name);
                        
                        // Удаляем старый файл receipt если он существует
                        if ($order->dispute->receipt) {
                            $old_receipt_path = storage_path('receipts/'.$order->dispute->receipt);
                            if (file_exists($old_receipt_path)) {
                                unlink($old_receipt_path);
                            }
                        }
                    } else {
                        $receipt_name = $order->dispute->receipt; // Оставляем старый receipt
                    }
                    
                    // Обновляем статус и данные диспута
                    $order->dispute->update([
                        'receipt' => $receipt_name,
                        'status' => DisputeStatus::PENDING,
                        'reason' => null,
                        'cancel_reason' => null,
                    ]);

                    // Переоткрываем заказ для рассмотрения диспута
                    services()->order()->reopenFinishedOrder($order->dispute->order_id, OrderSubStatus::WAITING_FOR_DISPUTE_TO_BE_RESOLVED);

                    $this->checkActiveDisputesLimit($order->dispute->trader_id);

                    return $order->dispute->fresh();
                }
                
                // Если диспут уже существует (PENDING или ACCEPTED), выбрасываем исключение
                throw new DisputeException('Dispute already exists.');
            }

            if ($order->status->equals(OrderStatus::PENDING)) {
                services()->order()->finishOrderAsFailed($order->id, OrderSubStatus::CANCELED);
                $order = Order::where('id', $orderID)->lockForUpdate()->first();
            }

            if ($order->status->equals(OrderStatus::SUCCESS) || $order->status->equals(OrderStatus::FAIL)) {
                services()->order()->reopenFinishedOrder($order->id, OrderSubStatus::WAITING_FOR_DISPUTE_TO_BE_RESOLVED);
            }

            if ($receipt) {
                $receipt_name = 'receipt_'.strtolower(Str::random(32)).'.'.$receipt->extension();
                $receipt->move(storage_path('receipts'), $receipt_name);
            } else {
                $receipt_name = null;
            }

            $traderId = null;
            $cascadePaymentDetailId = env('CASCADE_PAYMENT_DETAIL_ID', '1');

            if ($order->payment_detail_id == $cascadePaymentDetailId) {
                $traderId = $order->trader_id;
                Log::info('DisputeService: Выбран trader_id из заказа (провайдер каскада).', [
                    'order_id' => $order->id,
                    'payment_detail_id' => $order->payment_detail_id,
                    'cascade_payment_detail_id' => $cascadePaymentDetailId,
                    'selected_trader_id' => $traderId,
                ]);
            } else {
                $traderId = $order->paymentDetail->user_id;
                Log::info('DisputeService: Выбран user_id из платежных реквизитов (трейдер площадки).', [
                    'order_id' => $order->id,
                    'payment_detail_id' => $order->payment_detail_id,
                    'cascade_payment_detail_id' => $cascadePaymentDetailId,
                    'selected_trader_id' => $traderId,
                ]);
            }

            $dispute = Dispute::create([
                'receipt' => $receipt_name,
                'order_id' => $order->id,
                'trader_id' => $traderId,
                'status' => DisputeStatus::PENDING,
            ]);

            $this->checkActiveDisputesLimit($dispute->trader_id);

            return $dispute;
        });
    }

    public function accept(int $disputeID): bool
    {
        return Transaction::run(function () use ($disputeID) {
            $dispute = Dispute::where('id', $disputeID)->lockForUpdate()->first();

            if ($dispute->status->notEquals(DisputeStatus::PENDING)) {
                throw new DisputeException('Dispute must be pending.');
            }

            services()->order()->finishOrderAsSuccessful($dispute->order_id, OrderSubStatus::SUCCESSFULLY_PAID_BY_RESOLVED_DISPUTE);

            return $dispute->update([
                'status' => DisputeStatus::ACCEPTED
            ]);
        });
    }

    public function cancel(int $disputeID, string $cancelReason, ?string $comment = null): bool
    {
        return Transaction::run(function () use ($disputeID, $cancelReason, $comment) {
            $dispute = Dispute::where('id', $disputeID)->lockForUpdate()->first();

            if ($dispute->status->notEquals(DisputeStatus::PENDING)) {
                throw new DisputeException('Dispute must be pending.');
            }

            services()->order()->finishOrderAsFailed($dispute->order_id, OrderSubStatus::CANCELED_BY_DISPUTE);

            $updated = $dispute->update([
                'status' => DisputeStatus::CANCELED,
                'cancel_reason' => $cancelReason,
                'reason' => $comment,
            ]);

            // Проверяем количество отклоненных споров и отключаем трафик если нужно
            $this->checkRejectedDisputesLimit($dispute->trader_id);

            return $updated;
        });
    }

    /**
     * Проверяет количество отклоненных споров у трейдера за указанный период
     * При превышении лимита отключает трафик
     */
    protected function checkRejectedDisputesLimit(int $traderId): void
    {
        $trader = User::findOrFail($traderId);
        $maxRejectedDisputes = services()->settings()->getMaxRejectedDisputes();
        
        // Если лимит не установлен (count = 0), то не проверяем
        if ($maxRejectedDisputes['count'] <= 0 || $maxRejectedDisputes['period'] <= 0) {
            return;
        }
        
        $periodMinutes = $maxRejectedDisputes['period'];
        $maxCount = $maxRejectedDisputes['count'];
        
        // Определяем, с какой даты считаем споры
        // Используем более позднюю из двух дат: 
        // 1. Дата последнего включения трафика
        // 2. Текущее время минус период настройки
        $periodDate = Carbon::now()->subMinutes($periodMinutes);
        $sinceDate = $trader->traffic_enabled_at && $trader->traffic_enabled_at->isAfter($periodDate) 
            ? $trader->traffic_enabled_at 
            : $periodDate;
        
        // Считаем количество отклоненных споров за указанный период
        $count = Dispute::where('trader_id', $traderId)
            ->where('status', DisputeStatus::CANCELED)
            ->where('created_at', '>=', $sinceDate)
            ->count();
        
        // Если количество отклоненных споров превышает лимит, отключаем трафик
        if ($count >= $maxCount) {
            $trader->update([
                'stop_traffic' => true
            ]);
        }
    }

    /**
     * Проверяет количество активных споров у трейдера
     * При превышении лимита отключает трафик
     */
    protected function checkActiveDisputesLimit(int $traderId): void
    {
        $trader = User::findOrFail($traderId);
        $maxPendingDisputes = services()->settings()->getMaxPendingDisputes();

        // Если лимит не установлен (0 = бесконечно), то не проверяем
        if ($maxPendingDisputes <= 0) {
            return;
        }

        // Считаем количество активных споров
        $count = Dispute::where('trader_id', $traderId)
            ->where('status', DisputeStatus::PENDING)
            ->count();

        // Если количество активных споров превышает лимит, отключаем трафик
        if ($count >= $maxPendingDisputes) {
            $trader->update([
                'stop_traffic' => true
            ]);
        }
    }

    public function rollback(int $disputeID): bool
    {
        return Transaction::run(function () use ($disputeID) {
            $dispute = Dispute::where('id', $disputeID)->lockForUpdate()->first();

            if ($dispute->status->equals(DisputeStatus::PENDING)) {
                throw new DisputeException('Cannot rollback pending dispute.');
            }

            services()->order()->reopenFinishedOrder($dispute->order_id, OrderSubStatus::WAITING_FOR_DISPUTE_TO_BE_RESOLVED);

            $updated = $dispute->update([
                'status' => DisputeStatus::PENDING,
                'reason' => null,
                'cancel_reason' => null,
            ]);

            $this->checkActiveDisputesLimit($dispute->trader_id);

            return $updated;
        });
    }
}
