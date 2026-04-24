<?php

namespace App\Services\Settings;

use App\Contracts\SettingsServiceContract;
use App\Exceptions\SettingsException;
use App\Models\Setting;
use App\Models\ValueObjects\Settings\CurrencyPriceParserSettings;
use App\Models\ValueObjects\Settings\PrimeTimeSettings;
use App\Services\Money\Currency;

class SettingsService implements SettingsServiceContract
{
    const PRIME_TIME_BONUS_STARTS = "prime_time_bonus_starts";
    const PRIME_TIME_BONUS_ENDS = "prime_time_bonus_ends";
    const PRIME_TIME_BONUS_RATE = "prime_time_bonus_rate";
    const CURRENCY_PRICE_PARSER_SETTINGS = "currency_price_parser_settings";
    const SUPPORT_LINK = "support_link";
    const PLATFORM_WALLET = "platform_wallet";
    const FUNDS_ON_HOLD_TIME = "funds_on_hold_time";
    const MAX_PENDING_DISPUTES = "max_pending_disputes";
    const MAX_REJECTED_DISPUTES = "max_rejected_disputes";
    const DEPOSIT_LINK = "deposit_link";
    const MAX_CONSECUTIVE_FAILED_ORDERS = "max_consecutive_failed_orders";
    const WHITELISTED_FINANCE_USER_IDS = "whitelisted_finance_user_ids";

    protected $settings = null;

    public function getPrimeTimeBonus(): PrimeTimeSettings
    {
        return new PrimeTimeSettings(
            starts: $this->getParam(self::PRIME_TIME_BONUS_STARTS),
            ends: $this->getParam(self::PRIME_TIME_BONUS_ENDS),
            rate: $this->getParam(self::PRIME_TIME_BONUS_RATE),
        );
    }

    public function updatePrimeTimeBonus(
        string $starts,
        string $ends,
        float $rate,
    ): void {
        $this->updateParam(self::PRIME_TIME_BONUS_STARTS, $starts);
        $this->updateParam(self::PRIME_TIME_BONUS_ENDS, $ends);
        $this->updateParam(self::PRIME_TIME_BONUS_RATE, round($rate, 2));
    }

    public function getCurrencyPriceParser(
        Currency $currency,
    ): CurrencyPriceParserSettings {
        $param = json_decode(
            $this->getParam(self::CURRENCY_PRICE_PARSER_SETTINGS),
            true,
        );

        return new CurrencyPriceParserSettings(...$param[$currency->getCode()]);
    }

    public function updateCurrencyPriceParser(
        Currency $currency,
        CurrencyPriceParserSettings $settings,
    ): void {
        $param = json_decode(
            $this->getParam(self::CURRENCY_PRICE_PARSER_SETTINGS),
            true,
        );

        $param[$currency->getCode()] = $settings->toArray();

        $this->updateParam(
            self::CURRENCY_PRICE_PARSER_SETTINGS,
            json_encode($param),
        );
    }

    public function getSupportLink(): ?string
    {
        return $this->getParam(self::SUPPORT_LINK);
    }

    public function updateSupportLink(string $link): void
    {
        $this->updateParam(self::SUPPORT_LINK, $link);
    }

    public function getPlatformWallet(): ?string
    {
        return $this->getParam(self::PLATFORM_WALLET);
    }

    public function updatePlatformWallet(string $wallet): void
    {
        $this->updateParam(self::PLATFORM_WALLET, $wallet);
    }
    public function getFundsOnHoldTime(): int
    {
        return $this->getParam(self::FUNDS_ON_HOLD_TIME);
    }

    public function updateFundsOnHoldTime(int $minutes): void
    {
        $this->updateParam(self::FUNDS_ON_HOLD_TIME, $minutes);
    }

    public function getMaxPendingDisputes(): int
    {
        return $this->getParam(self::MAX_PENDING_DISPUTES);
    }

    public function updateMaxPendingDisputes(int $value): void
    {
        $this->updateParam(self::MAX_PENDING_DISPUTES, $value);
    }

    public function getMaxRejectedDisputes(): array
    {
        $value = $this->getParam(self::MAX_REJECTED_DISPUTES);
        if (!$value) {
            return ["count" => 0, "period" => 0];
        }
        return json_decode($value, true);
    }

    public function updateMaxRejectedDisputes(int $count, int $period): void
    {
        $this->updateParam(
            self::MAX_REJECTED_DISPUTES,
            json_encode(["count" => $count, "period" => $period]),
        );
    }

