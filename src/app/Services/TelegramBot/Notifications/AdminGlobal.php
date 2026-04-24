<?php

namespace App\Services\TelegramBot\Notifications;

use App\Models\Order;
use App\Models\Telegram;

class AdminGlobal extends Notification
{
    public function __construct(
        protected Telegram $telegram,
        protected \App\Models\Notification $notification,
    )
    {}

    public function getMessage(): string
    {
        $message = $this->notification->message;

        return "Новое уведомление от администратора!\r\n"
            ."\r\n"
            ."$message";
    }

    public function afterSend(): void
    {
        $this->notification->increment('delivered_count');
    }

    protected function getTelegram(): Telegram
    {
        return $this->telegram;
    }
}
