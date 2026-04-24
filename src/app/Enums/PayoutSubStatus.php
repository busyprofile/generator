<?php

namespace App\Enums;

use App\Traits\Enumable;

enum PayoutSubStatus: string
{
    use Enumable;

    case PROCESSING_BY_TRADER = 'processing_by_trader';
    case PROCESSING_BY_ADMINISTRATOR = 'processing_by_administrator';
    case FULLY_COMPLETED = 'fully_completed';
}
