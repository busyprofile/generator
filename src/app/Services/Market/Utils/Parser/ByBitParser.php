<?php

namespace App\Services\Market\Utils\Parser;

use App\Services\Market\Value\MarketPrices;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ByBitParser extends BaseParser
{
    private const API_BASE = 'https://api2.bybit.com/fiat/otc';
    private const TIMEOUT = 15;

    public function getPrices(Currency $currency): MarketPrices
    {
        return new MarketPrices(
            $this->parseBuyPrice($currency),
            $this->parseSellPrice($currency),
        );
    }

    protected function parseBuyPrice(Currency $currency): Money
    {
        $price = $this->parseAveragePrice($currency);

        return Money::fromPrecision($price, $currency);
    }

    protected function parseSellPrice(Currency $currency): Money
    {
        $price = $this->parseAveragePrice($currency, false);

        return Money::fromPrecision($price, $currency);
    }

    public function parsePaymentMethodsList(): array
    {
        $response = Http::asJson()
            ->timeout(self::TIMEOUT)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'PostmanRuntime/7.42.0',
            ])
            ->post(self::API_BASE . '/configuration/queryAllPaymentList');

        if ($response->failed()) {
            throw new \Exception('ByBit queryAllPaymentList HTTP error: ' . $response->status());
        }

        $result = $response->json();

        if (empty($result) || ($result['ret_msg'] ?? null) !== 'SUCCESS') {
            throw new \Exception('ByBit queryAllPaymentList error: ' . ($result['ret_msg'] ?? 'empty response'));
        }

        if (empty($result['result'])) {
            throw new \Exception('ByBit queryAllPaymentList: empty result');
        }

        return $result['result'];
    }

    protected function parseAveragePrice(Currency $currency, bool $buy = true): float
    {
        $settings = services()->settings()->getCurrencyPriceParser($currency);

        $ad_quantity = $settings->ad_quantity ?: 3;

        $data = [
            'userId' => "",
            'tokenId' => "USDT",
            'currencyId' => strtoupper($currency->getCode()),
            'payment' => $settings->payment_method ? [strval($settings->payment_method)] : [],
            'side' => strval(intval($buy)),
            'size' => strval($ad_quantity),
            'page' => "1",
            'amount' => $settings->amount ? strval($settings->amount) : "",
            'authMaker' => false,
            'canTrade' => false
        ];

        $response = Http::asJson()
            ->timeout(self::TIMEOUT)
            ->withHeaders([
                'Accept' => '*/*',
            ])
            ->post(self::API_BASE . '/item/online', $data);

        if ($response->failed()) {
            throw new \Exception('ByBit P2P API HTTP error: ' . $response->status() . ' for ' . $currency->getCode());
        }

        $json = $response->json();

        if (empty($json) || ($json['ret_msg'] ?? null) !== 'SUCCESS') {
            throw new \Exception('ByBit P2P API error: ' . ($json['ret_msg'] ?? 'empty response') . ' for ' . $currency->getCode());
        }

        $items = $json['result']['items'] ?? [];

        if (empty($items)) {
            throw new \Exception('ByBit P2P: no ads found for ' . $currency->getCode() . ' side=' . ($buy ? 'buy' : 'sell'));
        }

        $prices = [];
        foreach ($items as $item) {
            $prices[] = (float) $item['price'];
        }

        $delimiter = min(count($prices), $ad_quantity);

        if ($delimiter === 0) {
            throw new \Exception('ByBit P2P: zero valid prices for ' . $currency->getCode());
        }

        return round(array_sum($prices) / $delimiter, 2);
    }
}
