<?php

namespace App\Services\OrderCallback;

use App\Contracts\CallbackServiceContract;
use App\Models\CallbackLog;
use App\Models\Order;
use App\Models\Payout;
use Illuminate\Support\Facades\Http;

class CallbackService implements CallbackServiceContract
{
    public function sendForOrder(Order $order): void
    {
        if (is_local()) {
            return;
        }

        $order->load(['paymentDetail', 'paymentGateway', 'smsLog', 'merchant', 'dispute']);

        $callback_url = $order->callback_url ?? $order->merchant->callback_url;

        if (! $callback_url) {
            return;
        }

        if ($order->is_h2h) {
            $data = \App\Http\Resources\API\H2H\OrderResource::make($order)->resolve();
        } else {
            $data = \App\Http\Resources\API\Merchant\OrderResource::make($order)->resolve();
        }

        $token = $order->merchant->user->api_access_token;

        $response = Http::withoutVerifying()
            ->withHeader('Access-Token', $token)
            ->acceptJson()
            ->post(
                url: $callback_url,
                data: $data
            );

        try {
            // Логирование колбека
            $callbackLog = new CallbackLog([
                'type' => CallbackLog::TYPE_ORDER,
                'url' => $callback_url,
                'request_data' => $data,
                'response_data' => $response->json() ?: $response->body(),
                'status_code' => $response->status(),
                'is_success' => $response->successful(),
            ]);

            $order->callbackLogs()->save($callbackLog);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function sendForPayout(Payout $payout): void
    {
        $callback_url = $payout->callback_url ?? $payout->payoutGateway->callback_url;

        if (! $callback_url) {
            return;
        }

        $data = \App\Http\Resources\API\PayoutResource::make($payout)->resolve();

        $token = $payout->owner->api_access_token;

        $response = Http::withoutVerifying()
            ->withHeader('Access-Token', $token)
            ->acceptJson()
            ->post(
                url: $callback_url,
                data: $data
            );

        // Логирование колбека
        $callbackLog = new CallbackLog([
            'type' => CallbackLog::TYPE_PAYOUT,
            'url' => $callback_url,
            'request_data' => $data,
            'response_data' => $response->json() ?: $response->body(),
            'status_code' => $response->status(),
            'is_success' => $response->successful(),
        ]);

        $payout->callbackLogs()->save($callbackLog);
    }
}
