<?php

namespace App\Queries\Eloquent;

use App\Models\RequisiteProviderLog;
use App\ObjectValues\TableFilters\TableFiltersValue;
use App\Queries\Interfaces\ProviderLogQueries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProviderLogQueriesEloquent implements ProviderLogQueries
{
    /**
     * Получить пагинированный список логов провайдеров для админки
     *
     * @param TableFiltersValue $filters
     * @return LengthAwarePaginator
     */
    public function paginateForAdmin(TableFiltersValue $filters): LengthAwarePaginator
    {
        return RequisiteProviderLog::query()
            ->with(['provider:id,name', 'providerTerminal:id,name'])
            ->when($filters->provider, function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->where('provider_id', $filters->provider)
                        ->orWhereRelation('provider', 'name', 'LIKE', '%' . $filters->provider . '%')
                        ->orWhere('provider_name', 'LIKE', '%' . $filters->provider . '%');
                });
            })
            ->when($filters->providerTerminalId, function ($query) use ($filters) {
                $query->where('provider_terminal_id', $filters->providerTerminalId);
            })
            ->when(!is_null($filters->status), function ($query) use ($filters) {
                if ($filters->status === 'success') {
                    $query->where('success', true);
                } elseif ($filters->status === 'fail') {
                    $query->where('success', false);
                }
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 20);
    }
}
