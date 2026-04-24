<?php

namespace App\Http\Controllers;

use App\Enums\DetailType;
use App\Exceptions\PayoutException;
use App\Http\Requests\PayoutOffer\StoreRequest;
use App\Http\Requests\PayoutOffer\UpdateRequest;
use App\Http\Resources\PaymentGatewayResource;
use App\Http\Resources\PayoutOfferResource;
use App\Models\PayoutOffer;
use App\Services\Money\Currency;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PayoutOfferController extends Controller
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
        $currencies = Currency::getAll()
            ->transform(function (Currency $currency) {
                return [
                    'code' => $currency->getCode(),
                    'symbol' => $currency->getSymbol(),
                    'name' => $currency->getName(),
                ];
            })->toArray();

        $detailTypes = [];
        foreach (DetailType::values() as $detailType) {
            if (DetailType::CARD->equals($detailType) || DetailType::PHONE->equals($detailType) || DetailType::SIM->equals($detailType)) {
                $detailTypes[] = [
                    'name' => trans('detail-type.'.$detailType),
                    'code' => $detailType,
                ];
            }
        }

        $paymentGateways = queries()->paymentGateway()->getAllActive();
        $paymentGateways = PaymentGatewayResource::collection($paymentGateways)->resolve();

        return Inertia::render('PayoutOffer/AddEdit', compact('currencies', 'detailTypes', 'paymentGateways'));
    }

    public function store(StoreRequest $request)
    {
        try {
            services()->payout()->addOffer(auth()->user(), $request->validated());
        } catch (PayoutException $e) {
            return redirect()->back()->with('message', $e->getMessage());
        }

        return redirect()->back();
    }

    public function edit(PayoutOffer $payoutOffer)
    {
        Gate::authorize('access-to-payout-offer', $payoutOffer);

        $currencies = Currency::getAll()
            ->transform(function (Currency $currency) {
                return [
                    'code' => $currency->getCode(),
                    'symbol' => $currency->getSymbol(),
                    'name' => $currency->getName(),
                ];
            })->toArray();

        $detailTypes = [];
        foreach (DetailType::values() as $detailType) {
            if (DetailType::CARD->equals($detailType) || DetailType::PHONE->equals($detailType) || DetailType::SIM->equals($detailType)) {
                $detailTypes[] = [
                    'name' => trans('detail-type.'.$detailType),
                    'code' => $detailType,
                ];
            }
        }

        $paymentGateways = queries()->paymentGateway()->getAllActive();
        $paymentGateways = PaymentGatewayResource::collection($paymentGateways)->resolve();
        $payoutOffer = PayoutOfferResource::make($payoutOffer)->resolve();

        return Inertia::render('PayoutOffer/AddEdit', compact('currencies', 'detailTypes', 'paymentGateways', 'payoutOffer'));
    }

    public function update(UpdateRequest $request, PayoutOffer $payoutOffer)
    {
        Gate::authorize('access-to-payout-offer', $payoutOffer);

        //TODO check access
        services()->payout()->updateOffer($payoutOffer, $request->validated());
    }
}
