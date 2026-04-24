<?php

namespace App\Services\OrderPooling;

use App\Contracts\OrderPoolingServiceContract;
use App\Exceptions\OrderException;
use App\Http\Requests\API\H2H\Order\StoreRequest as H2HRequest;
use App\Http\Requests\API\Merchant\Order\StoreRequest as MerchantRequest;
use App\Http\Resources\API\H2H\OrderResource as H2HOrderResource;
use App\Http\Resources\API\Merchant\OrderResource as MOrderResource;
use App\Jobs\OrderPoolingJob;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Log;

class OrderPoolingService implements OrderPoolingServiceContract
{
    /**
     * Обрабатывает запрос на создание сделки через OrderPooling
     */
    public function processOrderPooling(H2HRequest|MerchantRequest $request): JsonResponse
    {
        $merchant = queries()->merchant()->findByUUID($request->merchant_id);

        // Логируем запрос и получаем request_id
        $requestId = services()->merchantApiLog()->logRequest($request, $merchant, $request->validated());

        Log::info('[OrderPoolingService] Started processing for request_id: ' . $requestId, ['external_id' => $request->external_id, 'merchant_id' => $request->merchant_id]);

        $timeout = (int)$merchant->max_order_wait_time;
        if (request()->header('X-Max-Wait-Ms')) {
            $timeout = (int)request()->header('X-Max-Wait-Ms');
        }

        $timeout = $timeout === 0 ? config('order-pooling.max_wait_time') : $timeout;
        $timeout = $timeout < 1000 ? 1000 : $timeout;
        $timeout = $timeout > config('order-pooling.max_wait_time') ? config('order-pooling.max_wait_time') : $timeout;

        // Ожидание результата
        $maxWaitMs = $timeout;
        Log::info('[OrderPoolingService] Max wait time set to: ' . $maxWaitMs . 'ms for request_id: ' . $requestId);

        $intervalMs = config('order-pooling.poll_interval');
        $waited = 0;
        $processingTimeMs = 0;
        $maxWaitProcessingMs = 3000;

        $jobID = Str::uuid()->toString();
        $createdAt = now()->getTimestampMs();

        Log::info('[OrderPoolingService] Generated JobID: ' . $jobID . ' for request_id: ' . $requestId);

        cache()->put("order:create:$jobID", json_encode([
            'status' => 'queued',
        ]), 60);

        $payload = $request->validated();
        if ($request instanceof H2HRequest) {
            $payload['h2h'] = true;
        }
        OrderPoolingJob::dispatch($jobID, $createdAt, $payload, $maxWaitMs);
        Log::info('[OrderPoolingService] Dispatched OrderPoolingJob with JobID: ' . $jobID, ['payload_keys' => array_keys($payload)]);

        Log::info('[OrderPoolingService] Starting wait loop for JobID: ' . $jobID);
        while ($waited < $maxWaitMs) {
            usleep($intervalMs * 1000);
            $waited += $intervalMs;

            $result = cache()->get("order:create:$jobID");

            if ($result) {
                $data = json_decode($result, true);
                Log::debug('[OrderPoolingService] Polled cache for JobID: ' . $jobID, ['status' => $data['status'] ?? 'unknown', 'waited_ms' => $waited]);

                if (empty($data['status'])) {
                    Log::warning('[OrderPoolingService] Empty status from cache for JobID: ' . $jobID . '. Breaking loop.');
                    break;
                }

                if ($data['status'] === 'queued' && $waited > $maxWaitMs + ($intervalMs * 2)) {
                    cache()->put("order:create:$jobID", json_encode([
                        'status' => 'expired',
                    ]), 60);
                    break;
                }

                if ($data['status'] === 'done') {
                    Log::info('[OrderPoolingService] JobID: ' . $jobID . ' reported DONE.');
                    /**
                     * @var Order $order
                     */
                    $order = Order::withoutGlobalScopes()->find($data['order_id']);
                    
                    if (!$order) {
                        Log::error('[OrderPoolingService] Order not found for JobID: ' . $jobID, ['order_id' => $data['order_id']]);
                        $response = response()->failWithMessage('Ордер не найден после создания');
                        services()->merchantApiLog()->updateWithResponse($merchant, $request->external_id, $requestId, $response);
                        return $response;
                    }

                    if ($request instanceof H2HRequest) {
                        $resource = H2HOrderResource::make($order);
                    } else {
                        $resource = MOrderResource::make($order);
                    }

                    // Обновляем лог с успешным ответом
                    $response = response()->success($resource);
                    services()->merchantApiLog()->updateWithResponse($merchant, $request->external_id, $requestId, $response, $order);

                    return $response;
                } elseif ($data['status'] === 'failed') {
                    Log::error('[OrderPoolingService] JobID: ' . $jobID . ' reported FAILED.', $data['exception'] ?? []);
                    if (empty($data['exception']['class']) || empty($data['exception']['message'])) {
                        $response = response()->failWithMessage('Произошла неизвестная ошибка при обработке запроса');
                        services()->merchantApiLog()->updateWithResponse($merchant, $request->external_id, $requestId, $response);

                        return $response;
                    }

                    if (is_a($data['exception']['class'], OrderException::class, true)) {
                        $response = response()->failWithMessage($data['exception']['message']);
                        services()->merchantApiLog()->updateWithResponse($merchant, $request->external_id, $requestId, $response, null, $data['exception']['class'], $data['exception']['message']);

                        return $response;
                    } elseif (is_a($data['exception']['class'], Throwable::class, true)) {
                        $response = response()->failWithMessage('Произошла ошибка при обработке запроса');
                        services()->merchantApiLog()->updateWithResponse($merchant, $request->external_id, $requestId, $response, null, $data['exception']['class'], $data['exception']['message']);

                        return $response;
                    } else {
                        $response = response()->failWithMessage('Произошла неизвестная ошибка при обработке запроса');
                        services()->merchantApiLog()->updateWithResponse($merchant, $request->external_id, $requestId, $response);

                        return $response;
                    }
                } elseif ($data['status'] === 'expired') {
                    Log::warning('[OrderPoolingService] JobID: ' . $jobID . ' reported EXPIRED.');
                    break;
                } elseif ($data['status'] === 'processing') {
                    $processingTimeMs = $processingTimeMs + $intervalMs;

                    if ($processingTimeMs > $maxWaitProcessingMs) {
                        break;
                    }
                }
            } else {
                Log::debug('[OrderPoolingService] No cache entry for JobID: ' . $jobID . ' after ' . $waited . 'ms. Retrying or timing out soon.');
                if ($maxWaitMs - $waited < $intervalMs * 5) {
                    Log::info('[OrderPoolingService] Nearing timeout for JobID: ' . $jobID, ['waited' => $waited, 'maxWait' => $maxWaitMs]);
                }
            }
        }

        Log::warning('[OrderPoolingService] Wait loop ended for JobID: ' . $jobID . (
            $waited >= $maxWaitMs ? ' by TIMEOUT.' : ' by break or no cache entry after loop.'), ['waited_ms' => $waited, 'max_wait_ms' => $maxWaitMs]);

        $response = response()->failWithMessage('Не удалось обработать запрос вовремя. Повторите попытку позже.', 504);
        services()->merchantApiLog()->updateWithResponse($merchant, $request->external_id, $requestId, $response);

        return $response;
    }
}
