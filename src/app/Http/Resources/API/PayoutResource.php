<?php

namespace App\Http\Resources\API;

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
            'uuid' => $this->uuid,
            'external_id' => $this->external_id,
            'detail' => $this->detail,
            'detail_type' => $this->detail_type->value,
            'detail_initials' => $this->detail_initials,
            'payout_amount' => $this->payout_amount->toBeauty(),
            'currency' => $this->currency->getCode(),
            'base_liquidity_amount' => $this->base_liquidity_amount->toBeauty(),
            'liquidity_amount' => $this->liquidity_amount->toBeauty(),
            'liquidity_currency' => $this->liquidity_amount->getCurrency()->getCode(),
            'service_commission_rate' => $this->service_commission_rate,
            'service_commission_amount' => $this->service_commission_amount->toBeauty(),
            'trader_profit_amount' => $this->trader_profit_amount->toBeauty(),
            'trader_exchange_markup_rate' => $this->trader_exchange_markup_rate,
            'trader_exchange_markup_amount' => $this->trader_exchange_markup_amount->toBeauty(),
            'base_exchange_price' => $this->base_exchange_price->toBeauty(),
            'exchange_price' => $this->exchange_price->toBeauty(),
            'status' => $this->status->value,
            'sub_status' => $this->sub_status->value,
            'callback_url' => $this->callback_url,
            'payment_gateway' => $this->paymentGateway->code,
            'payment_gateway_name' => $this->paymentGateway->name,
            'sub_payment_gateway' => $this->subPaymentGateway?->code,
            'sub_payment_gateway_name' => $this->subPaymentGateway?->name,
            'finished_at' => $this->finished_at?->getTimestamp(),
            'expires_at' => $this->expires_at->getTimestamp(),
            'created_at' => $this->created_at->getTimestamp(),
        ];
    }
}
