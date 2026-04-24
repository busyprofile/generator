<?php

namespace App\Contracts;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;

interface RequisiteProviderContract
{
    /**
     * Получить название провайдера
     */
    public function getName(): string;

    /**
     * Проверить доступность провайдера
     */
    public function isAvailable(): bool;

    /**
     * Получить приоритет провайдера (чем меньше число, тем выше приоритет)
     */
    public function getPriority(): int;

    /**
     * Поддерживает ли провайдер указанные параметры
     */
    public function supports(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?bool $transgran = null,
        ?Merchant $merchant = null
    ): bool;

    /**
     * Получить реквизиты от провайдера
     * 
     * @param Order|null $order Ордер для идентификации (uuid используется как ID для провайдера)
     */
    public function getRequisites(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null,
        ?Order $order = null
    ): ?Detail;

    /**
     * Получить конфигурацию провайдера
     */
    public function getConfig(): array;

    /**
     * Отменить сделку у провайдера
     * 
     * @param \App\Models\Order $order Ордер для отмены
     * @return bool Успешность операции
     */
    public function cancelOrder(\App\Models\Order $order): bool;
} 