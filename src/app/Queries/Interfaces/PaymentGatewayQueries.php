<?php

namespace App\Queries\Interfaces;

use App\Models\PaymentGateway;
use App\ObjectValues\TableFilters\TableFiltersValue;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PaymentGatewayQueries
{
    /**
     * @param bool|null $transgranOnly Если true - только трансгран, false - только не трансгран, null - все подряд
     * @return Collection<int, PaymentGateway>
     */
    public function getAllActive(?bool $transgranOnly = null): Collection;

    public function paginateForAdmin(TableFiltersValue $filters): LengthAwarePaginator;

    public function getByCode(string $code, ?bool $transgranOnly = null): ?PaymentGateway;

    /**
     * @param bool|null $transgranOnly Если true - только трансгран, false - только не трансгран, null - все подряд
     * @return Collection<int, PaymentGateway>
     */
    public function getByCodesForOrderCreate(array $codes, Money $amount, ?bool $transgranOnly = null): Collection;

    /**
     * @param bool|null $transgranOnly Если true - только трансгран, false - только не трансгран, null - все подряд
     * @return Collection<int, PaymentGateway>
     */
    public function getByCurrencyForOrderCreate(Currency $currency, Money $amount, ?bool $transgranOnly = null): Collection;
}
