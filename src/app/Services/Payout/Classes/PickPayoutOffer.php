<?php

namespace App\Services\Payout\Classes;

use App\Enums\DetailType;
use App\Models\PaymentGateway;
use App\Models\PayoutOffer;
use App\Models\User;
use App\Services\Money\Money;

class PickPayoutOffer
{
    public function pick(Money $amount, DetailType $detailType, PaymentGateway $paymentGateway, ?User $exceptTrader = null): ?PayoutOffer
    {
        $payoutOffers = PayoutOffer::query()
            ->when($exceptTrader, function ($query) use ($exceptTrader) {
                $query->whereNot('owner_id', $exceptTrader->id);
            })
            ->whereRelation('owner', 'is_payout_online', true)
            ->where('occupied', false)
            ->where('active', true)
            ->get();

        $payoutOffer = null;

        if ($payoutOffers->isNotEmpty()) {
            /**
             * @var PayoutOffer $payoutOffer
             */
            $payoutOffers = $payoutOffers
                ->filter(function (PayoutOffer $payoutOffer) use ($amount, $detailType, $paymentGateway) {
                    return $payoutOffer->currency->getCode() === $amount->getCurrency()->getCode()
                        && $payoutOffer->min_amount->lessOrEquals($amount)
                        && $payoutOffer->max_amount->greaterOrEquals($amount)
                        && $payoutOffer->payment_gateway_id === $paymentGateway->id
                        && $payoutOffer->detail_types->first()->equals($detailType);
                });

            if ($payoutOffers->isNotEmpty()) {
                $payoutOffer = $payoutOffers->random();

                PayoutOffer::query()
                    ->where('owner_id', $payoutOffer->owner_id)
                    ->update([
                        'occupied' => true,
                    ]);
            }
        }

        return $payoutOffer;
    }
}
