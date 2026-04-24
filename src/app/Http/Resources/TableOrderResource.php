<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\Money\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var Order $this
         */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'amount' => $this->amount->toBeauty(),
            'total_profit' => $this->total_profit->toBeauty(),
            'currency' => $this->currency->getCode(),
            'base_currency' => Currency::USDT()->getCode(),
            'status' => $this->status->value,
            'status_name' => $this->status_name,
            'payment_gateway_name' => $this->paymentGateway?->name ?? 'Партнерский шлюз',
            'payment_gateway_logo_path' => $this->paymentGateway?->logo ? asset('storage/logos/'.$this->paymentGateway->logo) : null,
            'payment_detail' => $this->getPaymentDetailData()['detail'],
            'payment_detail_type' => $this->getPaymentDetailData()['detail_type'],
            'payment_detail_name' => $this->getPaymentDetailData()['name'],
            'device_name' => $this->paymentDetail?->userDevice?->name ?? 'Партнерское устройство',
            'trader_email' => $this->trader?->email ?? 'Партнерский ордер',
            'trader_name' => $this->trader?->name ?? 'Партнер',
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }

    /**
     * Получить данные платежных реквизитов
     */
    protected function getPaymentDetailData(): array
    {
        // Проверяем, есть ли внешние реквизиты в кэше
        $cacheKey = "order_external_requisites_{$this->resource->id}";
        $externalRequisites = \Cache::get($cacheKey);

        if ($externalRequisites) {
            // Используем внешние реквизиты
            return [
                'detail' => $externalRequisites['detail'] ?? '',
                'detail_type' => $externalRequisites['detail_type'] ?? 'phone',
                'name' => $externalRequisites['initials'] ?? '',
            ];
        }

        // Используем обычные реквизиты из базы данных
        return [
            'detail' => $this->paymentDetail?->detail ?? 'Партнерские реквизиты',
            'detail_type' => $this->paymentDetail?->detail_type?->value ?? 'phone',
            'name' => $this->paymentDetail?->name ?? '',
        ];
    }
}
