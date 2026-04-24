<?php

namespace App\Http\Controllers\API\Payout;

use App\DTO\Payout\PayoutCreateDTO;
use App\Exceptions\PayoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Payout\StoreRequest;
use App\Http\Resources\API\PayoutResource;
use App\Models\Payout;
use App\Models\PayoutGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PayoutController extends Controller
{
    public function __construct()
    {
        //TODO
        if (! auth()->user()->payouts_enabled) {
            abort(403);
        }
    }

    public function show(Payout $payout)
    {
        Gate::authorize('access-to-payout', $payout);

        $payout->load(['trader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway']);

        return response()->success(
            PayoutResource::make($payout)
        );
    }

    public function store(StoreRequest $request)
    {
        $payoutGateway = PayoutGateway::where('uuid', $request->payout_gateway_id)->first();

        Gate::authorize('access-to-payout-gateway', $payoutGateway);

        try {
            $payout = services()->payout()->createPayout(
                PayoutCreateDTO::makeFromRequest($request->validated())
            );

            $payout->load(['trader', 'owner', 'payoutGateway', 'paymentGateway', 'subPaymentGateway']);

            return response()->success(
                PayoutResource::make($payout)
            );
        } catch (PayoutException $e) {
            return response()->failWithMessage($e->getMessage());
        }
    }
}
