<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderLogResource;
use App\Services\Statistics\ProviderLogStatisticsService;
use Inertia\Inertia;

class ProviderLogController extends Controller
{
    public function index(ProviderLogStatisticsService $statisticsService)
    {
        $filters = $this->getTableFilters();
        $filtersVariants = $this->getFiltersData();

        $logs = queries()->providerLog()->paginateForAdmin($filters);
        
        // Получаем статистику из сервиса
        $statistics = $statisticsService->getStatistics();

        return Inertia::render('ProviderLogs/Index', [
            'logs' => ProviderLogResource::collection($logs),
            'filters' => $filters,
            'filtersVariants' => $filtersVariants,
            'successTotal' => $statistics['successTotal'],
            'successToday' => $statistics['successToday'],
            'failedTotal' => $statistics['failedTotal'],
            'failedToday' => $statistics['failedToday'],
            'totalTotal' => $statistics['totalTotal'],
            'totalToday' => $statistics['totalToday'],
            'avgResponseTimeTotal' => $statistics['avgResponseTimeTotal'],
            'avgResponseTimeToday' => $statistics['avgResponseTimeToday'],
            'byTerminalToday' => $statistics['byTerminalToday'],
            'byTerminalTotal' => $statistics['byTerminalTotal'],
        ]);
    }
}
