<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BalanceType;
use App\Enums\FundsOnHoldStatus;
use App\Enums\PayoutStatus;
use App\Enums\PayoutSubStatus;
use App\Exceptions\PayoutException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayoutOfferResource;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\PayoutGatewayResource;
use App\Models\FundsOnHold;
use App\Models\Payout;
use App\Models\PayoutGateway;
use App\Models\PayoutOffer;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayoutController extends Controller
{
    public function index()
    {
        $problematicPayouts = Payout::query()
            ->with(['previousTrader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway'])
            ->whereNull('trader_id')
            ->where('status', PayoutStatus::PENDING)
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
        $problematicPayouts = PayoutResource::collection($problematicPayouts);

        $payouts = Payout::query()
            ->with(['trader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway', 'liquidityHold'])
            ->where(function ($query) {
                $query->whereNotNull('trader_id');
                $query->orWhereNot('status', PayoutStatus::PENDING);
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
        $payouts = PayoutResource::collection($payouts);

        $payoutGateways = PayoutGateway::query()
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
        $payoutGateways = PayoutGatewayResource::collection($payoutGateways);

        $payoutOffers = PayoutOffer::query()
            ->orderByDesc('owner_id')
            ->paginate(request()->per_page ?? 10);
        $payoutOffers = PayoutOfferResource::collection($payoutOffers);

        $completedPayoutsQuery = Payout::query()
            ->where('status', PayoutStatus::SUCCESS);

        $canceledPayoutsQuery = Payout::query()
            ->where('status', PayoutStatus::FAIL);

        $fundsOnHoldQuery = FundsOnHold::query()
            ->whereMorphRelation('holdable', Payout::class, 'status', PayoutStatus::SUCCESS)
            ->where('status', FundsOnHoldStatus::PENDING_FOR_EXECUTION)
            ->where('destination_wallet_balance_type', BalanceType::TRUST);

        $statistics = [
            'completed_payouts' => [
                'amount' => Money::fromUnits($completedPayoutsQuery->clone()->sum('base_liquidity_amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => $completedPayoutsQuery->clone()->count(),
            ],
            'commission' => [
                'amount' => Money::fromUnits($completedPayoutsQuery->clone()->sum('service_commission_amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => 0,
            ],
            'canceled_payouts' => [
                'amount' => Money::fromUnits($canceledPayoutsQuery->clone()->sum('base_liquidity_amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => $canceledPayoutsQuery->clone()->count(),
            ],
            'funds_on_hold' => [
                'amount' => Money::fromUnits($fundsOnHoldQuery->clone()->sum('amount'), Currency::USDT())->toBeauty(),
                'currency' => Currency::USDT()->getCode(),
                'count' => $fundsOnHoldQuery->clone()->count(),
            ],
        ];

        return Inertia::render('Payout/Admin/Index', compact('payoutGateways', 'payouts', 'payoutOffers', 'problematicPayouts', 'statistics'));
    }

    public function show(Payout $payout)
    {
        $payout->load(['previousTrader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway']);

        if ($payout->sub_status->notEquals(PayoutSubStatus::PROCESSING_BY_ADMINISTRATOR)) {
            abort(403);
        }

        $payout = PayoutResource::make($payout)->resolve();

        return Inertia::render('Payout/Admin/Show', compact('payout'));
    }

    public function receipt(Payout $payout)
    {
        $file_path = storage_path('video_receipts/'.$payout->video_receipt);

        return response()->file($file_path);
    }

    public function finish(Payout $payout)
    {
        services()->payout()->finishPayoutByAdmin($payout);

        return redirect()->route('admin.payouts.index')->with('message', 'Вы завершили выплату. Средства поступили на ваш счет.');
    }

    public function cancel(Payout $payout, Request $request)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'min:10', 'max:1000'],
        ]);

        services()->payout()->cancelPayout($payout, $request->reason);

        return redirect()->route('admin.payouts.index')->with('message', 'Вы отклонили выплату, деньги вернутся на счет мерчанта.');
    }

    public function passToTrader(Payout $payout)
    {
        try {
            services()->payout()->passToTrader($payout);

            return redirect()->route('admin.payouts.index')->with('message', 'Выплата передана свободному трейдеру. Теперь она отображается в списке всех выплат.');
        } catch (PayoutException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
