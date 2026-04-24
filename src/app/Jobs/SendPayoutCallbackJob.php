<?php

namespace App\Jobs;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SendPayoutCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 8;
    public int $timeout = 30;

    public function __construct(
        private Payout $payout,
    )
    {
        $this->onQueue('callback');
        $this->afterCommit();
    }

    public function handle(): void
    {
        services()->callback()->sendForPayout($this->payout);
    }

    public function backoff(): array //8 попыток
    {
        return [10, 60, 120, 240, 480, 1800, 3600, 7200]; // Интервалы в секундах перед повторными попытками
    }
}
