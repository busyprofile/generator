<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderCallbackLogResource;
use Inertia\Inertia;

class ProviderCallbackLogController extends Controller
{
    /**
     * Отображает список колбек-логов провайдеров
     */
    public function index()
    {
        $filters = $this->getTableFilters();

        $logs = queries()->providerCallbackLog()->paginateForAdmin($filters);

        return Inertia::render('ProviderCallbackLogs/Index', [
            'logs' => ProviderCallbackLogResource::collection($logs),
            'filters' => $filters,
        ]);
    }
}
