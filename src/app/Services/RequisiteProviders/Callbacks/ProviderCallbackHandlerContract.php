<?php

namespace App\Services\RequisiteProviders\Callbacks;

use App\Enums\ProviderIntegrationEnum;
use App\Models\ProviderTerminal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface ProviderCallbackHandlerContract
{
    public function integration(): ProviderIntegrationEnum;

    public function handle(Request $request, ProviderTerminal $terminal): JsonResponse;
}

