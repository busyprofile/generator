<?php

namespace App\Queries\Eloquent;

use App\Models\RequisiteProviderCallbackLog;
use App\ObjectValues\TableFilters\TableFiltersValue;
use App\Queries\Interfaces\ProviderCallbackLogQueries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProviderCallbackLogQueriesEloquent implements ProviderCallbackLogQueries
{
    /**
     * Получить пагинированный список колбек-логов провайдеров для админки
     *
     * @param TableFiltersValue $filters
     * @return LengthAwarePaginator
     */
    public function paginateForAdmin(TableFiltersValue $filters): LengthAwarePaginator
    {
        return RequisiteProviderCallbackLog::query()
            ->leftJoin('orders', 'orders.id', '=', 'requisite_provider_callback_logs.order_id')
            ->leftJoin('provider_terminals', 'provider_terminals.id', '=', 'orders.provider_terminal_id')
            ->leftJoin('providers', 'providers.id', '=', 'orders.provider_id')
            ->select(
                'requisite_provider_callback_logs.*',
                'orders.provider_id',
                'orders.provider_terminal_id',
                'provider_terminals.name as provider_terminal_name',
                'provider_terminals.uuid as provider_terminal_uuid',
                'providers.name as provider_real_name',
            )
            ->when($filters->provider, function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->where('orders.provider_id', $filters->provider)
                        ->orWhere('requisite_provider_callback_logs.provider_name', 'LIKE', '%' . $filters->provider . '%');
                });
            })
            ->when($filters->providerTerminalId, function ($query) use ($filters) {
                $query->where('orders.provider_terminal_id', $filters->providerTerminalId);
            })
            ->when($filters->statusCode, function ($query) use ($filters) {
                $query->where('requisite_provider_callback_logs.status_code', $filters->statusCode);
            })
            ->orderByDesc('requisite_provider_callback_logs.id')
            ->paginate(request()->per_page ?? 20);
    }
}
