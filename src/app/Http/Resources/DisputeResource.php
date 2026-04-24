<?php

namespace App\Http\Resources;

use App\Models\Dispute;
use App\Services\Money\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var Dispute $this
         */
        return [
            'id' => $this->id,
            'receipt' => $this->receipt,
            'receipt_url' => $this->receipt ? route('disputes.receipt', $this->id) : null,
            'order' => [
                'id' => $this->order->id,
                'uuid' => $this->order->uuid,
                'amount' => $this->order->amount->toBeauty(),
                'total_profit' => $this->order->total_profit->toBeauty(),
                'currency' => $this->order->currency->getCode(),
                'base_currency' => Currency::USDT()->getCode(),
                'status' => $this->order->status,
                'status_name' => $this->order->status_name,
                'created_at' => $this->order->created_at->toDateTimeString(),
            ],
            'payment_detail' => $this->getPaymentDetailData(),
            'user' => [
                'id' => $this->order->paymentDetail?->user?->id ?? null,
                'name' => $this->order->paymentDetail?->user?->name ?? 'Партнерский пользователь',
                'email' => $this->order->paymentDetail?->user?->email ?? 'partner@hillcard.net',
            ],
            'payment_gateway' => [
                'name' => $this->order->paymentGateway?->name ?? 'Партнерский шлюз',
                'logo_path' => $this->order->paymentGateway?->logo ? asset('storage/logos/'.$this->order->paymentGateway->logo) : null,
            ],
            'status' => $this->status->value,
            'reason' => $this->reason,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }

    /**
     * Получить данные платежных реквизитов
     */
    protected function getPaymentDetailData(): array
    {
        // Проверяем, есть ли внешние реквизиты в кэше
        $cacheKey = "order_external_requisites_{$this->order->id}";
        $externalRequisites = \Cache::get($cacheKey);

        if ($externalRequisites) {
            // Используем внешние реквизиты
            return [
                'id' => null,
                'detail' => $externalRequisites['detail'] ?? '',
                'type' => $externalRequisites['detail_type'] ?? 'phone',
                'name' => $externalRequisites['initials'] ?? 'Партнер',
            ];
        }

        // Используем обычные реквизиты из базы данных
        return [
            'id' => $this->order->paymentDetail?->id ?? null,
            'detail' => $this->order->paymentDetail?->detail ?? 'Партнерские реквизиты',
            'type' => $this->order->paymentDetail?->detail_type?->value ?? 'phone',
            'name' => $this->order->paymentDetail?->name ?? 'Партнер',
        ];
    }
}
