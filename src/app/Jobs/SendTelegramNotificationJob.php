<?php

namespace App\Jobs;

use App\Services\TelegramBot\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Notification $notification,
    )
    {
        $this->onQueue('notification');
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        services()->telegramBot()->sendNotification($this->notification);
    }

    public function backoff(): array //3 попыток
    {
        return [30, 60, 180]; // Интервалы в секундах перед повторными попытками
    }
}
