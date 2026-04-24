<?php

namespace App\Queries\Interfaces;

use App\ObjectValues\TableFilters\TableFiltersValue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProviderCallbackLogQueries
{
    /**
     * Получить пагинированный список колбек-логов провайдеров для админки
     *
     * @param TableFiltersValue $filters
     * @return LengthAwarePaginator
     */
    public function paginateForAdmin(TableFiltersValue $filters): LengthAwarePaginator;
}
