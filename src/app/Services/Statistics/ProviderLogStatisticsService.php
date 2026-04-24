<?php

namespace App\Services\Statistics;

use App\Contracts\ProviderLogStatisticsServiceContract;
use App\Models\RequisiteProviderLog;
use Illuminate\Support\Facades\DB;

class ProviderLogStatisticsService implements ProviderLogStatisticsServiceContract
{
    /**
     * Получает статистику за сегодня и за все время
     */
    public function getStatistics(): array
    {
        $today = now()->toDateString();

        // Статистика за сегодня
        $todayStats = RequisiteProviderLog::query()
            ->whereDate('created_at', $today)
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
            ])
            ->first();

        // Общая статистика за все время
        $totalStats = RequisiteProviderLog::query()
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
            ])
            ->first();

        // Статистика по провайдер терминалам за сегодня
        $byTerminalToday = RequisiteProviderLog::query()
            ->whereDate('requisite_provider_logs.created_at', $today)
            ->whereNotNull('provider_terminal_id')
            ->join('provider_terminals', 'requisite_provider_logs.provider_terminal_id', '=', 'provider_terminals.id')
            ->select([
                'provider_terminal_id',
                'provider_terminals.name as terminal_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed'),
            ])
            ->groupBy('provider_terminal_id', 'provider_terminals.name')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = $item->terminal_name . ' (ID: ' . $item->provider_terminal_id . ')';
                return [$key => [
                    'total' => $item->total,
                    'successful' => $item->successful,
                    'failed' => $item->failed,
                ]];
            })
            ->toArray();

        // Статистика по провайдер терминалам за все время
        $byTerminalTotal = RequisiteProviderLog::query()
            ->whereNotNull('provider_terminal_id')
            ->join('provider_terminals', 'requisite_provider_logs.provider_terminal_id', '=', 'provider_terminals.id')
            ->select([
                'provider_terminal_id',
                'provider_terminals.name as terminal_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed'),
            ])
            ->groupBy('provider_terminal_id', 'provider_terminals.name')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = $item->terminal_name . ' (ID: ' . $item->provider_terminal_id . ')';
                return [$key => [
                    'total' => $item->total,
                    'successful' => $item->successful,
                    'failed' => $item->failed,
                ]];
            })
            ->toArray();

        return [
            'successToday' => (int) ($todayStats->successful ?? 0),
            'failedToday' => (int) ($todayStats->failed ?? 0),
            'totalToday' => (int) ($todayStats->total ?? 0),
            'avgResponseTimeToday' => round($todayStats->avg_response_time ?? 0, 2),
            'successTotal' => (int) ($totalStats->successful ?? 0),
            'failedTotal' => (int) ($totalStats->failed ?? 0),
            'totalTotal' => (int) ($totalStats->total ?? 0),
            'avgResponseTimeTotal' => round($totalStats->avg_response_time ?? 0, 2),
            'byTerminalToday' => $byTerminalToday,
            'byTerminalTotal' => $byTerminalTotal,
        ];
    }
}
