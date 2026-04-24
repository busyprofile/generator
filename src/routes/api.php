<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['middleware' => ['api-access-token']], function () {
    //common
    Route::get('payment-gateways', [\App\Http\Controllers\API\PaymentGatewayController::class, 'index']);
    Route::get('currencies', [\App\Http\Controllers\API\CurrencyController::class, 'index']);

    Route::group(['prefix' => 'payout'], function () {
        Route::get('offers', [\App\Http\Controllers\API\Payout\PayoutOfferController::class, 'index']);
        Route::get('/{payout:uuid}', [\App\Http\Controllers\API\Payout\PayoutController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\API\Payout\PayoutController::class, 'store']);
    });

    Route::group(['prefix' => 'merchant'], function () {
        Route::get('order/{order:uuid}', [\App\Http\Controllers\API\Merchant\OrderController::class, 'show']);
        Route::get('order/{merchant_id}/{external_id}', [\App\Http\Controllers\API\Merchant\OrderController::class, 'showByExternal']);
        Route::post('order', [\App\Http\Controllers\API\Merchant\OrderController::class, 'store'])->name('api.order');
    });

    Route::group(['prefix' => 'h2h'], function () {
        Route::get('order/{order:uuid}', [\App\Http\Controllers\API\H2H\OrderController::class, 'show']);
        Route::get('order/{merchant_id}/{external_id}', [\App\Http\Controllers\API\H2H\OrderController::class, 'showByExternal']);
        Route::post('order', [\App\Http\Controllers\API\H2H\OrderController::class, 'store']);
        Route::patch('order/{order:uuid}/cancel', [\App\Http\Controllers\API\H2H\OrderController::class, 'cancel']);
        Route::patch('order/{order:uuid}/finish', [\App\Http\Controllers\API\H2H\OrderController::class, 'finish']);

        //TODO
        //Route::patch('order/{order:uuid}/confirm-paid', [\App\Http\Controllers\API\H2H\OrderController::class, 'cancel']);

        Route::post('order/{order:uuid}/dispute', [\App\Http\Controllers\API\H2H\DisputeController::class, 'store'])->name('api.dispute');
        Route::get('order/{order:uuid}/dispute', [\App\Http\Controllers\API\H2H\DisputeController::class, 'show']);
        Route::get('dispute/cancel-reasons', [\App\Http\Controllers\API\H2H\DisputeController::class, 'getDisputeCancelReasons']);
    });

    Route::group(['prefix' => 'wallet'], function () {
        Route::get('balance', [\App\Http\Controllers\API\Merchant\WalletController::class, 'balance']);
        Route::post('withdraw', [\App\Http\Controllers\API\Merchant\WalletController::class, 'withdraw']);
    });
});

Route::group(['prefix' => 'bot', 'middleware' => ['api-bot-access-token']], function () {
    Route::get('order/{order:uuid}', [\App\Http\Controllers\API\Bot\BotController::class, 'index']);
    Route::get('order/{merchant_id}/{external_id}', [\App\Http\Controllers\API\Bot\BotController::class, 'indexExternal']);
    Route::post('order/{order:uuid}/dispute', [\App\Http\Controllers\API\Bot\BotController::class, 'storeDispute']);
    Route::post('order/{order:uuid}/dispute/accept', [\App\Http\Controllers\API\Bot\BotController::class, 'acceptDispute']);
    Route::post('order/{order:uuid}/dispute/cancel', [\App\Http\Controllers\API\Bot\BotController::class, 'cancelDispute']);
    Route::get('dispute/cancel-reasons', [\App\Http\Controllers\API\Bot\BotController::class, 'getDisputeCancelReasons']);
    
    // Новые эндпоинты для управления пользователями
    Route::post('user/register', [\App\Http\Controllers\API\Bot\UserController::class, 'register']);
    Route::post('user/add-balance', [\App\Http\Controllers\API\Bot\UserController::class, 'addBalance']);
});

Route::group(['prefix' => 'deposit', 'middleware' => ['api-deposits-access-token']], function () {
    Route::post('webhook', [\App\Http\Controllers\API\Deposit\DepositController::class, 'webhook']);
});

Route::group(['prefix' => 'withdraw', 'middleware' => ['api-withdrawals-access-token']], function () {
    Route::post('webhook', [\App\Http\Controllers\API\Withdraw\WithdrawController::class, 'webhook']);
});

Route::group(['prefix' => 'app', 'middleware' => ['device-access-token']], function () {
    Route::post('sms', [\App\Http\Controllers\API\APP\SmsController::class, 'store'])->middleware('idempotency_for_app');
    Route::get('state', [\App\Http\Controllers\API\APP\StateController::class, 'index']);
    Route::post('device/connect', [\App\Http\Controllers\API\APP\DeviceController::class, 'connect']);
});

// Универсальный роут для callback от провайдеров реквизитов
// URL: POST /api/callback/{provider_terminal_uuid}
Route::post('/callback/{uuid}', [\App\Http\Controllers\API\ProviderCallbackController::class, 'handle'])
    ->name('api.callback.provider')
    ->where('uuid', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
