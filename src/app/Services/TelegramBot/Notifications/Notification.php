<?php

namespace App\Services\TelegramBot\Notifications;

use App\Models\Telegram;

abstract class Notification
{
    abstract public function getMessage(): string;

    abstract protected function getTelegram(): Telegram;

    public function afterSend(): void
    {
        //
    }

    public function getChat(): string
    {
        return $this->telegram->telegram_id;
    }

    public function getMemberStatus(): string
    {
        return $this->telegram->member_status;
    }
}
