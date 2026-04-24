<?php

namespace App\Enums;

use App\Traits\Enumable;

enum MarketEnum: string
{
    use Enumable;

    case BYBIT = 'bybit';
    case GARANTEX = 'garantex';
    case RAPIRA = 'rapira';
    case RAPIRA_RATES = 'rapira_rates';
    case RAPIRA_TOP1_PLUS10 = 'rapira_top1_plus10';
}
