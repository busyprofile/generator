<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MarketEnum;
use App\Http\Controllers\Controller;
use App\Services\Money\Currency;
use Inertia\Inertia;

class CurrencyController extends Controller
{
    public function index()
    {
        $markets = [];

        foreach (MarketEnum::cases() as $market) {
            $currencies = [];

            Currency::getAll()
                ->map(function (Currency $currency) use (&$currencies, $market) {
                    $currencies[] = [
                        'code' => $currency->getCode(),
                        'symbol' => $currency->getSymbol(),
                        'name' => $currency->getName(),
                        'buy_price' => services()->market()->getBuyPrice($currency, $market, false)->toPrecision(),
                        'sell_price' => services()->market()->getSellPrice($currency, $market, false)->toPrecision(),
                    ];
                });

            $markets[$market->value] = $currencies;
        }

        return Inertia::render('Currency/Index', compact('markets'));
    }
}
