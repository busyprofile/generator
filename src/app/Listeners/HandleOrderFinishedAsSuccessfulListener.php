<?php

namespace App\Listeners;

use App\Enums\BalanceType;
use App\Enums\TransactionType;
use App\Events\OrderFinishedAsSuccessfulEvent;
use App\Models\OrderAdditionalProfit;
use App\Utils\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleOrderFinishedAsSuccessfulListener implements ShouldQueue
{
    public int $tries = 3;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderFinishedAsSuccessfulEvent $event): void
    {
        \Log::info('Обработка события OrderFinishedAsSuccessfulEvent', [
            'order_id' => $event->order->id,
            'order_uuid' => $event->order->uuid
        ]);
        
        Transaction::run(function () use ($event) {
            services()->wallet()->giveToBalance(
                $event->order->merchant->user->wallet->id,
                $event->order->merchant_profit,
                TransactionType::INCOME_FROM_A_SUCCESSFUL_ORDER,
                BalanceType::MERCHANT
            );
            
            // Транзакция для основного тимлидера (тимлидера #1)
            if ($event->order->team_leader_id) {
                $event->order->loadMissing('teamLeader.wallet');
                if ($event->order->teamLeader?->wallet) {
                    \Log::debug('Создание транзакции для тимлидера #1', [
                        'team_leader_id' => $event->order->team_leader_id,
                        'team_leader_profit' => $event->order->team_leader_profit->toUnits(),
                        'order_id' => $event->order->id,
                    ]);
                    
                    services()->wallet()->giveToBalance(
                        $event->order->teamLeader->wallet->id,
                        $event->order->team_leader_profit,
                        TransactionType::INCOME_FROM_REFERRALS_SUCCESSFUL_ORDER,
                        BalanceType::TEAMLEADER
                    );
                }
            }

            // Транзакции для остальных тимлидеров (#2 и далее)
            $additionalProfits = $event->order->additionalProfits()->with('teamLeader.wallet')->get();

            foreach ($additionalProfits as $index => $profitRecord) {
                if ($profitRecord->teamLeader?->wallet) {
                    \Log::debug('Создание транзакции для тимлидера #' . ($index + 2), [
                        'team_leader_id' => $profitRecord->team_leader_id,
                        'profit_amount' => $profitRecord->profit_amount->toUnits(),
                        'order_id' => $event->order->id,
                    ]);
                    
                    services()->wallet()->giveToBalance(
                        $profitRecord->teamLeader->wallet->id,
                        $profitRecord->profit_amount,
                        TransactionType::INCOME_FROM_REFERRALS_SUCCESSFUL_ORDER,
                        BalanceType::TEAMLEADER
                    );
                }
            }
        });
    }

    public function viaQueue(): string
    {
        return 'order';
    }

    public function backoff(): array
    {
        return [5, 10, 15];
    }
}
