<?php

namespace App\Services\Market\Utils\Parser;

use App\Services\Market\Value\MarketPrices;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Exception;
use Illuminate\Support\Facades\Http;

class ByBitDopParser extends BaseParser
{
    /**
     * Специализированный парсер для BYBIT - ТОЧНО как в Python:
     * - side = "1" (продажа)
     * - Local Card (Green) (payment = ["582"])
     * - Объём от 80,000₽ (amount = "80000")
     * - БЕЗ дополнительной фильтрации (как в Python)
     * - Среднее арифметическое между 3-м и 4-м объявлением из ответа API
     */
    public function getPrices(Currency $currency): MarketPrices
    {
        if (! $currency->equals(Currency::RUB())) {
            throw new Exception('BYBIT_DOP поддерживает только RUB валюту');
        }

        $buyPrice = $this->getBuyPriceFromByBit();

        return new MarketPrices(
            buyPrice: Money::fromPrecision($buyPrice, $currency),
            sellPrice: Money::fromPrecision($buyPrice, $currency),
        );
    }

    /**
     * Получает цену покупки USDT с ByBit P2P API с точными фильтрами
     */
    private function getBuyPriceFromByBit(): float
    {
        $payload = [
            'userId' => '',
            'tokenId' => 'USDT',
            'currencyId' => 'RUB',
            'payment' => ['582'],
            'side' => '1',
            'size' => '10',
            'page' => '1',
            'amount' => '80000',
            'authMaker' => false,
            'canTrade' => false,
        ];

        $response = Http::asJson()->withHeaders([
            'Accept' => '*/*',
            'Accept-Encoding' => 'gzip, deflate, br',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->post('https://api2.bybit.com/fiat/otc/item/online', $payload);

        if ($response->failed()) {
            throw new Exception('Не удалось получить данные от ByBit P2P API');
        }

        $data = $response->json();

        if (($data['ret_msg'] ?? null) !== 'SUCCESS') {
            throw new Exception('ByBit API ошибка: ' . ($data['ret_msg'] ?? 'Unknown error'));
        }

        if (! isset($data['result']['items']) || empty($data['result']['items'])) {
            throw new Exception('Нет доступных объявлений на ByBit P2P для указанных параметров');
        }

        $items = $data['result']['items'];

        if (count($items) < 4) {
            throw new Exception('Недостаточно объявлений для расчета цены (найдено: ' . count($items) . ', нужно минимум 4)');
        }

        $thirdPrice = (float) $items[2]['price'];
        $fourthPrice = (float) $items[3]['price'];
        $averagePrice = ($thirdPrice + $fourthPrice) / 2;

        // \Log::info('BYBIT_DOP парсер - ТОЧНО как в Python', [
        //     'endpoint' => 'https://api2.bybit.com/fiat/otc/item/online',
        //     'request_params' => [
        //         'side' => '1 (продажа)',
        //         'amount' => '80000',
        //         'payment' => '582 (Local Card Green)',
        //         'authMaker' => false,
        //         'canTrade' => false,
        //     ],
        //     'total_items' => count($items),
        //     'no_filtering' => 'КАК В PYTHON - без фильтрации',
        //     'first_6_prices' => array_slice(array_column($items, 'price'), 0, 6),
        //     'third_ad' => [
        //         'position' => 3,
        //         'price' => $thirdPrice,
        //         'user' => $items[2]['advertiser']['nickName'] ?? 'unknown',
        //     ],
        //     'fourth_ad' => [
        //         'position' => 4,
        //         'price' => $fourthPrice,
        //         'user' => $items[3]['advertiser']['nickName'] ?? 'unknown',
        //     ],
        //     'average_price' => $averagePrice,
        //     'calculation' => "({$thirdPrice} + {$fourthPrice}) / 2 = {$averagePrice}",
        // ]);

        return $averagePrice;
    }
}
