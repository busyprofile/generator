<?php

namespace App\Contracts;

use App\Models\ValueObjects\Settings\CurrencyPriceParserSettings;
use App\Models\ValueObjects\Settings\PrimeTimeSettings;
use App\Services\Money\Currency;

interface SettingsServiceContract
{
    public function getPrimeTimeBonus(): PrimeTimeSettings;

    public function updatePrimeTimeBonus(
        string $starts,
        string $ends,
        float $rate,
    ): void;

    public function getCurrencyPriceParser(
        Currency $currency,
    ): CurrencyPriceParserSettings;

    public function updateCurrencyPriceParser(
        Currency $currency,
        CurrencyPriceParserSettings $settings,
    ): void;

    public function getSupportLink(): ?string;

    public function getFundsOnHoldTime(): int;

    public function updateFundsOnHoldTime(int $minutes);

    public function getMaxPendingDisputes(): int;

    public function updateMaxPendingDisputes(int $value): void;

    public function getMaxRejectedDisputes(): array;

    public function updateMaxRejectedDisputes(int $count, int $period): void;

    public function updateSupportLink(string $link): void;

    public function getDepositLink(): ?string;

    public function updateDepositLink(string $link): void;
    public function getPlatformWallet(): ?string;

    public function updatePlatformWallet(string $wallet): void;

    public function getWhitelistedFinanceUserIds(): ?string;

    public function updateWhitelistedFinanceUserIds(string $ids): void;

    public function createAll(): void;
}
