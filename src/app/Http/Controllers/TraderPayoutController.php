<?php

namespace App\Http\Controllers;

use App\Enums\BalanceType;
use App\Enums\FundsOnHoldStatus;
use App\Enums\PayoutStatus;
use App\Enums\PayoutSubStatus;
use App\Http\Resources\PayoutOfferResource;
use App\Http\Resources\PayoutResource;
use App\Models\FundsOnHold;
use App\Models\Payout;
use App\Models\PayoutOffer;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TraderPayoutController extends Controller
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
        $payouts = Payout::query()
            ->with(['trader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway', 'liquidityHold'])
            ->where('trader_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
        $payouts = PayoutResource::collection($payouts);

        $payoutOffers = PayoutOffer::query()
            ->withSum(['payouts as total_payout_amount' => function ($query) {
                $query->where('status', PayoutStatus::SUCCESS);
            }], 'payout_amount')
            ->where('owner_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);

        $payoutOffers = PayoutOfferResource::collection($payoutOffers);

        $totalFundsOnHoldAmount = FundsOnHold::query()
            ->whereMorphRelation('holdable', Payout::class, 'status', PayoutStatus::SUCCESS)
            ->where('status', FundsOnHoldStatus::PENDING_FOR_EXECUTION)
            ->where('destination_wallet_id', auth()->user()->wallet->id)
            ->where('destination_wallet_balance_type', BalanceType::TRUST)
            ->sum('amount');

        $totalFundsOnHold = [
            'amount' => Money::fromUnits($totalFundsOnHoldAmount, Currency::USDT())->toBeauty(),
            'currency' => Currency::USDT()->getCode(),
        ];

        $totalTurnoverAmount = FundsOnHold::query()
            ->whereMorphRelation('holdable', Payout::class, 'status', PayoutStatus::SUCCESS)
            ->where('status', FundsOnHoldStatus::COMPLETED)
            ->where('destination_wallet_id', auth()->user()->wallet->id)
            ->where('destination_wallet_balance_type', BalanceType::TRUST)
            ->sum('amount');

        $totalTurnover = [
            'amount' => Money::fromUnits($totalTurnoverAmount, Currency::USDT())->toBeauty(),
            'currency' => Currency::USDT()->getCode(),
        ];

        return Inertia::render('Payout/Trader/Index', compact('payoutOffers', 'payouts', 'totalFundsOnHold', 'totalTurnover'));
    }

    public function show(Payout $payout)
    {
        $payout->load(['trader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway']);

        if ($payout->status->notEquals(PayoutStatus::PENDING)) {
            abort(403);
        }

        if ($payout->previousTrader?->id === auth()->id()) { //TODO refactoring
            $payout = PayoutResource::make($payout)->resolve();
            return Inertia::render('Payout/Trader/PayoutExpired', compact('payout'));
        } else {
            Gate::authorize('access-to-payout', $payout);
        }

        if ($payout->sub_status->notEquals(PayoutSubStatus::PROCESSING_BY_TRADER)) {
            abort(403);
        }

        $payout = PayoutResource::make($payout)->resolve();

        return Inertia::render('Payout/Trader/Show', compact('payout'));
    }

    public function finish(Payout $payout, Request $request)
    {
        Gate::authorize('access-to-payout', $payout);

        $request->validate([
            'video_receipt' => ['required', 'mimetypes:video/avi,video/mpeg,video/quicktime', 'max:2048'],
        ]);

        $receiptVideo = $request->file('video_receipt');

        services()->payout()->finishPayout($payout, $receiptVideo);

        return redirect()->route('trader.payouts.index')->with('message', 'Вы завершили выплату. Средства поступят на ваш счет после завершения холда.');
    }

    public function refuse(Payout $payout, Request $request)
    {
        Gate::authorize('access-to-payout', $payout);

        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        services()->payout()->refusePayout($payout, $request->reason);

        return redirect()->route('trader.payouts.index')->with('message', 'Вы отказались от исполнения выплаты, вы больше не видите ее в списке ваших выплат.');
    }
}
