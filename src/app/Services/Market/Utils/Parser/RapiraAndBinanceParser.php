<?php

namespace App\Services\Market\Utils\Parser;

use App\Services\Market\Value\MarketPrices;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Exception;
use Illuminate\Support\Facades\Http;

class RapiraAndBinanceParser extends BaseParser
{
    public function getPrices(Currency $currency): MarketPrices
    {
        if ($currency->equals(Currency::RUB())) {
            list($buyPrice, $sellPrice) = $this->getRapiraPrices();
        } else {
            $buyPrice = $this->getBinancePrice($currency->getCode(), 'buy');
            $sellPrice = $this->getBinancePrice($currency->getCode(), 'sell');
        }

        return new MarketPrices(
            buyPrice: Money::fromPrecision($buyPrice, $currency),
            sellPrice: Money::fromPrecision($sellPrice, $currency),
        );
    }

    public function getRapiraPrices(): array
    {
        $url = "https://api.rapira.net/market/exchange-plate-mini?symbol=USDT/RUB";
        $response = Http::get($url);

        if ($response->failed()) {
            throw new Exception("Не удалось получить данные от Rapira API.");
        }

        $data = $response->json();

        if (empty($data['ask']['items']) || empty($data['bid']['items'])) {
            throw new Exception("Нет данных о стакане заявок.");
        }

        // Получаем первые 5 заявок на продажу и покупку
        $topAsks = array_slice($data['ask']['items'], 0, 5); // Продажа USDT
        $topBids = array_slice($data['bid']['items'], 0, 5); // Покупка USDT

        // Вычисляем среднюю цену
        $averageAskPrice = array_sum(array_column($topAsks, 'price')) / count($topAsks);
        $averageBidPrice = array_sum(array_column($topBids, 'price')) / count($topBids);

        return [$averageAskPrice, $averageBidPrice];
    }

    public function getBinancePrice(string $fiat, string $tradeType): ?float
    {
        $payload = [
            "page" => 1,
            "rows" => 5, // Запрашиваем последние 5 предложений
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
        $response->throw();

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $prices = array_map(fn($offer) => (float) $offer['adv']['price'], $data['data'] ?? []);

        if (empty($prices)) {
            return null;
        }

        return array_sum($prices) / count($prices); // Среднее значение
    }
}
