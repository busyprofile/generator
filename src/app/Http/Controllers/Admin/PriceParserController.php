<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PriceParser\UpdateRequest;
use App\Models\ValueObjects\Settings\CurrencyPriceParserSettings;
use App\Services\Money\Currency;
use Inertia\Inertia;

class PriceParserController extends Controller
{
    public function edit(string $currency)
    {
        $currency = new Currency($currency);

        try {
            $methods = services()->market()->getPaymentMethods($currency);
        } catch (\Throwable $e) {
            $methods = [];
        }

        $settings = services()
            ->settings()
            ->getCurrencyPriceParser($currency)
            ->toArray();

        return Inertia::render('Currency/PriceParser/Edit', [
            'currency' => $currency->getCode(),
            'methods' => $methods,
            'settings' => $settings,
        ]);
    }

    public function update(UpdateRequest $request, string $currency)
    {
        services()->settings()->updateCurrencyPriceParser(
            currency: new Currency($currency),
            settings: new CurrencyPriceParserSettings(
                amount: $request->amount,
                payment_method: $request->payment_method,
                ad_quantity: $request->ad_quantity,
            )
        );
    }
}
