<?php

namespace App\Services\TelegramBot\Notifications;

use App\Models\Dispute;
use App\Models\Telegram;

class PendingDispute extends Notification
{
    public function __construct(
        protected Telegram $telegram,
        protected Dispute $dispute,
        protected string $message,
    )
    {}

    public function getMessage(): string
    {
        $disputeID = $this->dispute->id;

        return "Внимание!\r\n"
            ."Спор: #$disputeID\r\n"
            ."$this->message\r\n";
    }

    protected function getTelegram(): Telegram
    {
        return $this->telegram;
    }
}
