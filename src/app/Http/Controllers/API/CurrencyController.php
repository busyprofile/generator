<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Money\Currency;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::getAll()
            ->transform(function ($currency) {
                return [
                    'currency' => $currency->getCode(),
                    //'buy_price' => services()->market()->getBuyPrice($currency)->toPrecision(),
                    //'sell_price' => services()->market()->getSellPrice($currency)->toPrecision(),
                    'precision' => $currency->getPrecision(),
                    'symbol' => $currency->getSymbol(),
                    'name' => $currency->getName(),
                ];
            });

        return response()->success($currencies);
    }
}
