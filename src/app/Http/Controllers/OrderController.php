<?php

namespace App\Http\Controllers;

use App\Enums\BalanceType;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Http\Resources\OrderResource;
use App\Http\Resources\TableOrderResource;
use App\Models\Order;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Utils\Transaction;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();
        $filtersVariants = $this->getFiltersData();

        $orders = queries()->order()->paginateForUser(auth()->user(), $filters);
        $orders = TableOrderResource::collection($orders);

        return Inertia::render('Order/Index', compact('orders', 'filters', 'filtersVariants'));
    }

    public function show(Order $order)
    {
        $order->load([
                'trader:id,name,email',
                'smsLog:id,sender,message,created_at,order_id',
                'paymentGateway:id,name,code,logo,currency',
                'paymentDetail:id,detail,detail_type,name,currency,created_at',
                'merchant:id,name',
                'merchant.merchantTeamLeaders:id,email',
                'teamLeader:id,name,email',
                'additionalProfits.teamLeader:id,name,email',
            ]);
        $order->loadExists('dispute');

        // Логирование перед передачей в ресурс
        Log::info('OrderController@show - Order merchant data:', [
            'order_id' => $order->id,
            'merchant_exists' => !is_null($order->merchant),
            'merchant_id' => $order->merchant ? $order->merchant->id : null,
            'merchant_name' => $order->merchant ? $order->merchant->name : null,
            'merchant_relation_loaded' => $order->relationLoaded('merchant'),
            'merchant_team_leaders_on_merchant_loaded' => $order->merchant ? $order->merchant->relationLoaded('merchantTeamLeaders') : false,
            'merchant_team_leaders_count' => ($order->merchant && $order->merchant->relationLoaded('merchantTeamLeaders')) ? $order->merchant->merchantTeamLeaders->count() : 0,
            'merchant_team_leaders_data' => ($order->merchant && $order->merchant->relationLoaded('merchantTeamLeaders')) ? $order->merchant->merchantTeamLeaders->toArray() : [],
            'trader_team_leaders' => $order->trader ? $order->trader->teamLeaders()->get()->toArray() : [],
            'order_additional_profits' => $order->additionalProfits->toArray(),
        ]);

        $orderResource = OrderResource::make($order);

        return response()->success(['order' => $orderResource]);
    }

    public function acceptOrder(Order $order)
    {
        Gate::authorize('access-to-order', $order);

        if ($order->dispute) {
            return;
        }

        if ($order->status->equals(OrderStatus::SUCCESS)) {
            return;
        }

        if (!$order->trader || !$order->trader->wallet) {
            return redirect()->back()->with('error', 'Партнерские ордера не поддерживают эту операцию.');
        }

        $balance = services()->wallet()->getTotalAvailableBalance(
            wallet: $order->trader->wallet,
            balanceType: BalanceType::TRUST,
        );

        if ($balance->lessThan($order->trader_paid_for_order) && $order->status->equals(OrderStatus::FAIL)) {
            return redirect()->back()->with('error', 'Не достаточно средств на балансе.');
        }

        Transaction::run(function () use ($order) {
            if ($order->status->equals(OrderStatus::FAIL)) {
                services()->order()->reopenFinishedOrder($order->id, OrderSubStatus::WAITING_FOR_PAYMENT);
            }

            services()->order()->finishOrderAsSuccessful($order->id, OrderSubStatus::ACCEPTED);
        });
    }
}
