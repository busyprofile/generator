<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Resources\TableOrderResource;
use Inertia\Inertia;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Utils\Transaction;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();
        $filtersVariants = $this->getFiltersData();

        $orders = queries()->order()->paginateForAdmin($filters);
        $orders = TableOrderResource::collection($orders);

        return Inertia::render('Support/Order/Index', compact('orders', 'filters', 'filtersVariants'));
    }

    public function acceptOrder(Order $order)
    {
        if ($order->dispute) {
            return redirect()->back()->with('error', 'По заказу открыт спор, подтверждение оплаты невозможно.');
        }

        if ($order->status->equals(OrderStatus::SUCCESS)) {
            return redirect()->back()->with('info', 'Заказ уже успешно оплачен.');
        }

        Transaction::run(function () use ($order) {
            if ($order->status->equals(OrderStatus::FAIL)) {
                services()->order()->reopenFinishedOrder($order->id, OrderSubStatus::WAITING_FOR_PAYMENT);
            }
            services()->order()->finishOrderAsSuccessful($order->id, OrderSubStatus::ACCEPTED_BY_SUPPORT);
        });

        return redirect()->back()->with('message', 'Заказ успешно подтвержден как оплаченный.');
    }
} 