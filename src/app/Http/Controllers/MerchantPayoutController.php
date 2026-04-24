<?php

namespace App\Http\Controllers;

use App\Enums\PayoutStatus;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\PayoutGatewayResource;
use App\Models\Payout;
use App\Models\PayoutGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Inertia\Inertia;

class MerchantPayoutController extends Controller
{
    public function __construct()
    {
        //TODO
        if (! auth()->user()->payouts_enabled) {
            abort(403);
        }
    }

    public function index()
    {
        $payout_gateways = request()->input('filters.payout_gateways', '');
        $payout_gateways = explode(',', $payout_gateways);
        $payout_gateways = array_filter($payout_gateways, function ($payout_gateway) {
            return $payout_gateway !== '' && $payout_gateway !== null;
        });
        $statuses = request()->input('filters.statuses', '');
        $statuses = explode(',', $statuses);
        foreach ($statuses as $key => $value) {
            if (! PayoutStatus::tryFrom($value)) {
                unset($statuses[$key]);
            }
        }

        $payoutStatuses = [];
        foreach (PayoutStatus::values() as $status) {
            $payoutStatuses[] = [
                'name' => trans("payout.status.{$status}"),
                'value' => $status,
            ];
        }

        $filtersData = [
            'payout_gateways' => PayoutGateway::query()
                ->where('owner_id', auth()->id())
                ->get()
                ->transform(function (PayoutGateway $gateway) {
                    return [
                        'id' => $gateway->id,
                        'name' => $gateway->name,
                    ];
                }),
            'payout_statuses' => $payoutStatuses,
        ];

        $currentFilters = [
            'payout_gateways' => $payout_gateways,
            'payout_statuses' => $statuses,
        ];

        $payouts = Payout::query()
            ->with(['trader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway'])
            ->where('owner_id', auth()->id())
            ->when(! empty($payout_gateways), function ($query) use ($payout_gateways) {
                $query->whereIn('payout_gateway_id', $payout_gateways);
            })
            ->when(! empty($statuses), function ($query) use ($statuses) {
                $query->whereIn('status', $statuses);
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
        $payouts = PayoutResource::collection($payouts);

        $payoutGateways = PayoutGateway::query()
            ->withSum(['payouts as total_liquidity_amount' => function ($query) {
                $query->where('status', PayoutStatus::SUCCESS);
            }], 'liquidity_amount')
            ->where('owner_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
        $payoutGateways = PayoutGatewayResource::collection($payoutGateways);

        $completedPayoutsQuery = Payout::query()
            ->where('owner_id', auth()->id())
            ->where('status', PayoutStatus::SUCCESS);
        $canceledPayoutsQuery = Payout::query()
            ->where('owner_id', auth()->id())
            ->where('status', PayoutStatus::FAIL);

        $statistics = [
            'completed_payouts' => [
                'amount' => Money::fromUnits($completedPayoutsQuery->clone()->sum('base_liquidity_amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => $completedPayoutsQuery->clone()->count(),
            ],
            'commission' => [
                'amount' => Money::fromUnits($completedPayoutsQuery->clone()->sum('service_commission_amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => $completedPayoutsQuery->clone()->count(),
            ],
            'canceled_payouts' => [
                'amount' => Money::fromUnits($canceledPayoutsQuery->clone()->sum('base_liquidity_amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => $canceledPayoutsQuery->clone()->count(),
            ],
            'total' => [
                'amount' => Money::fromUnits($completedPayoutsQuery->clone()->sum('liquidity_amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => $completedPayoutsQuery->clone()->count(),
            ],
        ];

        return Inertia::render('Payout/Merchant/Index', compact('payoutGateways', 'payouts', 'filtersData', 'currentFilters', 'statistics'));
    }
}
