<?php

namespace App\Contracts;

use App\Enums\MarketEnum;
use App\Services\Money\Currency;
use App\Services\Money\Money;

interface MarketServiceContract
{
    public function loadAllPrices(): void;

    public function loadPricesFor(Currency $currency, MarketEnum $market): void;

    public function getSellPrice(Currency $currency, MarketEnum $market, bool $withoutFalling = true): Money;

    public function getBuyPrice(Currency $currency, MarketEnum $market, bool $withoutFalling = true): Money;

    public function loadPaymentMethodsList(): void;

    public function getPaymentMethods(Currency $currency): array;
}
