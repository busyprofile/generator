<?php

namespace App\Http\Resources;

use App\Models\PaymentDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraderDetailStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var PaymentDetail $this
         */
        return [
            'id' => $this->id,
            'name' => $this->name,
            'detail' => $this->detail,
            'detail_type' => $this->detail_type->value,
            'initials' => $this->initials,
            'is_active' => $this->is_active,
            'daily_limit' => $this->daily_limit->toBeauty(),
            'current_daily_limit' => $this->current_daily_limit->toBeauty(),
            'pending_orders_count' => $this->pending_orders_count,
            'max_pending_orders_quantity' => $this->max_pending_orders_quantity,
            'min_order_amount' => $this->min_order_amount?->toBeauty(),
            'max_order_amount' => $this->max_order_amount?->toBeauty(),
            'order_interval_minutes' => $this->order_interval_minutes,
            'currency' => $this->currency->getCode(),
            'user_device_id' => $this->user_device_id,
            'created_at' => $this->created_at->toDateString(),
            'monthly_turnover' => $this->monthly_turnover ?? 0,
            'monthly_orders_count' => $this->monthly_orders_count ?? 0,
            $this->mergeWhen($this->resource->relationLoaded('paymentGateways'), function () {
                /**
                 * @var PaymentDetail $this
                 */
                $paymentGateway = $this->paymentGateways->first();
                return [
                    'payment_gateway' => [
                        'name' => $paymentGateway->name,
                        'logo_path' => $paymentGateway?->logo ? asset('storage/logos/'.$paymentGateway->logo) : null,
                    ],
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('user'), function () {
                return [
                    'owner_email' => $this->user->email,
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('userDevice'), function () {
                $device = $this->userDevice;
                return [
                    'device_name' => $device ? $device->name : null,
                    'device_model' => $device ? $device->device_model : null,
                    'device_android_version' => $device ? $device->android_version : null,
                ];
            }),
            'payment_gateway_ids' => $this->payment_gateway_ids ?? [],
        ];
    }
} 