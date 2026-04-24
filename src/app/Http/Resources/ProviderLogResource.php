<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $httpResponse = is_array($this->response_data) ? ($this->response_data['http_response'] ?? null) : null;
        $providerResponseText = null;
        $providerResponseStatusCode = null;

        if (is_array($httpResponse)) {
            $providerResponseStatusCode = $httpResponse['status_code'] ?? null;
            $providerResponseText = $httpResponse['body'] ?? null;
        }

        return [
            'id' => $this->id,
            'provider' => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name ?? $this->provider_name,
            ],
            'provider_terminal' => $this->when($this->providerTerminal, function () {
                return [
                    'id' => $this->providerTerminal->id,
                    'name' => $this->providerTerminal->name,
                ];
            }),
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
            'request_type' => $this->request_type,
            'request_data' => $this->request_params,
            'response_data' => $this->response_data,
            'provider_response_status_code' => $providerResponseStatusCode,
            'provider_response_text' => $providerResponseText,
            'status' => $this->success ? 'success' : 'fail',
            'is_success' => $this->success,
            'error_message' => $this->error_message,
            'response_time_ms' => $this->response_time_ms,
            'retry_attempt' => $this->retry_attempt,
            'detail_id' => $this->detail_id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
