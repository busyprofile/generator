<?php

namespace App\Console\Commands;

use App\Enums\MarketEnum;
use App\Services\Market\Utils\Parser\ByBitDopParser;
use App\Services\Market\Utils\Parser\Parser;
use App\Services\Money\Currency;
use Illuminate\Console\Command;
use Throwable;

class TestByBitDopCommand extends Command
{
    protected $signature = 'app:test-bybit-dop {currency=RUB}';

    protected $description = 'Выводит цены BYBIT_DOP и BYBIT для указанной валюты';

    public function handle(): int
    {
        try {
            $currency = Currency::make($this->argument('currency'));

            $dopPrices = (new ByBitDopParser())->getPrices($currency);
            $bybitPrices = (new Parser())->getPrices($currency, MarketEnum::BYBIT);

            $this->info('BYBIT_DOP: buy ' . $dopPrices->buyPrice->toBeauty() . ' / sell ' . $dopPrices->sellPrice->toBeauty());
            $this->info('BYBIT:     buy ' . $bybitPrices->buyPrice->toBeauty() . ' / sell ' . $bybitPrices->sellPrice->toBeauty());

            return 0;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
