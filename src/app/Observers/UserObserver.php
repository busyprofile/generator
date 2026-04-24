<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserMeta;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        UserMeta::create([
            'user_id' => $user->id,
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('banned_at') && $user->banned_at) {
            $user->updateQuietly([
                'is_online' => false,
                'is_payout_online' => false,
            ]);
        }
        if ($user->wasChanged('payouts_enabled') && ! $user->payouts_enabled) {
            $user->updateQuietly([
                'is_payout_online' => false,
            ]);
        }
        if ($user->wasChanged('stop_traffic') && $user->stop_traffic) {
            $user->updateQuietly([
                'is_online' => false,
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
