<?php

namespace App\Listeners;

use App\Events\NewWalletTransactionCreatedEvent;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\Wallet;
use App\Services\TelegramBot\Notifications\LowBalance;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleNewWalletTransactionCreatedListener implements ShouldQueue
{
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
    public function handle(NewWalletTransactionCreatedEvent $event): void
    {
        $transaction = $event->transaction;
        $transaction->load('wallet.user.telegram');

        if (Wallet::RESERVE_BALANCE / 10 > intval($transaction->wallet->trust_balance->toBeauty()) && $transaction->wallet->user->telegram) {
            SendTelegramNotificationJob::dispatch(
                new LowBalance(
                    telegram: $transaction->wallet->user->telegram,
                    wallet: $transaction->wallet
                )
            );
        }
    }
}
