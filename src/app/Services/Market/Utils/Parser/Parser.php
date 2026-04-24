<?php

namespace App\Services\Market\Utils\Parser;

use App\Enums\MarketEnum;
use App\Services\Market\Value\MarketPrices;
use App\Services\Money\Currency;

class Parser
{
    private const PARSABLE_MARKETS = [
        'bybit',
        'rapira',
        'rapira_rates',
        'rapira_top1_plus10',
    ];

    public static function hasParsableMarket(MarketEnum $market): bool
    {
        return in_array($market->value, self::PARSABLE_MARKETS, true);
    }

    public function getPrices(Currency $currency, MarketEnum $market): MarketPrices
    {
        return match (true) {
            $market->equals(MarketEnum::BYBIT) => (new ByBitParser())->getPrices($currency),
            $market->equals(MarketEnum::RAPIRA) => (new RapiraAndBinanceParser())->getPrices($currency),
            $market->equals(MarketEnum::RAPIRA_RATES) => (new RapiraRatesParser())->getPrices($currency),
            $market->equals(MarketEnum::RAPIRA_TOP1_PLUS10) => (new RapiraTop1Plus10Parser())->getPrices($currency),
            default => throw new \Exception("Parser not implemented for market: {$market->value}"),
        };
    }
}
