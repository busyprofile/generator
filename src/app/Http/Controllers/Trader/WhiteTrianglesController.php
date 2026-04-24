<?php

namespace App\Http\Controllers\Trader;

use App\Enums\BalanceType;
use App\Enums\DetailType;
use App\Enums\OrderStatus;
use App\Enums\OrderSubStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentDetailResource;
use App\Http\Resources\PaymentGatewayResource;
use App\Models\Order;
use App\Models\PaymentDetail;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Utils\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WhiteTrianglesController extends Controller
{
    public function index(Request $request)
    {
        // Базовый запрос — все реквизиты трейдера
        $baseQuery = PaymentDetail::where('user_id', auth()->id())
            ->whereNull('archived_at');

        // Все ID для загрузки сделок (без фильтра)
        $allDetailIds = (clone $baseQuery)->pluck('id');

        // Запрос реквизитов с фильтрами
        $detailQuery = (clone $baseQuery)->with(['paymentGateways']);

        if ($request->filled('filter_name')) {
            $detailQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->filter_name . '%')
                  ->orWhere('detail', 'like', '%' . $request->filter_name . '%');
            });
        }

        if ($request->filled('filter_gateway_id')) {
            $detailQuery->whereHas('paymentGateways', function ($q) use ($request) {
                $q->where('payment_gateways.id', (int) $request->filter_gateway_id);
            });
        }

        $paginated = $detailQuery->orderByDesc('created_at')->paginate(15);

        // Считаем суммы сделок только для отфильтрованных реквизитов
        $filteredIds = collect($paginated->items())->pluck('id');
        $dealsAmounts = Order::where('status', OrderStatus::SUCCESS)
            ->whereIn('payment_detail_id', $filteredIds)
            ->selectRaw('payment_detail_id, SUM(amount) as total_amount')
            ->groupBy('payment_detail_id')
            ->pluck('total_amount', 'payment_detail_id');

        $detailsWithAmounts = collect($paginated->items())->map(function (PaymentDetail $detail) use ($dealsAmounts) {
            $detail->setAttribute('deals_amount', $dealsAmounts[$detail->id] ?? 0);
            return $detail;
        });

        $paymentDetailsData = PaymentDetailResource::collection($detailsWithAmounts)->resolve();

        // Последние 150 сделок по всем реквизитам трейдера
        $orders = Order::with(['paymentGateway', 'paymentDetail'])
            ->whereIn('payment_detail_id', $allDetailIds)
            ->orderByDesc('created_at')
            ->limit(150)
            ->get();

        $ordersData = OrderResource::collection($orders)->resolve();

        $paymentGateways = PaymentGatewayResource::collection(queries()->paymentGateway()->getAllActive())->resolve();

        return Inertia::render('WhiteTriangles/Index', [
            'paymentDetails' => $paymentDetailsData,
            'pagination'     => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
            'orders'         => $ordersData,
            'paymentGateways' => $paymentGateways,
            'filters'        => [
                'filter_name'       => $request->filter_name ?? '',
                'filter_gateway_id' => $request->filled('filter_gateway_id') ? (int) $request->filter_gateway_id : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'detail_type'        => ['required', 'string', 'in:card,phone'],
            'payment_gateway_id' => ['required', 'integer', 'exists:payment_gateways,id'],
            'detail'             => ['required', 'string'],
            'name'               => ['required', 'string', 'min:3', 'max:100'],
            'daily_limit'        => ['required', 'integer', 'min:1', 'max:100000000'],
            'min_order_amount'   => ['nullable', 'integer', 'min:0'],
            'max_order_amount'   => ['nullable', 'integer', 'min:0'],
        ]);

        $currency = Currency::RUB();

        $paymentDetail = PaymentDetail::create([
            'name'                      => $request->name,
            'detail'                    => preg_replace('~\D+~', '', $request->detail),
            'detail_type'               => DetailType::from($request->detail_type),
            'initials'                  => $request->name,
            'is_active'                 => false,
            'daily_limit'               => Money::fromPrecision($request->daily_limit, $currency),
            'current_daily_limit'       => Money::fromPrecision(0, $currency),
            'max_pending_orders_quantity' => 1,
            'min_order_amount'          => $request->min_order_amount !== null ? Money::fromPrecision($request->min_order_amount, $currency) : null,
            'max_order_amount'          => $request->max_order_amount !== null ? Money::fromPrecision($request->max_order_amount, $currency) : null,
            'currency'                  => $currency,
            'user_id'                   => auth()->id(),
            'last_used_at'              => now(),
        ]);

        $paymentDetail->paymentGateways()->sync([$request->payment_gateway_id]);

        return back();
    }

    public function updateLimits(Request $request, PaymentDetail $paymentDetail)
    {
        if ($paymentDetail->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'daily_limit'      => ['required', 'integer', 'min:1', 'max:100000000'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_order_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $currency = $paymentDetail->currency;

        $paymentDetail->update([
            'daily_limit'      => Money::fromPrecision($request->daily_limit, $currency),
            'min_order_amount' => $request->min_order_amount !== null
                ? Money::fromPrecision($request->min_order_amount, $currency)
                : null,
            'max_order_amount' => $request->max_order_amount !== null
                ? Money::fromPrecision($request->max_order_amount, $currency)
                : null,
        ]);

        return back();
    }

    public function toggleActive(PaymentDetail $paymentDetail)
    {
        if ($paymentDetail->user_id !== auth()->id()) {
            abort(403);
        }

        $paymentDetail->update(['is_active' => !$paymentDetail->is_active]);

        return response()->json(['is_active' => $paymentDetail->fresh()->is_active]);
    }

    public function confirm(Order $order)
    {
        // Проверяем принадлежность сделки текущему трейдеру
        $traderDetailIds = PaymentDetail::where('user_id', auth()->id())->pluck('id');

        if (!$traderDetailIds->contains($order->payment_detail_id)) {
            abort(403);
        }

        Gate::authorize('access-to-order', $order);

        if ($order->dispute) {
            return back()->with('error', 'Нельзя подтвердить сделку со спором.');
        }

        if ($order->status->equals(OrderStatus::SUCCESS)) {
            return back()->with('info', 'Сделка уже подтверждена.');
        }

        if (!$order->trader || !$order->trader->wallet) {
            return back()->with('error', 'Партнёрские ордера не поддерживают эту операцию.');
        }

        $balance = services()->wallet()->getTotalAvailableBalance(
            wallet: $order->trader->wallet,
            balanceType: BalanceType::TRUST,
        );

        if ($balance->lessThan($order->trader_paid_for_order) && $order->status->equals(OrderStatus::FAIL)) {
            return back()->with('error', 'Недостаточно средств на балансе.');
        }

        Transaction::run(function () use ($order) {
            if ($order->status->equals(OrderStatus::FAIL)) {
                services()->order()->reopenFinishedOrder($order->id, OrderSubStatus::WAITING_FOR_PAYMENT);
            }
            services()->order()->finishOrderAsSuccessful($order->id, OrderSubStatus::ACCEPTED);
        });

        return back();
    }
}
