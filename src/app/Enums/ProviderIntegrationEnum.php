<?php

namespace App\Enums;

use App\Traits\Enumable;

enum ProviderIntegrationEnum: string
{
    use Enumable;

    case GAREX = 'GAREX';
    case ALPHAPAY = 'ALPHAPAY';
    case METHODPAY = 'METHODPAY';
    case X023 = 'X023';
}
