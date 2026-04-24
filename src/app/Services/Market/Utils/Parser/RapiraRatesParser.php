<?php

namespace App\Services\Market\Utils\Parser;

use App\Services\Market\Value\MarketPrices;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Exception;
use Illuminate\Support\Facades\Http;

class RapiraRatesParser extends BaseParser
{
    public function getPrices(Currency $currency): MarketPrices
    {
        if ($currency->equals(Currency::RUB())) {
            list($buyPrice, $sellPrice) = $this->getRapiraRatesPrices();
        } else {
            // Для других валют используем Binance как резерв
            $buyPrice = $this->getBinancePrice($currency->getCode(), 'buy');
            $sellPrice = $this->getBinancePrice($currency->getCode(), 'sell');
        }

        return new MarketPrices(
            buyPrice: Money::fromPrecision($buyPrice, $currency),
            sellPrice: Money::fromPrecision($sellPrice, $currency),
        );
    }

    /**
     * Получить курсы из Rapira Rates API
     */
    public function getRapiraRatesPrices(): array
    {
        $url = "https://api.rapira.net/open/market/rates";
        $response = Http::get($url);

        if ($response->failed()) {
            throw new Exception("Не удалось получить данные от Rapira Rates API.");
        }

        $data = $response->json();

        if (empty($data['data'])) {
            throw new Exception("Нет данных о курсах валют.");
        }

        // Ищем USDT/RUB пару
        $usdtRubRate = null;
        foreach ($data['data'] as $rate) {
            if ($rate['symbol'] === 'USDT/RUB') {
                $usdtRubRate = $rate;
                break;
            }
        }

        if (!$usdtRubRate) {
            throw new Exception("Не найден курс USDT/RUB в данных Rapira Rates.");
        }

        // Используем close цену как основную
        $closePrice = (float) $usdtRubRate['close'];
        
        // Если есть askPrice и bidPrice, используем их
        $askPrice = isset($usdtRubRate['askPrice']) ? (float) $usdtRubRate['askPrice'] : $closePrice;
        $bidPrice = isset($usdtRubRate['bidPrice']) ? (float) $usdtRubRate['bidPrice'] : $closePrice;

        // askPrice - цена продажи USDT (покупка рублей)
        // bidPrice - цена покупки USDT (продажа рублей)
        return [$askPrice, $bidPrice];
    }

    /**
     * Получить цену с Binance для других валют (резерв)
     */
    public function getBinancePrice(string $fiat, string $tradeType): ?float
    {
        $payload = [
            "page" => 1,
            "rows" => 5,
            "payTypes" => [],
            "asset" => "USDT",
            "tradeType" => strtoupper($tradeType),
            "fiat" => strtoupper($fiat),
            "publisherType" => null
        ];

        $headers = [
            "Content-Type" => "application/json",
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
        ];

        $response = Http::withHeaders($headers)->post('https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search', $payload);
        
        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $prices = array_map(fn($offer) => (float) $offer['adv']['price'], $data['data'] ?? []);

        if (empty($prices)) {
            return null;
        }

        return array_sum($prices) / count($prices);
    }
} 