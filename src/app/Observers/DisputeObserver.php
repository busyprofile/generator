<?php

namespace App\Observers;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\Dispute;
use App\Services\TelegramBot\Notifications\NewDispute;

class DisputeObserver
{
    /**
     * Handle the Dispute "created" event.
     */
    public function created(Dispute $dispute): void
    {
        if ($dispute->order->paymentDetail && 
            $dispute->order->paymentDetail->user && 
            $dispute->order->paymentDetail->user->telegram) {
            SendTelegramNotificationJob::dispatch(
                new NewDispute(
                    telegram: $dispute->order->paymentDetail->user->telegram,
                    dispute: $dispute
                )
            );
        }
    }

    /**
     * Handle the Dispute "updated" event.
     */
    public function updated(Dispute $dispute): void
    {
        //
    }

    /**
     * Handle the Dispute "deleted" event.
     */
    public function deleted(Dispute $dispute): void
    {
        //
    }

    /**
     * Handle the Dispute "restored" event.
     */
    public function restored(Dispute $dispute): void
    {
        //
    }

    /**
     * Handle the Dispute "force deleted" event.
     */
    public function forceDeleted(Dispute $dispute): void
    {
        //
    }
}
