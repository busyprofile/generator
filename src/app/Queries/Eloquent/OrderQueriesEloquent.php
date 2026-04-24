<?php

namespace App\Queries\Eloquent;

use App\Enums\OrderStatus;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\UserDevice;
use App\ObjectValues\TableFilters\TableFiltersValue;
use App\Queries\Interfaces\OrderQueries;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Sms\CurrencyConverterService;

class OrderQueriesEloquent implements OrderQueries
{
    public function findPending(Money $amount, User $user, PaymentGateway $paymentGateway, UserDevice $device): ?Order
    {
        // Сначала пробуем найти ордер с точным совпадением валюты и суммы
        $exactOrder = Order::where('amount', $amount->toUnits())
            ->whereDoesntHave('dispute')
            ->where('status', OrderStatus::PENDING)
            ->where('currency', $amount->getCurrency()->getCode())
            ->where('trader_id', $user->id)
            ->whereRelation('paymentDetail', 'user_device_id', $device->id)
            ->where('payment_gateway_id', $paymentGateway->id)
            ->first();

        if ($exactOrder) {
            return $exactOrder;
        }

        // Если точного совпадения нет, ищем с конвертацией валют
        return $this->findPendingWithCurrencyConversion($amount, $user, $paymentGateway, $device);
    }

