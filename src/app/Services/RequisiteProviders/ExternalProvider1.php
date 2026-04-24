<?php

namespace App\Services\RequisiteProviders;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
use App\Services\Order\Features\OrderDetailProvider\Values\Gateway;
use App\Services\Order\Features\OrderDetailProvider\Values\Trader;
use Illuminate\Support\Facades\Http;

/**
 * Пример второго провайдера с другим API и протоколом
 */
class ExternalProvider2 extends AbstractRequisiteProvider
{
    public function getName(): string
    {
        return 'external_provider_2';
    }

    public function getPriority(): int
    {
        return 20; // Еще ниже приоритет
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'api_url' => env('EXTERNAL_PROVIDER_2_API_URL', 'https://api.provider2.com'),
            'api_key' => env('EXTERNAL_PROVIDER_2_API_KEY'),
            'api_secret' => env('EXTERNAL_PROVIDER_2_API_SECRET'),
            'timeout' => 20,
            'supported_currencies' => ['RUB', 'KZT', 'TJS'],
            'supported_detail_types' => ['card', 'account_number'],
            'supported_gateways' => [4, 5, 6],
            'min_amount' => 500,
            'max_amount' => 1000000,
            'use_xml' => true, // Этот провайдер использует XML API
        ]);
    }

    public function supports(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?bool $transgran = null,
        ?Merchant $merchant = null
    ): bool {
        return parent::supports($amount, $currency, $gateway, $detailType, $transgran, $merchant);
    }

    public function getRequisites(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null,
        ?Order $order = null
    ): ?Detail {

        $this->log('info', 'Attempting to get requisites from external provider 2', [
            'merchant_id' => $merchant->id,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
        ]);

        try {
            return $this->withRetry(function () use ($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran) {
                if ($this->config['use_xml']) {
                    return $this->makeXmlApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran);
                } else {
                    return $this->makeJsonApiRequest($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran);
                }
            });
        } catch (\Exception $e) {
            $this->log('error', 'Failed to get requisites from external provider 2', [
                'error' => $e->getMessage(),
                'merchant_id' => $merchant->id,
            ]);
            return null;
        }
    }

    /**
     * XML API запрос (другой формат)
     */
    protected function makeXmlApiRequest(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null
    ): ?Detail {

        // Создаем XML запрос
        $xml = new \SimpleXMLElement('<request/>');
        $xml->addChild('merchant_uuid', $merchant->uuid);
        $xml->addChild('amount', $amount->toPrecision());
        $xml->addChild('currency', $currency?->getCode() ?? $amount->getCurrency()->getCode());
        if ($detailType) {
            $xml->addChild('detail_type', $detailType->value);
        }
        if ($gateway) {
            $xml->addChild('gateway_id', $gateway->id);
        }

        // Добавляем подпись
        $signature = $this->generateSignature($xml->asXML());
        $xml->addChild('signature', $signature);

        $response = Http::timeout($this->config['timeout'])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/xml',
            ])
            ->withBody($xml->asXML(), 'application/xml')
            ->post($this->config['api_url'] . '/v2/requisites');

        if (!$response->successful()) {
            throw new \Exception("XML API request failed: " . $response->body());
        }

        $responseXml = simplexml_load_string($response->body());

        if ((string)$responseXml->status !== 'success' || !$responseXml->data) {
            return null;
        }

        return $this->createDetailFromXmlResponse($responseXml->data, $gateway, $amount);
    }

    /**
     * JSON API запрос (стандартный)
     */
    protected function makeJsonApiRequest(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null
    ): ?Detail {

        $requestData = [
            'merchant_uuid' => $merchant->uuid,
            'amount' => $amount->toPrecision(),
            'currency' => $currency?->getCode() ?? $amount->getCurrency()->getCode(),
            'detail_type' => $detailType?->value,
            'gateway_id' => $gateway?->id,
            'timestamp' => time(),
        ];

        // Добавляем подпись
        $requestData['signature'] = $this->generateSignature(json_encode($requestData));

        $response = Http::timeout($this->config['timeout'])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])
            ->post($this->config['api_url'] . '/v2/requisites', $requestData);

        if (!$response->successful()) {
            throw new \Exception("JSON API request failed: " . $response->body());
        }

        $data = $response->json();

        if (empty($data['status']) || $data['status'] !== 'success' || empty($data['data'])) {
            return null;
        }

        return $this->createDetailFromJsonResponse($data['data'], $gateway, $amount);
    }

    /**
     * Генерация подписи для API (пример)
     */
    protected function generateSignature(string $data): string
    {
        return hash_hmac('sha256', $data, $this->config['api_secret']);
    }

    /**
     * Создать Detail из XML ответа
     */
    protected function createDetailFromXmlResponse(\SimpleXMLElement $data, ?PaymentGateway $gateway, Money $amount): Detail
    {
        return $this->createDetailFromArray([
            'detail_id' => (string)$data->detail_id,
            'trader_id' => (string)$data->trader_id,
            'gateway_name' => (string)$data->gateway_name,
            'gateway_code' => (string)$data->gateway_code,
            'currency' => (string)$data->currency,
            'trader_commission_rate' => (float)$data->trader_commission_rate,
            'service_commission_rate' => (float)$data->service_commission_rate,
            'reservation_time' => (int)$data->reservation_time,
            'daily_limit' => (int)$data->daily_limit,
            'current_daily_limit' => (int)$data->current_daily_limit,
            'trust_balance' => (int)$data->trust_balance,
        ], $gateway, $amount);
    }

    /**
     * Создать Detail из JSON ответа
     */
    protected function createDetailFromJsonResponse(array $data, ?PaymentGateway $gateway, Money $amount): Detail
    {
        return $this->createDetailFromArray($data, $gateway, $amount);
    }

    /**
     * Общий метод создания Detail из массива данных
     */
    protected function createDetailFromArray(array $data, ?PaymentGateway $gateway, Money $amount): Detail
    {
        $mockGateway = new Gateway(
            id: $gateway?->id ?? 998,
            code: $data['gateway_code'] ?? 'external2',
            reservationTime: $data['reservation_time'] ?? 20,
            serviceCommissionRate: $data['service_commission_rate'] ?? 6.0,
            traderCommissionRate: $data['trader_commission_rate'] ?? 3.0,
            partnerExternalId: null,
        );

        $mockTrader = new Trader(
            id: $data['trader_id'] ?? 998,
            trustBalance: Money::fromUnits($data['trust_balance'] ?? 2000000, Currency::USDT()),
            teamLeaderID: null,
            teamLeaderCommissionRate: 0.0,
            traderCommissionRate: $data['trader_commission_rate'] ?? null,
        );

        // Рассчитываем профиты
        $exchangePrice = services()->market()->getBuyPrice($amount->getCurrency());
        $totalProfit = $amount->convert($exchangePrice, Currency::USDT());
        $serviceProfit = $totalProfit->mul($mockGateway->serviceCommissionRate / 100);
        $traderProfit = $totalProfit->mul($mockGateway->traderCommissionRate / 100);
        $merchantProfit = $totalProfit->sub($serviceProfit)->sub($traderProfit);

        return new Detail(
            id: $data['detail_id'] ?? 998,
            userID: $data['trader_id'] ?? 998,
            paymentGatewayID: $mockGateway->id,
            userDeviceID: $data['device_id'] ?? null,
            dailyLimit: Money::fromUnits($data['daily_limit'] ?? 2000000, Currency::USDT()),
            currentDailyLimit: Money::fromUnits($data['current_daily_limit'] ?? 0, Currency::USDT()),
            currency: Currency::make($data['currency'] ?? 'RUB'),
            exchangePrice: $exchangePrice,
            totalProfit: $totalProfit,
            serviceProfit: $serviceProfit,
            merchantProfit: $merchantProfit,
            traderProfit: $traderProfit,
            teamLeaderProfit: Money::fromUnits(0, Currency::USDT()),
            traderCommissionRate: $mockGateway->traderCommissionRate,
            teamLeaderCommissionRate: 0.0,
            traderPaidForOrder: $totalProfit->sub($traderProfit),
            gateway: $mockGateway,
            trader: $mockTrader,
            amount: $amount,
        );
    }

    protected function getSupportedCurrencies(): array
    {
        return $this->config['supported_currencies'];
    }

    protected function getSupportedDetailTypes(): array
    {
        return $this->config['supported_detail_types'];
    }

    protected function getSupportedGateways(): array
    {
        return $this->config['supported_gateways'];
    }
} 