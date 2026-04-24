<?php

namespace App\Models\ValueObjects\Settings;

class CurrencyPriceParserSettings
{
    public function __construct(
        public ?int $amount,
        public ?int $payment_method,
        public ?int $ad_quantity,
    )
    {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
