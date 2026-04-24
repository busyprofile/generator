<?php

namespace App\Http\Resources;

use App\Enums\FundsOnHoldStatus;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var Payout $this
         */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'external_id' => $this->external_id,
            'detail' => $this->detail,
            'detail_type' => [
                'name' => trans('detail-type.'.$this->detail_type->value),
                'code' => $this->detail_type->value,
            ],
            'detail_initials' => $this->detail_initials,
            'payout_amount' => $this->payout_amount->toBeauty(),
            'currency' => $this->currency->getCode(),
            'base_liquidity_amount' => $this->base_liquidity_amount->toBeauty(),
            'liquidity_amount' => $this->liquidity_amount->toBeauty(),
            'liquidity_amount_in_payout_currency' => $this->payout_amount->mul(1 + ($this->service_commission_rate/100))->toBeauty(),
            'liquidity_currency' => $this->liquidity_amount->getCurrency()->getCode(),
            'service_commission_rate' => $this->service_commission_rate,
            'service_commission_amount' => $this->service_commission_amount->toBeauty(),
            'trader_profit_amount' => $this->trader_profit_amount->toBeauty(),
            'trader_exchange_markup_rate' => $this->trader_exchange_markup_rate,
            'trader_exchange_markup_amount' => $this->trader_exchange_markup_amount->toBeauty(),
            $this->mergeWhen($this->resource->relationLoaded('trader'), function () {
                return [
                    'trader' => [
                        'name' => $this->trader?->name,
                        'email' => $this->trader?->email,
                    ]
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('previousTrader'), function () {
                return [
                    'previous_trader' => [
                        'name' => $this->previousTrader?->name,
                        'email' => $this->previousTrader?->email,
                    ]
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('owner'), function () {
                return [
                    'owner' => [
                        'name' => $this->owner->name,
                        'email' => $this->owner->email,
                    ]
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('liquidityHold'), function () {
                /**
                 * @var Payout $this
                 */
                return [
                    'funds_on_hold' => [
                        'is_on_hold' => $this->liquidityHold?->status->equals(FundsOnHoldStatus::PENDING_FOR_EXECUTION),
                        'hold_until' => $this->liquidityHold?->hold_until?->toDateTimeString(),
                    ]
                ];
            }),
            'previous_trader_id' => $this->previous_trader_id,
            'base_exchange_price' => $this->base_exchange_price->toBeauty(),
            'exchange_price' => $this->exchange_price->toBeauty(),
            'status' => $this->status->value,
            'status_name' => $this->status_name,
            'sub_status' => $this->sub_status->value,
            'callback_url' => $this->callback_url,
            'payment_gateway' => [
                'code' => $this->paymentGateway->code,
                'name' => $this->paymentGateway->name,
                'logo_path' => $this->paymentGateway->logo ? asset('storage/logos/'.$this->paymentGateway->logo) : null,
            ],
            'sub_payment_gateway' => $this->when($this->subPaymentGateway, function () {
                return [
                    'code' => $this->subPaymentGateway->code,
                    'name' => $this->subPaymentGateway->name,
                    'logo_path' => $this->subPaymentGateway->logo ? asset('storage/logos/'.$this->subPaymentGateway->logo) : null,
                ];
            }),
            'payout_gateway' => [
                'name' => $this->payoutGateway->name,
            ],
            'receipt_url' => $this->video_receipt ? route('admin.payouts.receipt', $this->id) : null,
            'refuse_reason' => $this->refuse_reason,
            'finished_at' => $this->finished_at?->toDateTimeString(),
            'expires_at' => $this->expires_at->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'now' => now()->toDateTimeString(),
        ];
    }
}
