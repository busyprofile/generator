<?php

namespace App\Services\Order\Features\OrderDetailProvider;

use App\Enums\DetailType;
use App\Exceptions\OrderException;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Classes\FindAvailablePaymentDetail;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;

class OrderDetailProvider
{
    public function __construct(
        protected Order $order,
        protected Merchant $merchant,
        protected Money $amount,
        protected ?Currency $currency = null,
        protected ?PaymentGateway $gateway = null,
        protected ?DetailType $detailType = null,
        protected ?bool $transgran = null,
    )
    {}

    /**
     * @throws OrderException
     */
    public function provide(): Detail
    {
        \Log::error('[OrderDetailProvider] Start provide', [
            'order_id' => $this->order->id,
            'merchant_id' => $this->merchant->id,
            'amount' => $this->amount->toBeauty(),
            'currency' => $this->currency?->getCode(),
            'gateway_id' => $this->gateway?->id,
            'detail_type' => $this->detailType?->value,
            'transgran' => $this->transgran,
        ]);

        // Используем цепочку провайдеров для каскадного поиска реквизитов
        $chain = services()->requisiteProviderChain();
        $selectedDetail = $chain->getRequisites(
            merchant: $this->merchant,
            market: $this->order->market,
            amount: $this->amount,
            detailType: $this->detailType,
            currency: $this->currency,
            gateway: $this->gateway,
            transgran: $this->transgran,
            order: $this->order
        );

        if (! $selectedDetail) {
            \Log::error('[OrderDetailProvider] No detail found via provider chain', [
                'order_id' => $this->order->id,
                'merchant_id' => $this->merchant->id,
            ]);

            // ВАЖНО: этот блок специально для диагностики, чтобы причина была видна
            // в ответе OrderPoolingService даже если логи воркера недоступны.
            try {
                $selector = $chain->getSelector();

                $amountPrecision = $this->amount->toPrecision();
                $amountFloat = (float) $amountPrecision;

                $activeTerminals = $selector->getActiveTerminals();
                $suitableTerminals = $selector->selectAllSuitableTerminals(
                    merchantId: $this->merchant->id,
                    amount: $amountFloat,
                    detailType: $this->detailType,
                    checkBalance: true
                );

                $activeIntegrationsCount = $activeTerminals
                    ->pluck('integration')
                    ->map(fn($v) => is_string($v) ? $v : (string) $v)
                    ->countBy()
                    ->all();

                $suitableIntegrationsCount = $suitableTerminals
                    ->pluck('integration')
                    ->map(fn($v) => is_string($v) ? $v : (string) $v)
                    ->countBy()
                    ->all();

                $firstSuitable = $suitableTerminals
                    ->take(25)
                    ->map(fn(array $t) => [
                        'terminal_id' => $t['id'] ?? null,
                        'terminal_uuid' => $t['uuid'] ?? null,
                        'provider_name' => $t['provider_name'] ?? null,
                        'integration' => $t['integration'] ?? null,
                        'priority' => $t['priority'] ?? null,
                        'min_sum' => $t['min_sum'] ?? null,
                        'max_sum' => $t['max_sum'] ?? null,
                        'enabled_detail_types' => $t['enabled_detail_types'] ?? null,
                    ])
                    ->values()
                    ->all();

                $diag = [
                    'order_id' => $this->order->id,
                    'merchant_id' => $this->merchant->id,
                    'amount_toPrecision' => $amountPrecision,
                    'amount_float_used_for_terminal_filter' => $amountFloat,
                    'currency' => $this->currency?->getCode(),
                    'gateway_id' => $this->gateway?->id,
                    'detail_type' => $this->detailType?->value,
                    'transgran' => $this->transgran,
                    'active_terminals_count' => $activeTerminals->count(),
                    'active_integrations_count' => $activeIntegrationsCount,
                    'suitable_terminals_count' => $suitableTerminals->count(),
                    'suitable_integrations_count' => $suitableIntegrationsCount,
                    'first_suitable_terminals' => $firstSuitable,
                    'chain_diagnostics' => $chain->getLastDiagnostics(),
                    'hint' => 'Если suitable_terminals_count=0, MethodPay не будет вызван. Причины: provider/provider_terminal is_active=false, нет связи merchant<->terminal (provider_terminal_merchant.is_active), enabled_detail_types не содержит нужный тип, min_sum/max_sum не совпадают по единицам (рубли vs копейки), баланс трейдера <= 0.',
                ];

                \Log::error('[OrderDetailProvider] Diagnostics: no detail found', $diag);

                // Диагностика только в логах, мерчанту возвращаем чистое сообщение
                throw OrderException::make('Подходящие платежные реквизиты не найдены');
            } catch (OrderException $e) {
                throw $e;
            } catch (\Throwable $e) {
                // fallback: не ломаем ошибку диагностикой
                throw OrderException::make('Подходящие платежные реквизиты не найдены');
            }
        }

        \Log::error('[OrderDetailProvider] Detail selected', [
            'order_id' => $this->order->id,
            'merchant_id' => $this->merchant->id,
            'detail_id' => $selectedDetail->id,
            'provider' => $selectedDetail->gateway->code ?? null,
        ]);

        return $selectedDetail;
    }
}
