<?php

namespace App\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

interface RequisiteProviderCallbackLogServiceContract
{
    /**
     * Логирует входящий callback от провайдера реквизитов
     */
    public function logRequest(Request $request, string $providerName, array $requestData): string;

    /**
     * Обновляет лог после формирования ответа
     */
    public function updateWithResponse(
        string $requestId,
        JsonResponse $response,
        ?int $orderId = null,
        ?int $merchantId = null,
        ?string $exceptionClass = null,
        ?string $exceptionMessage = null
    ): void;
}
