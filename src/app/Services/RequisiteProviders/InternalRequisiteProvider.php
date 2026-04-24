<?php

namespace App\Services\RequisiteProviders;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Classes\FindAvailablePaymentDetail;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;

class InternalRequisiteProvider extends AbstractRequisiteProvider
{
    public function getName(): string
    {
        return 'internal';
    }

    public function getPriority(): int
    {
        return 1; // Самый высокий приоритет
    }

    public function supports(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?bool $transgran = null,
        ?Merchant $merchant = null
    ): bool {
        // Внутренний провайдер поддерживает все параметры
        return $this->validateParameters($amount, $currency, $gateway, $detailType);
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
        // Не используем executeWithLogging для internal провайдера,
        // чтобы не создавать записи в requisite_provider_logs
        return $this->getInternalRequisites($merchant, $market, $amount, $detailType, $currency, $gateway, $transgran);
    }

    /**
     * Внутренний метод получения реквизитов (без логирования)
     * Ищет ТОЛЬКО внутренние реквизиты (is_external = false).
     *
     * Внешние реквизиты должны получаться через внешний провайдер (H2H API)
     * в RequisiteProviderChain (шаг 2), а не через внутренний провайдер.
     */
    protected function getInternalRequisites(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null
    ): ?Detail {
        
        $this->logInfo('Attempting to get internal requisites', [
            'merchant_id' => $merchant->id,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'gateway_id' => $gateway?->id,
            'detail_type' => $detailType?->value,
        ]);

        try {
            $finder = new FindAvailablePaymentDetail(
                merchant: $merchant,
                market: $market,
                amount: $amount,
                currency: $currency,
                gateway: $gateway,
                detailType: $detailType,
                transgran: $transgran
            );

            // Сначала ищем внутренние реквизиты (is_external = false)
            $internalDetail = $finder->get();
            if ($internalDetail) {
                $this->logInfo('Found internal payment detail', [
                    'payment_detail_id' => $internalDetail->id,
                    'user_id' => $internalDetail->userID,
                    'merchant_id' => $merchant->id,
                ]);
                return $internalDetail;
            }

            $this->logInfo('No internal requisites found', [
                'merchant_id' => $merchant->id,
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logWarning('Failed to get requisites', [
                'error' => $e->getMessage(),
                'merchant_id' => $merchant->id,
                'amount' => $amount->toBeauty(),
            ]);
            return null;
        }
    }

    protected function getSupportedCurrencies(): array
    {
        // Внутренний провайдер поддерживает все валюты системы
        return Currency::getAll()->map(fn(Currency $currency) => $currency->getCode())->toArray();
    }

    protected function getSupportedDetailTypes(): array
    {
        // Внутренний провайдер поддерживает все типы реквизитов
        return array_map(fn(DetailType $type) => $type->value, DetailType::cases());
    }

    protected function getSupportedGateways(): array
    {
        // Внутренний провайдер поддерживает все шлюзы
        return PaymentGateway::pluck('id')->toArray();
    }
} 