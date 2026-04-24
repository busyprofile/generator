<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayoutGateway\StoreRequest;
use App\Http\Requests\PayoutGateway\UpdateRequest;
use App\Http\Resources\PayoutGatewayResource;
use App\Models\PayoutGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PayoutGatewayController extends Controller
{
    public function __construct()
    {
        //TODO
        if (! auth()->user()->payouts_enabled) {
            abort(403);
        }
    }

    public function create()
    {
        return Inertia::render('PayoutGateway/AddEdit');
    }

    public function store(StoreRequest $request)
    {
        PayoutGateway::create($request->validated() + [
                'owner_id' => auth()->id(),
                'uuid' => (string)Str::uuid(),
            ]);
    }

    public function edit(PayoutGateway $payoutGateway)
    {
        Gate::authorize('access-to-payout-gateway', $payoutGateway);

        $payoutGateway = PayoutGatewayResource::make($payoutGateway)->resolve();

        return Inertia::render('PayoutGateway/AddEdit', compact('payoutGateway'));
    }

    public function update(UpdateRequest $request, PayoutGateway $payoutGateway)
    {
        Gate::authorize('access-to-payout-gateway', $payoutGateway);

        $payoutGateway->update($request->validated());
    }
}
