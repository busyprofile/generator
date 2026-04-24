<?php

namespace App\Services\Payout\Utils;

use App\Exceptions\PayoutException;
use App\Models\PaymentGateway;
use App\Models\PayoutOffer;
use App\Models\User;
use App\Services\Money\Money;

class OfferMaker
{
    public function add(User $user, array $data)
    {
        $exits = PayoutOffer::query()
            ->where('owner_id', $user->id)
            ->where('payment_gateway_id', $data['payment_gateway_id'])
            ->exists();

        if ($exits) {
            throw PayoutException::offerAlreadyExists();
        }

        $currency = PaymentGateway::find($data['payment_gateway_id'])->currency;
        return PayoutOffer::create([
            'min_amount' => Money::fromPrecision($data['min_amount'], $currency),
            'max_amount' => Money::fromPrecision($data['max_amount'], $currency),
            'currency' => $currency->getCode(),
            'detail_types' => $data['detail_types'],
            'active' => $data['active'],
            'payment_gateway_id' => $data['payment_gateway_id'],
            'owner_id' => $user->id,
        ]);
    }

    public function update(PayoutOffer $payoutOffer, array $data)
    {
        $payoutOffer->update([
            'min_amount' => Money::fromPrecision($data['min_amount'], $payoutOffer->currency),
            'max_amount' => Money::fromPrecision($data['max_amount'], $payoutOffer->currency),
            'detail_types' => $data['detail_types'],
            'active' => $data['active'],
        ]);

        return $payoutOffer;
    }
}
