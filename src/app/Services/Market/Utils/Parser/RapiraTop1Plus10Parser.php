<?php

namespace App\Services\Market\Utils\Parser;

use App\Services\Market\Value\MarketPrices;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Exception;
use Illuminate\Support\Facades\Http;

class RapiraTop1Plus10Parser extends BaseParser
{
    private const PRICE_ADDITION = 10.0; // Добавляем 10 рублей к цене

    public function getPrices(Currency $currency): MarketPrices
    {
        if (!$currency->equals(Currency::RUB())) {
            throw new Exception("RapiraTop1Plus10Parser поддерживает только валюту RUB.");
        }

        list($buyPrice, $sellPrice) = $this->getRapiraTop1Plus10Prices();

        return new MarketPrices(
            buyPrice: Money::fromPrecision($buyPrice, $currency),
            sellPrice: Money::fromPrecision($sellPrice, $currency),
        );
    }

    /**
     * Получить цены из Rapira API: топ-1 заявка + 10 рублей
     */
    public function getRapiraTop1Plus10Prices(): array
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

        // Получаем первую заявку на продажу и покупку (топ-1)
        $topAsk = $data['ask']['items'][0] ?? null; // Продажа USDT
        $topBid = $data['bid']['items'][0] ?? null; // Покупка USDT

        if (!$topAsk || !$topBid) {
            throw new Exception("Нет данных о топ-1 заявках.");
        }

        // Берем цену из первой заявки и добавляем 10 рублей
        $askPrice = (float) $topAsk['price'] + self::PRICE_ADDITION;
        $bidPrice = (float) $topBid['price'] + self::PRICE_ADDITION;

        return [$askPrice, $bidPrice];
    }
}

