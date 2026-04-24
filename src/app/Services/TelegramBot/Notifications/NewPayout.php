<?php

namespace App\Services\TelegramBot\Notifications;

use App\Models\Payout;
use App\Models\Telegram;

class NewPayout extends Notification
{
    public function __construct(
        protected Telegram $telegram,
        protected Payout $payout,
    )
    {}

    public function getMessage(): string
    {
        $id = $this->payout->id;
        $amount = $this->payout->payout_amount->toBeauty();
        $currency = strtoupper($this->payout->payout_amount->getCurrency()->getCode());

        $detail = $this->payout->detail;
        $detail_initials = $this->payout->detail_initials;

        $method = $this->payout->paymentGateway->name;

        if ($this->payout->subPaymentGateway) {
            $method .= ' ('.$this->payout->subPaymentGateway->name.')';
        }

        return "У вас новая выплата!\r\n"
            ."ID: $id\r\n"
            ."Сумма: $amount $currency\r\n"
            ."Реквизиты: $detail $detail_initials\r\n"
            ."Метод: $method";
    }

    protected function getTelegram(): Telegram
    {
        return $this->telegram;
    }
}
