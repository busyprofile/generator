<?php

namespace App\Http\Resources;

use App\Models\PayoutGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutGatewayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var PayoutGateway $this
         */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'domain' => $this->domain,
            'owner' => [
                'id' => $this->owner->id,
                'email' => $this->owner->email,
                'name' => $this->owner->name,
            ],
            'callback_url' => $this->callback_url,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at->toDateTimeString(),
            'total_liquidity' => [
                'amount' => $this->total_liquidity_amount ? Money::fromUnits($this->total_liquidity_amount, Currency::USDT())->toBeauty() : 0,
                'currency' => Currency::USDT()->getCode(),
            ],
        ];
    }
}
