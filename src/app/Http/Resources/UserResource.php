<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\Wallet;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var User $this
         */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_uuid' => $this->avatar_uuid,
            'avatar_style' => $this->avatar_style,
            'apk_latest_ping_at' => cache()->get("user-apk-latest-ping-at-$this->id"),
            'banned_at' => $this->banned_at?->toDateString(),
            'created_at' => $this->created_at->toDateString(),
            'promo_code_id' => $this->promo_code_id,
            'promo_used_at' => $this->promo_used_at?->toDateTimeString(),
            'promo_code' => $this->whenLoaded('promoCode', function () {
                return [
                    'id' => $this->promoCode->id,
                    'code' => $this->promoCode->code,
                    'team_leader' => [
                        'email' => $this->promoCode->teamLeader?->email,
                        'commission_percentage' => $this->promoCode->teamLeader?->referral_commission_percentage,
                    ]
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('roles'), function () {
                return [
                    'role' => RoleResource::make($this->roles[0])->resolve()
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('wallet'), function () {
                $amount = Money::fromPrecision(0, Currency::USDT());
                /**
                 * @var Wallet $wallet
                 */
                $wallet = $this->wallet;
                if ($this->hasRole('Merchant')) {
                    $amount = $wallet->merchant_balance;
                } else if ($this->hasRole('Trader')) {
                    $amount = $wallet->trust_balance;
                } else if ($this->hasRole('Team Leader')) {
                    $amount = $wallet->teamleader_balance;
                }

                return [
                    'balance' => $amount->toBeauty(),
                ];
            }),
            'payouts_enabled' => $this->payouts_enabled,
            'stop_traffic' => $this->stop_traffic,
            'traffic_enabled_at' => $this->traffic_enabled_at?->toDateTimeString(),
            'is_online' => $this->is_online,
            'is_payout_online' => $this->is_payout_online,
            'is_vip' => $this->is_vip,
            'referral_commission_percentage' => $this->referral_commission_percentage,
            'trader_commission_rate' => $this->trader_commission_rate,
            'can_be_impersonated' => $this->id !== auth()->user()?->id && $this->banned_at === null,
            'has_2fa' => (bool)$this->google2fa_secret,
            'additional_team_leader_ids' => $this->additional_team_leader_ids,
            'trader_category_id' => $this->trader_category_id,
            'trader_category' => $this->whenLoaded('traderCategory', function () {
                return $this->traderCategory ? [
                    'id' => $this->traderCategory->id,
                    'name' => $this->traderCategory->name,
                    'slug' => $this->traderCategory->slug,
                ] : null;
            }),
        ];
    }
}
