<?php

namespace App\Services\Payout\Classes;

use App\Models\PaymentGateway;

class GetExpirationTime
{
    public function __construct(
        protected PaymentGateway $paymentGateway
    )
    {}

    public function get()
    {
        return now()->addMinutes($this->paymentGateway->reservation_time_for_payouts);
    }
}
