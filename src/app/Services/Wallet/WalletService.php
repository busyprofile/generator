<?php

namespace App\Services\Wallet;

use App\Contracts\WalletServiceContract;
use App\Enums\BalanceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\TransactionType;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Wallet\GiveToBalanceHandler\GiveToCommission;
use App\Services\Wallet\GiveToBalanceHandler\GiveToMerchant;
use App\Services\Wallet\GiveToBalanceHandler\GiveToTeamleader;
use App\Services\Wallet\GiveToBalanceHandler\GiveToTrust;
use App\Services\Wallet\TakeFromBalanceHandler\TakeFromCommission;
use App\Services\Wallet\TakeFromBalanceHandler\TakeFromMerchant;
use App\Services\Wallet\TakeFromBalanceHandler\TakeFromTeamleader;
use App\Services\Wallet\TakeFromBalanceHandler\TakeFromTrust;
use App\Services\Wallet\ValueObjects\BalanceValue;
use App\Services\Wallet\ValueObjects\BaseValue;
use App\Services\Wallet\ValueObjects\CurrencyValue;
use App\Services\Wallet\ValueObjects\EscrowValue;
use App\Services\Wallet\ValueObjects\EscrowsValue;
use App\Services\Wallet\ValueObjects\WalletStatsValue;
use App\Utils\Transaction;

class WalletService implements WalletServiceContract
{
    public function getMaxReserveBalance(): int
    {
        return Wallet::RESERVE_BALANCE;
    }

    public function create(User $user, ?int $targetReserveAmount = null): Wallet
    {
        return Wallet::create([
            'merchant_balance' => 0,
            'trust_balance' => 0,
            'reserve_balance' => 0,
            'commission_balance' => 0,
            'teamleader_balance' => 0,
            'target_reserve_amount' => $targetReserveAmount ?? Wallet::RESERVE_BALANCE,
            'user_id' => $user->id,
        ]);
    }

    public function takeFromBalance(int $walletID, Money $amount, TransactionType $transactionType, BalanceType $balanceType): void
    {
        Transaction::run(function () use ($walletID, $amount, $transactionType, $balanceType) {
            $wallet = Wallet::where('id', $walletID)->lockForUpdate()->first();

            $handler = null;

            if ($balanceType->equals(BalanceType::TRUST)) {
                $handler = new TakeFromTrust();
            } else if ($balanceType->equals(BalanceType::MERCHANT)) {
                $handler = new TakeFromMerchant();
            } else if ($balanceType->equals(BalanceType::COMMISSION)) {
                $handler = new TakeFromCommission();
            } else if ($balanceType->equals(BalanceType::TEAMLEADER)) {
                $handler = new TakeFromTeamleader();
            }

            $handler->handle($wallet, $amount, $transactionType);
        });
    }

    public function giveToBalance(int $walletID, Money $amount, TransactionType $transactionType, BalanceType $balanceType): void
    {
        \Log::info('Начало создания транзакции', [
            'wallet_id' => $walletID,
            'amount' => $amount->toUnits(),
            'transaction_type' => $transactionType,
            'balance_type' => $balanceType
        ]);

        Transaction::run(function () use ($walletID, $amount, $transactionType, $balanceType) {
            $wallet = Wallet::where('id', $walletID)->lockForUpdate()->first();

            if (!$wallet) {
                \Log::error('Кошелек не найден', ['wallet_id' => $walletID]);
                return;
            }

            \Log::info('Кошелек найден', [
                'wallet_id' => $wallet->id, 
                'user_id' => $wallet->user_id
            ]);

            $handler = null;

            if ($balanceType->equals(BalanceType::TRUST)) {
                $handler = new GiveToTrust();
            } else if ($balanceType->equals(BalanceType::MERCHANT)) {
                $handler = new GiveToMerchant();
            } else if ($balanceType->equals(BalanceType::COMMISSION)) {
                $handler = new GiveToCommission();
            } else if ($balanceType->equals(BalanceType::TEAMLEADER)) {
                $handler = new GiveToTeamleader();
            }

            \Log::debug('Перед обработкой транзакции', [
                'handler' => get_class($handler),
                'wallet_id' => $wallet->id,
                'amount' => $amount->toUnits()
            ]);

            $handler->handle($wallet, $amount, $transactionType);

            \Log::info('Транзакция успешно создана', [
                'wallet_id' => $wallet->id,
                'amount' => $amount->toUnits(),
                'transaction_type' => $transactionType,
                'balance_type' => $balanceType
            ]);
        });
    }

