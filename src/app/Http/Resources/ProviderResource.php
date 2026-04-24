<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'integration' => $this->integration?->value,
            'trader_id' => $this->trader_id,
            'is_active' => $this->is_active,
            'balance' => $this->when(isset($this->trusted_balance_cents), fn() => ((int) $this->trusted_balance_cents) / 100),
            'terminals_count' => $this->when(isset($this->provider_terminals_count), fn() => $this->provider_terminals_count),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
