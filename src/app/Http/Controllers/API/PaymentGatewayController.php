<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\PaymentGatewayResource;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function index(Request $request)
    {
        $transgranOnly = null;
        if ($request->has('transgran')) {
            $transgranOnly = filter_var($request->input('transgran'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $paymentGateways = queries()->paymentGateway()->getAllActive($transgranOnly);

        $paymentGateways = PaymentGatewayResource::collection($paymentGateways);

        return response()->success($paymentGateways);
    }
}
