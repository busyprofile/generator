<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCallbackLogResource extends JsonResource
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
            'request_id' => $this->request_id,
            'provider_name' => $this->provider_name,
            'provider_real_name' => $this->provider_real_name,
            'provider_id' => $this->provider_id,
            'provider_terminal_id' => $this->provider_terminal_id,
            'provider_terminal_name' => $this->provider_terminal_name,
            'provider_terminal_uuid' => $this->provider_terminal_uuid,
            'merchant' => $this->when($this->merchant, function () {
                return [
                    'id' => $this->merchant->id,
                    'name' => $this->merchant->name,
                ];
            }),
            'order' => $this->when($this->order, function () {
                return [
                    'id' => $this->order->id,
                    'uuid' => $this->order->uuid,
                ];
            }),
            'request_data' => $this->request_data,
            'response_data' => $this->response_data,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'execution_time' => $this->execution_time,
            'status_code' => $this->status_code,
            'is_success' => $this->is_successful,
            'error_message' => $this->error_message,
            'exception_class' => $this->exception_class,
            'exception_message' => $this->exception_message,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
