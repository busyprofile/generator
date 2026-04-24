<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\PaymentDetail\StoreRequest;
use App\Http\Requests\PaymentDetail\UpdateRequest;
use App\Http\Resources\PaymentDetailResource;
use App\Http\Resources\PaymentGatewayResource;
use App\Http\Resources\UserDeviceResource;
use App\Models\PaymentDetail;
use App\Models\PaymentGateway;
use App\Models\UserDevice;
use App\Services\Money\Money;
use App\Services\Money\Currency;
use App\Utils\Transaction;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PaymentDetailController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();
        $filtersVariants = $this->getFiltersData();

        $fromArchive = request()->tab === 'archived';

        $paymentDetails = queries()->paymentDetail()->paginateForUser(auth()->user(), $filters, $fromArchive);

        $paymentDetails = PaymentDetailResource::collection($paymentDetails);

        return Inertia::render('PaymentDetail/Index', compact('paymentDetails', 'filters', 'filtersVariants'));
    }

    public function create()
    {
        $paymentGateways = PaymentGatewayResource::collection(queries()->paymentGateway()->getAllActive())->resolve();
        $devices = UserDeviceResource::collection(
            UserDevice::where('user_id', auth()->id())->get()
        )->resolve();

        return Inertia::render('PaymentDetail/Add', compact('paymentGateways', 'devices'));
    }

    public function store(StoreRequest $request)
    {
        // Проверяем принадлежность устройства пользователю
        $device = UserDevice::where('id', $request->user_device_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$device) {
            return;
        }

        Transaction::run(function () use ($request) {
            $paymentDetail = PaymentDetail::create([
                'daily_limit' => Money::fromPrecision($request->daily_limit, Currency::make($request->currency)),
                'user_id' => auth()->id(),
                'currency' => Currency::make($request->currency),
                'last_used_at' => now(),
                'user_device_id' => $request->user_device_id,
            ] + $request->validated());

            $paymentDetail->paymentGateways()->sync($request->payment_gateway_ids);
        });

        return redirect()->route('payment-details.index');
    }

    public function edit(PaymentDetail $paymentDetail)
    {
        Gate::authorize('access-to-payment-detail', $paymentDetail);

        $paymentDetail->load(['user', 'userDevice']);
        $paymentDetail->loadCount(['orders as pending_orders_count' => function ($query) {
            $query->where('status', OrderStatus::PENDING);
        }]);

        $paymentDetail->setAttribute('payment_gateway_ids', $paymentDetail->paymentGateways()->pluck('payment_gateways.id')->toArray());

        $devices = UserDeviceResource::collection(
            UserDevice::where('user_id', $paymentDetail->user_id)->get()
        )->resolve();

        $paymentDetail = PaymentDetailResource::make($paymentDetail)->resolve();

        $paymentGateways = PaymentGatewayResource::collection(queries()->paymentGateway()->getAllActive())->resolve();

        return Inertia::render('PaymentDetail/Edit', compact('paymentDetail', 'paymentGateways', 'devices'));
    }

    public function update(UpdateRequest $request, PaymentDetail $paymentDetail)
    {
        Gate::authorize('access-to-payment-detail', $paymentDetail);

        // Проверяем принадлежность устройства пользователю
        $device = UserDevice::where('id', $request->user_device_id)
            ->where('user_id', $paymentDetail->user_id)
            ->first();

        if (!$device) {
            return;
        }

        // Получаем текущие ID платежных методов
        $currentPaymentGatewayIds = $paymentDetail->paymentGateways()->pluck('payment_gateways.id')->toArray();

        // Проверяем, что все текущие ID присутствуют в новом списке
        $missingIds = array_diff($currentPaymentGatewayIds, $request->payment_gateway_ids);
        if (!empty($missingIds)) {
            return redirect()->back()->withErrors([
                'payment_gateway_ids' => 'Нельзя удалить уже выбранные платежные методы'
            ]);
        }

        Transaction::run(function () use ($paymentDetail, $request) {
            $paymentDetail = PaymentDetail::where('id', $paymentDetail->id)->lockForUpdate()->first();

            $updateData = $request->validated();
            
            // Явно добавляем поля, так как они могут отсутствовать в validated() для не-VIP пользователей
            if ($request->has('unique_amount_percentage')) {
                $updateData['unique_amount_percentage'] = $request->unique_amount_percentage;
            }
            if ($request->has('unique_amount_seconds')) {
                $updateData['unique_amount_seconds'] = $request->unique_amount_seconds;
            }

            $paymentDetail->update([
                    'daily_limit' => Money::fromPrecision($request->daily_limit, $paymentDetail->currency),
                    'min_order_amount' => $request->min_order_amount ? Money::fromPrecision($request->min_order_amount, $paymentDetail->currency) : null,
                    'max_order_amount' => $request->max_order_amount ? Money::fromPrecision($request->max_order_amount, $paymentDetail->currency) : null,
                    'order_interval_minutes' => $request->order_interval_minutes,
                ] + $updateData);

            // Подготавливаем данные для синхронизации с timestamps
            $syncData = collect($request->payment_gateway_ids)->mapWithKeys(function ($id) {
                return [$id => ['created_at' => now(), 'updated_at' => now()]];
            })->all();

            $paymentDetail->paymentGateways()->sync($syncData);
        });
        
        // Перенаправляем обратно на страницу редактирования с сообщением об успехе
        return redirect()->route('payment-details.edit', $paymentDetail->id)
                         ->with('success', 'Реквизиты успешно обновлены');
    }

    public function toggleActive(PaymentDetail $paymentDetail)
    {
        Gate::authorize('access-to-payment-detail', $paymentDetail);

        Transaction::run(function () use ($paymentDetail) {
            $paymentDetail = PaymentDetail::where('id', $paymentDetail->id)->lockForUpdate()->first();

            $paymentDetail->update([
                'is_active' => !$paymentDetail->is_active
            ]);
        });
    }
}