    public function getTotalAvailableBalance(Wallet $wallet, BalanceType $balanceType): Money
    {
        if ($balanceType->equals(BalanceType::TRUST)) {
            $balanceAmount = $wallet->trust_balance->add($wallet->reserve_balance);
        }
        if ($balanceType->equals(BalanceType::MERCHANT)) {
            $balanceAmount = $wallet->merchant_balance;
        }
        if ($balanceType->equals(BalanceType::COMMISSION)) {
            $balanceAmount = $wallet->commission_balance;
        }
        if ($balanceType->equals(BalanceType::TEAMLEADER)) {
            $balanceAmount = $wallet->teamleader_balance;
        }

        return $balanceAmount;
    }

    public function getWalletStats(Wallet $wallet): WalletStatsValue
    {
        $primaryCurrency = Currency::USDT(); // Равен валюте $wallet
        $secondaryCurrency = Currency::RUB();

        $conversionRate = services()->market()->getBuyPrice($secondaryCurrency);

        $totalAvailableBalances = collect();

        foreach (BalanceType::cases() as $balanceType) {
            $balance = $this->getTotalAvailableBalance($wallet, $balanceType);
            $totalAvailableBalances->put($balanceType->value,
                new BalanceValue($balance, $conversionRate->mul($balance))
            );
        }

        //===

        $lockedForWithdrawalBalances = collect();

        foreach (BalanceType::cases() as $balanceType) {
            $value = Invoice::query()
                ->where('type', InvoiceType::WITHDRAWAL)
                ->where('wallet_id', $wallet->id)
                ->where('status', InvoiceStatus::PENDING)
                ->where('balance_type', $balanceType)
                ->sum('amount');

            $balance = Money::fromUnits($value, $primaryCurrency);

            $lockedForWithdrawalBalances->put($balanceType->value,
                new BalanceValue($balance, $conversionRate->mul($balance))
            );
        }

        //===

        $escrowOrdersQuery = Order::query()
            ->where('status', OrderStatus::PENDING)
            ->whereRelation('paymentDetail', 'user_id', $wallet->user_id)
            ->whereDoesntHave('dispute');

        $escrowOrdersBalance = Money::fromUnits($escrowOrdersQuery->sum('total_profit'), $primaryCurrency);
        $escrowOrdersCount = $escrowOrdersQuery->count();

        //===

        $disputeOrdersQuery = Order::query()
            ->where('status', OrderStatus::PENDING)
            ->whereRelation('paymentDetail', 'user_id', $wallet->user_id)
            ->whereHas('dispute');

        $escrowDisputeBalance = Money::fromUnits($disputeOrdersQuery->sum('total_profit'), $primaryCurrency);
        $escrowDisputeCount = $disputeOrdersQuery->count();

        return new WalletStatsValue(
            base: new BaseValue(
                merchantAmount: $wallet->merchant_balance,
                trustAmount: $wallet->trust_balance,
                trustReserveAmount: $wallet->reserve_balance,
                teamleaderAmount: $wallet->teamleader_balance
            ),
            totalAvailableBalances: $totalAvailableBalances,
            lockedForWithdrawalBalances: $lockedForWithdrawalBalances,
            escrowBalances: new EscrowsValue(
                orders: new EscrowValue(
                    balance: new BalanceValue($escrowOrdersBalance, $conversionRate->mul($escrowOrdersBalance)),
                    count: $escrowOrdersCount
                ),
                disputes: new EscrowValue(
                    balance: new BalanceValue($escrowDisputeBalance, $conversionRate->mul($escrowDisputeBalance)),
                    count: $escrowDisputeCount
                )
            ),
            currency:  new CurrencyValue($primaryCurrency, $secondaryCurrency),
            maxReserveBalance: $wallet->target_reserve_amount ?? Wallet::RESERVE_BALANCE
        );
    }
}
