<?php

namespace App\Services\TelegramBot\Notifications;

use App\Models\Dispute;
use App\Models\Telegram;

class NewDispute extends Notification
{
    public function __construct(
        protected Telegram $telegram,
        protected Dispute $dispute,
    )
    {}

    public function getMessage(): string
    {
        $order_id = $this->dispute->order->id;
        $amount = $this->dispute->order->amount->toBeauty();
        $currency = strtoupper($this->dispute->order->amount->getCurrency()->getCode());

        $detail = $this->dispute->order->paymentDetail?->detail ?? 'Партнерские реквизиты';
        $detail_name = $this->dispute->order->paymentDetail?->name ?? '';
        
        return "Открыт новый спор!\r\n"
            ."Сделка: #$order_id\r\n"
            ."Сумма: $amount $currency\r\n"
            ."Реквизиты: $detail $detail_name\r\n";
    }

    protected function getTelegram(): Telegram
    {
        return $this->telegram;
    }
}
