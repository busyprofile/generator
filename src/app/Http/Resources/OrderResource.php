<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderAdditionalProfit;
use App\Services\Money\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = explode('-', $this->uuid);
        $shotUUID = $items[count($items) - 1];
        /**
         * @var Order $this
         */
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'uuid_short' => $shotUUID,
            'payment_detail_id' => $this->payment_detail_id,
            'external_id' => $this->external_id,
            'provider_order_id' => $this->provider_order_id,
            'base_amount' => $this->amount->toBeauty(),
            'amount' => $this->amount->toBeauty(),
            'total_profit' => $this->total_profit->toBeauty(),
            'trader_profit' => $this->trader_profit->toBeauty(),
            'team_leader_profit' => $this->team_leader_profit->toBeauty(),
            'merchant_profit' => $this->merchant_profit->toBeauty(),
            'service_profit' => $this->service_profit->toBeauty(),
            'trader_paid_for_order' => $this->trader_paid_for_order?->toBeauty(),
            'base_conversion_price' => $this->conversion_price->toBeauty(),
            'conversion_price' => $this->conversion_price->toBeauty(),
            'trader_commission_rate' => $this->trader_commission_rate,
            'team_leader_commission_rate' => $this->team_leader_commission_rate,
            'total_service_commission_rate' => $this->total_service_commission_rate,
            'service_commission_amount_total' => (float)$this->total_profit
                ->mul($this->total_service_commission_rate / 100)
                ->toBeauty(),
            'currency' => $this->currency->getCode(),
            'base_currency' => Currency::USDT()->getCode(),
            'status' => $this->status->value,
            'status_name' => $this->status_name,
            'callback_url' => $this->callback_url,
            'is_h2h' => $this->is_h2h,
            $this->mergeWhen(auth()->check() && auth()->user()->hasRole('Super Admin'), function () {
                return [
                    'amount_updates_history' => $this->amount_updates_history ? array_reverse($this->amount_updates_history) : null,
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('paymentGateway'), function () {
                return [
                    'payment_gateway_code' => $this->paymentGateway?->code,
                    'payment_gateway_name' => $this->paymentGateway?->name_with_currency,
                    'payment_gateway_logo_path' => $this->paymentGateway?->logo ? asset('storage/logos/'.$this->paymentGateway->logo) : null,
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('paymentGateway'), function () {
                return [
                    'payment_gateway_code' => $this->paymentGateway->code,
                    'payment_gateway_name' => $this->paymentGateway->name_with_currency,
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('paymentDetail'), function () {
                // Проверяем, есть ли внешние реквизиты в кэше
                $cacheKey = "order_external_requisites_{$this->resource->id}";
                $externalRequisites = \Cache::get($cacheKey);

                if ($externalRequisites) {
                    // Используем внешние реквизиты
                    return [
                        'payment_detail' => $externalRequisites['detail'] ?? '',
                        'payment_detail_type' => $externalRequisites['detail_type'] ?? 'phone',
                        'payment_detail_name' => $externalRequisites['initials'] ?? '',
                    ];
                }

                // Используем обычные реквизиты из базы данных
                return [
                    'payment_detail' => $this->paymentDetail?->detail ?? 'Данные от партнерской платформы',
                    'payment_detail_type' => $this->paymentDetail?->detail_type?->value ?? 'phone',
                    'payment_detail_name' => $this->paymentDetail?->name ?? 'Партнерские реквизиты',
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('trader'), function () {
                return [
                    'user' => [
                        'id' => $this->trader->id,
                        'name' => $this->trader->name,
                        'email' => $this->trader->email,
                    ]
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('teamLeader') && $this->teamLeader, function () {
                return [
                    'team_leader' => [
                        'id' => $this->teamLeader->id,
                        'name' => $this->teamLeader->name,
                        'email' => $this->teamLeader->email,
                    ]
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('smsLog') && $this->smsLog, function () {
                return [
                    'sms_log' => [
                        'sender' => $this->smsLog->sender,
                        'message' => $this->smsLog->message,
                        'created_at' => $this->smsLog->created_at->toDateTimeString(),
                    ]
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('merchant'), function () {
                return [
                    'merchant' => [
                        'id' => $this->merchant->id,
                        'name' => $this->merchant->name,
                    ],
                ];
            }),
            $this->mergeWhen($this->resource->relationLoaded('additionalProfits'), function () {
                $traderTeamLeaders = $this->additionalProfits->filter(function (OrderAdditionalProfit $profit) {
                    return in_array($profit->source, [
                        'trader_relation_new_system', 
                        'trader_main_old_system', 
                        'trader_additional_old_system'
                    ]);
                })->values();
                $merchantTeamLeaders = $this->additionalProfits->where('source', 'merchant')->values();
                return [
                    'additional_profits' => $traderTeamLeaders->map(function (OrderAdditionalProfit $profit) {
                        return [
                            'team_leader' => [
                                'id' => $profit->teamLeader->id,
                                'name' => $profit->teamLeader->name,
                                'email' => $profit->teamLeader->email,
                            ],
                            'commission_rate' => $profit->commission_rate,
                            'profit_amount' => $profit->profit_amount->toBeauty(),
                            'source' => $profit->source,
                        ];
                    }),
                    'merchant_additional_profits' => $merchantTeamLeaders->map(function (OrderAdditionalProfit $profit) {
                        return [
                            'team_leader' => [
                                'id' => $profit->teamLeader->id,
                                'name' => $profit->teamLeader->name,
                                'email' => $profit->teamLeader->email,
                            ],
                            'commission_rate' => $profit->commission_rate,
                            'profit_amount' => $profit->profit_amount->toBeauty(),
                            'source' => $profit->source,
                        ];
                    })
                ];
            }),
            'has_dispute' => $this->dispute_exists,
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'finished_at' => $this->finished_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'payment_link' => route('payment.show', $this->uuid),
            'canEditAmount' => $this->status->equals(OrderStatus::PENDING) && $this->dispute_exists && $this->trader_paid_for_order,
            $this->mergeWhen(
                $this->resource->relationLoaded('merchant') && $this->merchant,
                function () {
                    $totalProfit = $this->total_profit;
                    $merchantTeamLeadersData = $this->merchant->merchantTeamLeaders->map(function ($teamLeaderUser) use ($totalProfit) {
                        $commission = $teamLeaderUser->pivot->commission_percentage ?? 0;
                        $profitAmount = $totalProfit ? $totalProfit->mul($commission / 100)->toBeauty() : null;
                        return [
                            'email' => $teamLeaderUser->email,
                            'commission' => $commission,
                            'profit' => $profitAmount,
                        ];
                    })->all();
                    return [
                        'merchant_team_leaders' => $merchantTeamLeadersData,
                    ];
                }
            ),
            'trader' => [
                'id' => $this->trader?->id ?? null,
                'name' => $this->trader?->name ?? 'Партнер',
                'email' => $this->trader?->email ?? 'partner@hillcard.net',
            ],
        ];
    }
}
