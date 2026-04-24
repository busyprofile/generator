<?php

namespace App\Http\Resources;

use App\Models\PayoutGateway;
use App\Models\PromoCode;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var PromoCode $this
         */
        return [
            'id' => $this->id,
            'code' => $this->code,
            'max_uses' => $this->max_uses,
            'used_count' => $this->used_count,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toDateTimeString(),
            'team_leader' => $this->whenLoaded('teamLeader', function () {
                return [
                    'id' => $this->teamLeader->id,
                    'name' => $this->teamLeader->name,
                    'email' => $this->teamLeader->email,
                ];
            }),
        ];
    }
}
