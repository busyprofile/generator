<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderTerminalResource extends JsonResource
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
            'provider_id' => $this->provider_id,
            'provider' => $this->when($this->provider, [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
                'integration' => $this->provider?->integration?->value,
                'owner_email' => $this->provider?->trader?->email,
            ]),
            'provider_name' => $this->provider?->name,
            'provider_integration' => $this->provider?->integration?->value,
            'min_sum' => $this->min_sum,
            'max_sum' => $this->max_sum,
            'time_for_order' => $this->time_for_order,
            'rate' => $this->rate,
            'max_response_time_ms' => $this->max_response_time_ms,
            'number_of_retries' => $this->number_of_retries,
            'retry_delay_ms' => $this->retry_delay_ms,
            'is_active' => $this->is_active,
            'enabled_detail_types' => $this->enabled_detail_types ?? [],
            'additional_settings' => $this->additional_settings ?? [],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
