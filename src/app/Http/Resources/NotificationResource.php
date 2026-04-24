<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var Notification $this
         */
        return [
            'id' => $this->id,
            'message' => $this->message,
            'recipients_count' => $this->recipients_count,
            'delivered_count' => $this->delivered_count,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
