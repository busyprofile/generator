<?php

namespace App\Queries\Eloquent;

use App\Models\PaymentGateway;
use App\ObjectValues\TableFilters\TableFiltersValue;
use App\Queries\Interfaces\PaymentGatewayQueries;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PaymentGatewayQueriesEloquent implements PaymentGatewayQueries
{
    /**
     * @param bool|null $transgranOnly Если true - только трансгран, false - только не трансгран, null - все подряд
     * @return Collection<int, PaymentGateway>
     */
    public function getAllActive(?bool $transgranOnly = null): Collection
    {
        return PaymentGateway::query()
            ->active()
            ->when(!is_null($transgranOnly), function ($query) use ($transgranOnly) {
                return $query->where('is_transgran', $transgranOnly);
            })
            ->get();
    }

    public function paginateForAdmin(TableFiltersValue $filters): LengthAwarePaginator
    {
        return PaymentGateway::query()
            ->when($filters->search, function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . $filters->search . '%');
                    $query->orWhere('code', 'like', '%' . $filters->search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);
    }

    public function getByCode(string $code, ?bool $transgranOnly = null): ?PaymentGateway
    {
        $query = PaymentGateway::query()->where('code', $code);

        if (!is_null($transgranOnly)) {
            $query->where('is_transgran', $transgranOnly);
        }

        return $query->first();
    }

    /**
     * @return Collection<int, PaymentGateway>
     */
    public function getByCodesForOrderCreate(array $codes, Money $amount, ?bool $transgranOnly = null): Collection
    {
        return PaymentGateway::query()
            ->where(function ($query) use ($amount) {
                $query->where('min_limit', '<=', intval($amount->toBeauty()));
                $query->where('max_limit', '>=', intval($amount->toBeauty()));
            })
            ->whereIn('code', $codes)
            ->active()
            ->when(!is_null($transgranOnly), function ($query) use ($transgranOnly) {
                return $query->where('is_transgran', $transgranOnly);
            })
            ->get();
    }

    /**
     * @return Collection<int, PaymentGateway>
     */
    public function getByCurrencyForOrderCreate(Currency $currency, Money $amount, ?bool $transgranOnly = null): Collection
    {
        return PaymentGateway::query()
            ->where(function ($query) use ($amount) {
                $query->where('min_limit', '<=', intval($amount->toBeauty()));
                $query->where('max_limit', '>=', intval($amount->toBeauty()));
            })
            ->where('currency', $currency->getCode())
            ->active()
            ->when(!is_null($transgranOnly), function ($query) use ($transgranOnly) {
                return $query->where('is_transgran', $transgranOnly);
            })
            ->get();
    }
}
