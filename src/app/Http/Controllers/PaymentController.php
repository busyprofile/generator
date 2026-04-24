<?php

namespace App\Http\Controllers;

use App\Contracts\OrderServiceContract;
use App\DTO\Order\CreateOrderDTO;
use App\Exceptions\OrderException;
use App\Http\Requests\Payment\StoreRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentGatewayResource;
use App\Models\Merchant;
use App\Services\Money\Currency;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

// Для экспорта
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MerchantPaymentsExport;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();
        $filtersVariants = $this->getFiltersData();

        $orders = queries()->order()->paginateForMerchant(auth()->user(), $filters);
        $orders = OrderResource::collection($orders);

        return Inertia::render('Payment/Index', compact('orders', 'filters', 'filtersVariants'));
    }

    public function create()
    {
        $paymentGateways = PaymentGatewayResource::collection(queries()->paymentGateway()->getAllActive())->resolve();

        $currencies = Currency::getAll()->transform(function ($currency) {
            return [
                'code' => strtoupper($currency->getCode()),
                'name' => strtoupper($currency->getCode()) . ' - ' . $currency->getName(),
            ];
        })->toArray();

        $merchants = Merchant::query()
            ->where('user_id', auth()->user()->id)
            ->whereNotNull('validated_at')
            ->whereNull('banned_at')
            ->where('active', true)
            ->orderByDesc('id')
            ->get()
            ->transform(function (Merchant $merchant) {
                $data['id'] = $merchant->id;
                $data['name'] = $merchant->name;

                return $data;
            });

        return Inertia::render('Payment/Add', compact('paymentGateways', 'currencies', 'merchants'));
    }

    public function store(StoreRequest $request)
    {
        $merchant = Merchant::where('id', $request->merchant_id)->first();

        Gate::authorize('access-to-merchant', $merchant);

        try {
            make(OrderServiceContract::class)->create(
                CreateOrderDTO::makeFromRequest(
                    $request->all() + ['merchant' => $merchant],
                )
            );
        } catch (OrderException $e) {
            return redirect()->back()->with('message', $e->getMessage());
        }

        return redirect()->route('payments.index');
    }

    public function exportMerchantPayments(Request $request)
    {
        $validated = $request->validate([
            'startDate' => 'required|date_format:Y-m-d',
            'endDate' => 'required|date_format:Y-m-d|after_or_equal:startDate',
        ]);

        $startDate = $validated['startDate'];
        $endDate = $validated['endDate'];

        // Проверка прав доступа, если это необходимо (например, убедиться что пользователь - мерчант)
        // Gate::authorize('export-merchant-payments'); 
        // или просто положиться на мидлвар группы маршрутов

        $fileName = 'payments-' . Carbon::now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new MerchantPaymentsExport(auth()->user(), $startDate, $endDate), $fileName);
    }
}
