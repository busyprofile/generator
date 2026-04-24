<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
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
            'promo_code' => $this->whenLoaded('promoCode', function () {
                return [
                    'id' => $this->promoCode->id,
                    'code' => $this->promoCode->code,
                ];
            }),
            'promo_used_at' => $this->promo_used_at?->toDateTimeString(),
            'orders_count' => $this->when(isset($this->orders_count), $this->orders_count),
            'total_profit' => $this->when(isset($this->total_team_leader_profit), fn() => $this->total_team_leader_profit->toBeauty()),
            'created_at' => $this->created_at?->format('d.m.Y H:i'),
            
            // Добавляем новые поля для отображения комиссии
            'commission_percentage' => $this->when(isset($this->commission_percentage), $this->commission_percentage),
            'is_primary_teamleader' => $this->when(isset($this->is_primary_teamleader), $this->is_primary_teamleader),
            'is_merchant' => $this->when(isset($this->is_merchant), $this->is_merchant),
            'relation_created_at' => $this->relation_created_at?->format('d.m.Y H:i'),
        ];
    }
}