    public function getMaxConsecutiveFailedOrders(): array
    {
        $value = $this->getParam(self::MAX_CONSECUTIVE_FAILED_ORDERS);
        if (!$value) {
            return ["count" => 0, "period" => 0];
        }
        return json_decode($value, true);
    }

    public function updateMaxConsecutiveFailedOrders(
        int $count,
        int $period,
    ): void {
        $this->updateParam(
            self::MAX_CONSECUTIVE_FAILED_ORDERS,
            json_encode(["count" => $count, "period" => $period]),
        );
    }

    public function getWhitelistedFinanceUserIds(): ?string
    {
        return $this->getParam(self::WHITELISTED_FINANCE_USER_IDS);
    }

    public function updateWhitelistedFinanceUserIds(string $ids): void
    {
        $this->updateParam(self::WHITELISTED_FINANCE_USER_IDS, $ids);
    }

    public function getDepositLink(): ?string
    {
        return $this->getParam(self::DEPOSIT_LINK);
    }

    public function updateDepositLink(string $link): void
    {
        $this->updateParam(self::DEPOSIT_LINK, $link);
    }

    public function createAll(): void
    {
        Setting::firstOrCreate([
            "key" => self::PRIME_TIME_BONUS_STARTS,
            "value" => "00:00",
        ]);
        Setting::firstOrCreate([
            "key" => self::PRIME_TIME_BONUS_ENDS,
            "value" => "07:00",
        ]);
        Setting::firstOrCreate([
            "key" => self::PRIME_TIME_BONUS_RATE,
            "value" => "1.2",
        ]);
        Setting::firstOrCreate([
            "key" => self::SUPPORT_LINK,
            "value" => null,
        ]);
        Setting::firstOrCreate([
            "key" => self::FUNDS_ON_HOLD_TIME,
            "value" => 1440,
        ]);

        Setting::firstOrCreate([
            "key" => self::PLATFORM_WALLET, // Добавил создание ключа кошелька
            "value" => "",
        ]);

        Setting::firstOrCreate([
            "key" => self::MAX_PENDING_DISPUTES,
            "value" => 5,
        ]);

        Setting::firstOrCreate([
            "key" => self::MAX_REJECTED_DISPUTES,
            "value" => json_encode(["count" => 10, "period" => 60]),
        ]);

        Setting::firstOrCreate([
            "key" => self::DEPOSIT_LINK,
            "value" => "https://test.com/pay.php",
        ]);

        Setting::firstOrCreate([
            "key" => self::MAX_CONSECUTIVE_FAILED_ORDERS,
            "value" => json_encode(["count" => 5, "period" => 60]),
        ]);

        Setting::firstOrCreate([
            "key" => self::WHITELISTED_FINANCE_USER_IDS,
            "value" => "",
        ]);

        $currenciesJson = $this->getParam(self::CURRENCY_PRICE_PARSER_SETTINGS);
        if (!empty($currenciesJson)) {
            $currencies = json_decode($currenciesJson, true);
        } else {
            $currencies = [];
        }

        Currency::getAll()->each(function (Currency $currency) use (
            &$currencies,
        ) {
            if (empty($currencies[$currency->getCode()])) {
                $currencies[
                    $currency->getCode()
                ] = (new CurrencyPriceParserSettings(
                    ...[
                        "amount" => null,
                        "payment_method" => null,
                        "ad_quantity" => 3,
                    ],
                ))->toArray();
            }
        });

        Setting::updateOrCreate(
            ["key" => self::CURRENCY_PRICE_PARSER_SETTINGS],
            [
                "key" => self::CURRENCY_PRICE_PARSER_SETTINGS,
                "value" => json_encode($currencies),
            ],
        );

        cache()->put("app-settings", Setting::all());
    }

    protected function getParam(string $key): mixed
    {
        if (!$this->settings) {
            $settings = cache()->get("app-settings");

            if (!$settings) {
                $settings = cache()->rememberForever(
                    "app-settings",
                    function () {
                        return Setting::all();
                    },
                );
            }

            $this->settings = $settings;
        }

        $setting = $this->settings->where("key", $key)->first();
        return $setting ? $setting->value : null;
    }

    protected function updateParam(string $key, mixed $value): bool
    {
        $res = Setting::where("key", $key)->update(["value" => $value]);

        cache()->put("app-settings", Setting::all());
        $this->settings = null;

        return (bool) $res;
    }
}
