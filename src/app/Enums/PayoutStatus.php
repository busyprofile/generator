<?php

namespace App\Enums;

use App\Traits\Enumable;

enum PayoutStatus: string
{
    use Enumable;

    case SUCCESS = 'success';
    case FAIL = 'fail';
    case PENDING = 'pending';
}
