<?php

namespace App\Services\TelegramBot\Notifications;

use App\Models\Order;
use App\Models\Telegram;

class NewOrder extends Notification
{
    public function __construct(
        protected Telegram $telegram,
        protected Order $order,
    )
    {}

    public function getMessage(): string
    {
        $id = $this->order->id;
        $amount = $this->order->amount->toBeauty();
        $currency = strtoupper($this->order->amount->getCurrency()->getCode());

        $detail = $this->order->paymentDetail?->detail ?? 'Неизвестные реквизиты';
        $detail_name = $this->order->paymentDetail?->name ?? '';

        $method = $this->order->paymentGateway?->name_with_currency ?? 'Неизвестный метод';

        return "У вас новая сделка!\r\n"
            ."ID: $id\r\n"
            ."Сумма: $amount $currency\r\n"
            ."Реквизиты: $detail $detail_name\r\n"
            ."Метод: $method";
    }

    protected function getTelegram(): Telegram
    {
        return $this->telegram;
    }
}
