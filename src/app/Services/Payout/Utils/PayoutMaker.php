<?php

namespace App\Services\Payout\Utils;

use App\DTO\Payout\PayoutCreateDTO;
use App\Enums\BalanceType;
use App\Enums\DetailType;
use App\Enums\PayoutStatus;
use App\Enums\PayoutSubStatus;
use App\Exceptions\PayoutException;
use App\Jobs\AutoRefusePayoutJob;
use App\Models\PaymentGateway;
use App\Models\Payout;
use App\Models\PayoutOffer;
use App\Models\User;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Payout\Classes\GetExpirationTime;
use App\Services\Payout\Classes\PickPayoutOffer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PayoutMaker
{
    public function create(PayoutCreateDTO $dto): Payout
    {
        if (! $dto->payoutGateway->enabled) {
            throw PayoutException::payoutGatewayIsDisabled();
        }

        $serviceCommission = $dto->paymentGateway->total_service_commission_rate_for_payouts;
        $exchangePriceMarkupRate = $dto->paymentGateway->trader_commission_rate_for_payouts;

        $baseExchangePrice = services()->market()->getSellPrice($dto->amount->getCurrency());
        $markupPart = $baseExchangePrice->mul($exchangePriceMarkupRate / 100);
        $exchangePrice = $baseExchangePrice->sub($markupPart);

        $baseLiquidityAmount = $dto->amount->convert($exchangePrice, Currency::USDT());

        $serviceCommissionAmount = $baseLiquidityAmount->mul($serviceCommission / 100);
        $exchangeMarkupAmount = $dto->amount
            ->convert($baseExchangePrice, Currency::USDT())
            ->sub($baseLiquidityAmount)
            ->abs();

        $liquidityAmount = $baseLiquidityAmount->add($serviceCommissionAmount);

        $traderProfit = $baseLiquidityAmount;

        $sufficientBalance = $dto->payoutGateway
            ->owner
            ->wallet
            ->merchant_balance
            ->greaterOrEquals($liquidityAmount);

        if (! $sufficientBalance) {
            throw PayoutException::insufficientBalance();
        }

        $payoutOffer = $this->getPayoutOffer($dto->amount, $dto->detailType, $dto->paymentGateway);

        if (! $payoutOffer) {
            throw PayoutException::offerNotExists();
        }

        $expires_at = $this->getExpirationTime($dto->paymentGateway);

        $payout = Payout::create([
            'uuid' => (string)Str::uuid(),
            'external_id' => $dto->externalId,
            'detail' => $dto->detail,
            'detail_type' => $dto->detailType,
            'detail_initials' => $dto->detailInitials,
            'payout_amount' => $dto->amount,
            'currency' => $dto->paymentGateway->currency,
            'base_liquidity_amount' => $baseLiquidityAmount,
            'liquidity_amount' => $liquidityAmount,
            'service_commission_rate' => $serviceCommission,
            'service_commission_amount' => $serviceCommissionAmount,
            'trader_profit_amount' => $traderProfit,
            'trader_exchange_markup_rate' => $exchangePriceMarkupRate,
            'trader_exchange_markup_amount' => $exchangeMarkupAmount,
            'base_exchange_price' => $baseExchangePrice,
            'exchange_price' => $exchangePrice,
            'status' => PayoutStatus::PENDING,
            'sub_status' => PayoutSubStatus::PROCESSING_BY_TRADER,
            'callback_url' => $dto->callbackUrl,
            'payout_offer_id' => $payoutOffer->id,
            'payout_gateway_id' => $dto->payoutGateway->id,
            'payment_gateway_id' => $dto->paymentGateway->id,
            'sub_payment_gateway_id' => $dto->subPaymentGateway?->id,
            'trader_id' => $payoutOffer->owner->id,
            'owner_id' => $dto->payoutGateway->owner->id,
            'finished_at' => null,
            'expires_at' => $expires_at,
        ]);

        services()->fundsHolder()->holdFundsFor(
            amount: $baseLiquidityAmount,
            sourceWallet: $dto->payoutGateway->owner->wallet,
            destinationWallet: $payoutOffer->owner->wallet,
            sourceWalletBalanceType: BalanceType::MERCHANT,
            destinationWalletBalanceType: BalanceType::TRUST,
            forAction: $payout,
        );

        services()->fundsHolder()->holdFundsFor(
            amount: $serviceCommissionAmount,
            sourceWallet: $dto->payoutGateway->owner->wallet,
            destinationWallet: User::find(1)->wallet, //TODO
            sourceWalletBalanceType: BalanceType::MERCHANT,
            destinationWalletBalanceType: BalanceType::COMMISSION,
            forAction: $payout,
        );

        AutoRefusePayoutJob::dispatch($payout, $payoutOffer->owner)->delay($expires_at);

        return $payout;
    }

    private function getPayoutOffer(Money $amount, DetailType $detailType, PaymentGateway $paymentGateway): ?PayoutOffer
    {
        return (new PickPayoutOffer())->pick($amount, $detailType, $paymentGateway);
    }

    protected function getExpirationTime(PaymentGateway $paymentGateway): Carbon
    {
        return (new GetExpirationTime($paymentGateway))->get();
    }
}
