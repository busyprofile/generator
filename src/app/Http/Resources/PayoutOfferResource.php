<?php

namespace App\Http\Resources;

use App\Enums\DetailType;
use App\Models\PayoutOffer;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutOfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var PayoutOffer $this
         */
        return [
            'id' => $this->id,
            'max_amount' => $this->max_amount->toBeauty(),
            'min_amount' => $this->min_amount->toBeauty(),
            'currency' => $this->currency->getCode(),
            'detail_types' => $this->detail_types->transform(function (DetailType $detailType) {
                return [
                    'name' => trans('detail-type.'.$detailType->value),
                    'code' => $detailType->value,
                ];
            })->toArray(),
            'owner' => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
                'is_payout_online' => $this->owner->is_payout_online,
            ],
            'active' => $this->active,
            'payment_gateway_id' => $this->payment_gateway_id,
            'payment_gateway_code' => $this->paymentGateway->code,
            'payment_gateway_name' => $this->paymentGateway->name_with_currency,
            'created_at' => $this->created_at->toDateTimeString(),
            'total_payout_amount' => $this->total_payout_amount ? Money::fromUnits($this->total_payout_amount, $this->currency)->toBeauty() : 0
        ];
    }
}