    /**
     * Поиск ордера с конвертацией валют используя зафиксированный курс из заказа
     */
    private function findPendingWithCurrencyConversion(Money $smsAmount, User $user, PaymentGateway $paymentGateway, UserDevice $device): ?Order
    {
        $converter = new CurrencyConverterService();
        
        // Получаем все pending ордера этого пользователя с этим шлюзом
        $pendingOrders = Order::whereDoesntHave('dispute')
            ->where('status', OrderStatus::PENDING)
            ->where('trader_id', $user->id)
            ->whereRelation('paymentDetail', 'user_device_id', $device->id)
            ->where('payment_gateway_id', $paymentGateway->id)
            ->get();

        foreach ($pendingOrders as $order) {
            try {
                // Если валюты одинаковые, но сумма не совпадает - пропускаем
                if ($order->currency->getCode() === $smsAmount->getCurrency()->getCode()) {
                    continue;
                }

                // Получаем зафиксированный курс из заказа
                $orderConversionPrice = $order->conversion_price; // Курс RUB к USDT на момент создания заказа
                
                // Получаем текущий курс целевой валюты (SMS) к USDT
                $smsTargetRate = services()->market()->getBuyPrice($smsAmount->getCurrency());
                
                // Создаем массив фиксированных курсов для конвертации
                $fixedRates = [
                    $order->currency->getCode() => (float) $orderConversionPrice->toPrecision(),
                    $smsAmount->getCurrency()->getCode() => (float) $smsTargetRate->toPrecision(),
                ];

                // Проверяем соответствие суммы с допуском ±1.2% используя зафиксированный курс
                if ($converter->isWithinToleranceWithFixedRate($order->amount, $smsAmount, 1.2, $fixedRates)) {
                    \Log::info('Найден ордер с конвертацией валют (зафиксированный курс)', [
                        'order_id' => $order->id,
                        'order_amount' => $order->amount->toBeauty(),
                        'order_currency' => $order->currency->getCode(),
                        'order_conversion_price' => $orderConversionPrice->toBeauty(),
                        'sms_amount' => $smsAmount->toBeauty(),
                        'sms_currency' => $smsAmount->getCurrency()->getCode(),
                        'sms_target_rate' => $smsTargetRate->toBeauty(),
                        'fixed_rates' => $fixedRates,
                    ]);
                    
                    return $order;
                }
            } catch (\Exception $e) {
                // Логируем ошибку конвертации, но продолжаем поиск
                \Log::warning('Ошибка конвертации валют при поиске ордера', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return null;
    }

    public function paginateForAdmin(TableFiltersValue $filters): LengthAwarePaginator
    {
        return Order::query()
            ->withoutGlobalScopes() // Убираем глобальный скоуп, чтобы видеть партнёрские заказы
            ->with([
                'trader:id,email,name',
                'paymentGateway:id,logo,name',
                'paymentDetail:id,detail,detail_type,name,user_device_id',
                'paymentDetail.userDevice:id,name',
            ])
            ->select(['id', 'uuid', 'amount', 'currency', 'total_profit', 'status', 'created_at', 'payment_gateway_id', 'payment_detail_id', 'trader_id'])
            ->when(! empty($filters->orderStatuses), function ($query) use ($filters) {
                $query->whereIn('status', $filters->orderStatuses);
            })
            ->when($filters->startDate, function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters->startDate);
            })
            ->when($filters->endDate, function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters->endDate);
            })
            ->when($filters->externalID, function ($query) use ($filters) {
                $query->where('external_id', 'LIKE', '%' . $filters->externalID . '%');
            })
            ->when($filters->uuid, function ($query) use ($filters) {
                $query->where('uuid', 'LIKE', '%' . $filters->uuid . '%');
            })
            ->when($filters->amount, function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $amount = Money::fromPrecision($filters->amount, Currency::USDT())->toUnits();
                    $query->where('amount', 'LIKE', '%' . $amount . '%');
                    $query->orWhere('total_profit', 'LIKE', '%' . $amount . '%');
                });
            })
            ->when($filters->paymentDetail, function ($query) use ($filters) {
                $query->whereHas('paymentDetail', function ($subQuery) use ($filters) {
                    $subQuery->where('detail', 'LIKE', '%' . $filters->paymentDetail . '%');
                });
            })
            ->when($filters->detailTypes && count($filters->detailTypes) > 0, function ($query) use ($filters) {
                $query->whereHas('paymentDetail', function ($subQuery) use ($filters) {
                    $subQuery->whereIn('detail_type', $filters->detailTypes);
                });
            })
            ->when($filters->paymentGateway, function ($query) use ($filters) {
                $query->whereHas('paymentGateway', function ($subQuery) use ($filters) {
                    $subQuery->where('name', 'LIKE', '%' . $filters->paymentGateway . '%')
                        ->orWhere('code', 'LIKE', '%' . $filters->paymentGateway . '%');
                });
            })
            ->when($filters->user, function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    // Ищем по трейдеру напрямую (для партнёрских заказов) или через paymentDetail.user
                    $query->whereHas('trader', function ($subQuery) use ($filters) {
                        $subQuery->where('name', 'LIKE', '%' . $filters->user . '%')
                                 ->orWhere('email', 'LIKE', '%' . $filters->user . '%');
                    })
                    ->orWhereHas('paymentDetail.user', function ($subQuery) use ($filters) {
                        $subQuery->where('name', 'LIKE', '%' . $filters->user . '%')
                                 ->orWhere('email', 'LIKE', '%' . $filters->user . '%');
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
    }

    public function paginateForUser(User $user, TableFiltersValue $filters): LengthAwarePaginator
    {
        return Order::query()
            ->whereRelation('paymentDetail', 'user_id', $user->id)
            ->with([
                'trader:id,email',
                'paymentGateway:id,logo,name',
                'paymentDetail:id,detail,detail_type,name,user_device_id',
                'paymentDetail.userDevice:id,name',
            ])
            ->whereNotNull('payment_detail_id')
            ->when(! empty($filters->orderStatuses), function ($query) use ($filters) {
                $query->whereIn('status', $filters->orderStatuses);
            })
            ->when($filters->startDate, function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters->startDate);
            })
            ->when($filters->endDate, function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters->endDate);
            })
            ->when($filters->uuid, function ($query) use ($filters) {
                $query->where('uuid', 'LIKE', '%' . $filters->uuid . '%');
            })
            ->when($filters->amount, function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $amount = Money::fromPrecision($filters->amount, Currency::USDT())->toUnits();
                    $query->where('amount', 'LIKE', $amount);
                    $query->orWhere('total_profit', 'LIKE', $amount);
                });
            })
            ->when($filters->paymentDetail, function ($query) use ($filters) {
                $query->whereRelation('paymentDetail', 'detail', 'LIKE', '%' . $filters->paymentDetail . '%');
            })
            ->when($filters->detailTypes && count($filters->detailTypes) > 0, function ($query) use ($filters) {
                $query->whereRelation('paymentDetail', function ($subQuery) use ($filters) {
                    $subQuery->whereIn('detail_type', $filters->detailTypes);
                });
            })
            ->when($filters->paymentGateway, function ($query) use ($filters) {
                $query->whereRelation('paymentGateway', function ($subQuery) use ($filters) {
                    $subQuery->where('name', 'LIKE', '%' . $filters->paymentGateway . '%')
                        ->orWhere('code', 'LIKE', '%' . $filters->paymentGateway . '%');
                });
            })
            ->select(['id', 'uuid', 'amount', 'currency', 'total_profit', 'status', 'created_at', 'payment_gateway_id', 'payment_detail_id', 'trader_id'])
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
    }

    public function paginateForMerchant(User $user, TableFiltersValue $filters): LengthAwarePaginator
    {
        return Order::query()
            ->withoutGlobalScopes()
            ->whereNotNull('payment_detail_id')
            ->with(['merchant'])
            ->whereRelation('merchant', 'user_id', $user->id)
            ->when(! empty($filters->orderStatuses), function ($query) use ($filters) {
                $query->whereIn('status', $filters->orderStatuses);
            })
            ->when($filters->externalID, function ($query) use ($filters) {
                $query->where('external_id', 'LIKE', '%' . $filters->externalID . '%');
            })
            ->when($filters->uuid, function ($query) use ($filters) {
                $query->where('uuid', 'LIKE', '%' . $filters->uuid . '%');
            })
            ->when($filters->amount, function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $amount = Money::fromPrecision($filters->amount, Currency::USDT())->toUnits();
                    $query->where('amount', 'LIKE', $amount);
                    $query->orWhere('total_profit', 'LIKE', $amount);
                });
            })
            ->when($filters->detailTypes && count($filters->detailTypes) > 0, function ($query) use ($filters) {
                $query->whereRelation('paymentDetail', function ($subQuery) use ($filters) {
                    $subQuery->whereIn('detail_type', $filters->detailTypes);
                });
            })
            ->when($filters->paymentGateway, function ($query) use ($filters) {
                $query->whereRelation('paymentGateway', function ($subQuery) use ($filters) {
                    $subQuery->where('name', 'LIKE', '%' . $filters->paymentGateway . '%')
                        ->orWhere('code', 'LIKE', '%' . $filters->paymentGateway . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
    }


    /**
     * @return Collection<int, Dispute>
     */
    public function getForAdminApiDisputeCreate(): Collection
    {
        return Order::query()
            ->where('status', OrderStatus::FAIL)
            ->whereDoesntHave('dispute')
            ->whereDate('created_at', '>=', now()->subDay())
            ->orderByDesc('id')
            ->get(['id', 'amount', 'currency']);
    }
}
