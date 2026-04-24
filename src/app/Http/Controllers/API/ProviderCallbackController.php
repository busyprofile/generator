<?php

namespace App\Http\Controllers\API;

use App\Contracts\RequisiteProviderCallbackLogServiceContract;
use App\Enums\ProviderIntegrationEnum;
use App\Http\Controllers\Controller;
use App\Models\ProviderTerminal;
use App\Services\RequisiteProviders\Callbacks\AlphaPayCallbackHandler;
use App\Services\RequisiteProviders\Callbacks\GarexCallbackHandler;
use App\Services\RequisiteProviders\Callbacks\MethodPayCallbackHandler;
use App\Services\RequisiteProviders\Callbacks\X023CallbackHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Универсальный контроллер для обработки callback'ов от всех провайдеров
 * 
 * URL: POST /api/callback/{provider_terminal_uuid}
 * 
 * Автоматически определяет тип интеграции по UUID терминала
 * и вызывает соответствующий обработчик.
 */
class ProviderCallbackController extends Controller
{
    public function __construct(
        protected RequisiteProviderCallbackLogServiceContract $callbackLogService
    ) {}

    /**
     * Обработка callback'а от провайдера
     */
    public function handle(Request $request, string $uuid): JsonResponse
    {
        Log::info('[ProviderCallback] Received callback', [
            'uuid' => $uuid,
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // Находим терминал по UUID
        $terminal = ProviderTerminal::with('provider')->where('uuid', $uuid)->first();

        if (!$terminal) {
            Log::warning('[ProviderCallback] Terminal not found', ['uuid' => $uuid]);
            $this->logCallback($request, $uuid, null, null, 'Terminal not found');
            return response()->json(['error' => 'Terminal not found'], 404);
        }

        if (!$terminal->provider) {
            Log::warning('[ProviderCallback] Provider not found for terminal', [
                'uuid' => $uuid,
                'terminal_id' => $terminal->id,
            ]);
            $this->logCallback($request, $uuid, $terminal->id, null, 'Provider not found');
            return response()->json(['error' => 'Provider not found'], 404);
        }

        $integration = $terminal->provider->integration;

        Log::info('[ProviderCallback] Processing callback for integration', [
            'uuid' => $uuid,
            'terminal_id' => $terminal->id,
            'provider_id' => $terminal->provider->id,
            'integration' => $integration->value,
        ]);

        // Делегируем обработку конкретному обработчику по интеграции (каждый провайдер — в отдельном файле)
        $handler = $this->resolveHandler($integration);

        if ($handler === null) {
            Log::warning('[ProviderCallback] Unknown integration type', [
                'integration' => $terminal->provider->integration->value ?? 'null',
            ]);

            $this->logCallbackDirect($request, $uuid, $terminal->id, null, 'Unknown integration', 400);
            return response()->json(['error' => 'Unknown integration type'], 400);
        }

        return $handler->handle($request, $terminal);
    }

    private function resolveHandler(ProviderIntegrationEnum $integration): ?\App\Services\RequisiteProviders\Callbacks\ProviderCallbackHandlerContract
    {
        return match ($integration) {
            ProviderIntegrationEnum::GAREX => app(GarexCallbackHandler::class),
            ProviderIntegrationEnum::METHODPAY => app(MethodPayCallbackHandler::class),
            ProviderIntegrationEnum::ALPHAPAY => app(AlphaPayCallbackHandler::class),
            ProviderIntegrationEnum::X023 => app(X023CallbackHandler::class),
            default => null,
        };
    }

    private function logCallbackDirect(
        Request $request,
        string $uuid,
        ?int $terminalId,
        ?int $orderId,
        ?string $error,
        int $statusCode
    ): void {
        try {
            $this->callbackLogService->log(
                providerName: 'provider_terminal:' . $uuid,
                orderId: $orderId,
                requestData: $request->all(),
                responseData: $error ? ['error' => $error] : ['success' => true],
                statusCode: $statusCode,
                providerTerminalId: $terminalId,
            );
        } catch (\Exception $e) {
            Log::warning('[ProviderCallback] Failed to log callback', ['error' => $e->getMessage()]);
        }
    }
}
