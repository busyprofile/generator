<?php

namespace App\Enums;

use App\Traits\Enumable;

enum DetailType: string
{
    use Enumable;

    case CARD = 'card';
    case PHONE = 'phone';
    case ACCOUNT_NUMBER = 'account_number';
    case SIM = 'sim';
}
