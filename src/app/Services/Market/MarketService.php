<?php

namespace App\Services\Market;

use App\Contracts\MarketServiceContract;
use App\Enums\MarketEnum;
use App\Jobs\LoadConversionPricesJob;
use App\Services\Market\Utils\Parser\ByBitParser;
use App\Services\Money\Currency;
use App\Services\Market\Utils\MarketStore;
use App\Services\Market\Utils\Parser\Parser;
use App\Services\Money\Money;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketService implements MarketServiceContract
{
    protected Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function loadAllPrices(): void
    {
        Currency::getAll()
            ->each(function (Currency $currency) {
                foreach (MarketEnum::cases() as $market) {
                    if (! Parser::hasParsableMarket($market)) {
                        continue;
                    }
                    LoadConversionPricesJob::dispatch($currency, $market);
                }
            });
    }

    public function loadPricesFor(Currency $currency, MarketEnum $market = MarketEnum::BYBIT): void
    {
        try {
            $prices = $this->parser->getPrices($currency, $market);

            MarketStore::putPrice(
                currency: $currency,
                market: $market,
                buy_price: $prices->buyPrice->toUnits(),
                sell_price: $prices->sellPrice->toUnits()
            );
        } catch (Throwable $e) {
            Log::warning("Market price load failed [{$market->value}] [{$currency->getCode()}]: {$e->getMessage()}");
        }
    }

    public function getSellPrice(Currency $currency, MarketEnum $market = MarketEnum::BYBIT, bool $withoutFalling = true): Money
    {
        $price = MarketStore::getSellPrice($currency, $market);

        if (! $price && $withoutFalling) {
            $fallbackMarkets = $this->getFallbackMarkets($market);

            foreach ($fallbackMarkets as $fallbackMarket) {
                $price = MarketStore::getSellPrice($currency, $fallbackMarket);
                if ($price) {
                    break;
                }
            }
        }
        if (! $price && ! $withoutFalling) {
            $price = 0;
        }

        return new Money((string) ($price ?? '0'), $currency);
    }

    public function getBuyPrice(Currency $currency, MarketEnum $market = MarketEnum::BYBIT, bool $withoutFalling = true): Money
    {
        $price = MarketStore::getBuyPrice($currency, $market);

        if (! $price && $withoutFalling) {
            $fallbackMarkets = $this->getFallbackMarkets($market);

            foreach ($fallbackMarkets as $fallbackMarket) {
                $price = MarketStore::getBuyPrice($currency, $fallbackMarket);
                if ($price) {
                    break;
                }
            }
        }
        if (! $price && ! $withoutFalling) {
            $price = 0;
        }

        return new Money((string) ($price ?? '0'), $currency);
    }

    private function getFallbackMarkets(MarketEnum $currentMarket): array
    {
        $allMarkets = [MarketEnum::BYBIT, MarketEnum::RAPIRA_RATES, MarketEnum::RAPIRA, MarketEnum::RAPIRA_TOP1_PLUS10];

        return array_values(array_filter($allMarkets, fn ($market) => ! $market->equals($currentMarket)));
    }

    public function loadPaymentMethodsList(): void
    {
        $methods = (new ByBitParser())->parsePaymentMethodsList();
        MarketStore::putPaymentMethodsList($methods);
    }

    public function getPaymentMethods(Currency $currency): array
    {
        $paymentList = MarketStore::getPaymentMethodsList();

        if (empty($paymentList) || empty($paymentList['currencyPaymentIdMap'] ?? null) || empty($paymentList['paymentConfigVo'] ?? null)) {
            return [];
        }

        $currencyPaymentIdMap = json_decode($paymentList['currencyPaymentIdMap'], true);
        if (empty($currencyPaymentIdMap)) {
            return [];
        }

        $paymentConfigVo = $paymentList['paymentConfigVo'];
        $currencyCode = strtoupper($currency->getCode());

        if (empty($currencyPaymentIdMap[$currencyCode])) {
            return [];
        }

        $currencyPaymentIds = $currencyPaymentIdMap[$currencyCode];
        $methods = [];

        foreach ($paymentConfigVo as $paymentMethod) {
            if (in_array($paymentMethod['paymentType'], $currencyPaymentIds)) {
                $methods[] = [
                    'id' => $paymentMethod['paymentType'],
                    'name' => $paymentMethod['paymentName'],
                ];
            }
        }

        return $methods;
    }
}
