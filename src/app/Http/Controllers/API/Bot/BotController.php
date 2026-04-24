<?php

namespace App\Http\Controllers\API\Bot;

use App\Exceptions\DisputeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\H2H\Dispute\StoreRequest;
use App\Http\Resources\API\H2H\DisputeResource;
use App\Http\Resources\API\H2H\OrderResource;
use App\Http\Resources\PaymentDetailResource;
use App\Http\Resources\UserResource;
use App\Models\Order;
use App\Models\User;
use App\Enums\DisputeCancelReason;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function index(Order $order)
    {
        $order->load(['paymentDetail', 'dispute', 'paymentGateway']);

        $cascadePaymentDetailId = env('CASCADE_PAYMENT_DETAIL_ID', '1');
        $responseUser = null;

        if ($order->payment_detail_id == $cascadePaymentDetailId && $order->trader_id) {
            $responseUser = User::find($order->trader_id);
        } else {
            $responseUser = $order->paymentDetail?->user;
        }

        return response()->success([
            'order' => OrderResource::make($order)->resolve(),
            'detail' => PaymentDetailResource::make($order->paymentDetail)->resolve(),
            'user' => $responseUser ? UserResource::make($responseUser)->resolve() : null,
            'dispute' => $order->dispute ? DisputeResource::make($order->dispute)->resolve() : null
        ]);
    }

    public function indexExternal(string $merchant_id, string $external_id)
    {
        $order = Order::query()
            ->with(['paymentDetail', 'dispute', 'paymentGateway'])
            ->whereRelation('merchant', 'uuid', $merchant_id)
            ->where('external_id', $external_id)
            ->firstOrFail();

        $cascadePaymentDetailId = env('CASCADE_PAYMENT_DETAIL_ID', '1');
        $responseUser = null;

        if ($order->payment_detail_id == $cascadePaymentDetailId && $order->trader_id) {
            $responseUser = User::find($order->trader_id);
        } else {
            $responseUser = $order->paymentDetail?->user;
        }

        return response()->success([
            'order' => OrderResource::make($order)->resolve(),
            'detail' => PaymentDetailResource::make($order->paymentDetail)->resolve(),
            'user' => $responseUser ? UserResource::make($responseUser)->resolve() : null,
            'dispute' => $order->dispute ? DisputeResource::make($order->dispute)->resolve() : null
        ]);
    }

    public function storeDispute(StoreRequest $request, Order $order)
    {
        try {
            $dispute = services()->dispute()->create($order->id, $request->receipt);

            return response()->success(
                DisputeResource::make($dispute)
            );
        } catch (DisputeException $e) {
            return response()->failWithMessage($e->getMessage());
        }
    }

    public function acceptDispute(Order $order)
    {
        if (! $order->dispute) {
            return response()->failWithMessage('Dispute not found.');
        }

        try {
            services()->dispute()->accept($order->dispute->id);

            return response()->success();
        } catch (DisputeException $e) {
            return response()->failWithMessage($e->getMessage());
        }
    }

    public function cancelDispute(Request $request, Order $order)
    {
        $request->validate([
            'cancel_reason' => ['required', 'string', 'in:requires_bank_statement,requires_video_proof,wrong_payment_refund_required,incorrect_amount_received'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $order->dispute) {
            return response()->failWithMessage('Dispute not found.');
        }

        try {
            services()->dispute()->cancel($order->dispute->id, $request->cancel_reason, $request->comment);

            return response()->success();
        } catch (DisputeException $e) {
            return response()->failWithMessage($e->getMessage());
        }
    }

    public function getDisputeCancelReasons()
    {
        $reasons = [];
        foreach (DisputeCancelReason::cases() as $reason) {
            $reasons[] = [
                'value' => $reason->value,
                'label' => $reason->label(),
            ];
        }

        return response()->success([
            'cancel_reasons' => $reasons,
        ]);
    }
}
