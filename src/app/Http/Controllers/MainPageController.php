<?php

namespace App\Http\Controllers;

use App\Enums\BalanceType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Inertia\Inertia;

class MainPageController extends Controller
{
    public function merchant()
    {
        $stats = cache()->remember('merchant-main-page-stats-'.auth()->id(), 60 * 5, function () {
            $query = Order::query()
                ->whereRelation('merchant', 'user_id', auth()->id())
                ->where('status', OrderStatus::SUCCESS);

            $totalProfit = Money::fromUnits($query->clone()->sum('merchant_profit'), Currency::USDT());

            $totalWithdrawalAmount = Invoice::query()
                ->whereRelation('wallet', 'user_id', auth()->id())
                ->where('type', InvoiceType::WITHDRAWAL)
                ->where('balance_type', BalanceType::MERCHANT)
                ->where('status', InvoiceStatus::SUCCESS)
                ->sum('amount');
            $totalWithdrawalAmount = Money::fromUnits($totalWithdrawalAmount, Currency::USDT());

            $balance = services()->wallet()->getTotalAvailableBalance(auth()->user()->wallet, BalanceType::MERCHANT);

            $successOrderCount = $query->clone()->count();

            //=====

            // Определяем текущую дату и дату 30 дней назад
            $startDate = now()->subDays(29); // Дата 30 дней назад
            $endDate = now();

            // Запрос для получения суммы доходов по дням
            $earningsByDay = Order::where('status', OrderStatus::SUCCESS)
                ->whereRelation('merchant', 'user_id', auth()->id())
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(merchant_profit) as total_earnings')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Формируем данные для графика
            $labels = [];
            $data = [];

            // Заполняем данные для каждого из последних 30 дней
            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);
                $labels[] = $date->day; // Форматируем дату для отображения
                $data[] = Money::fromUnits($earningsByDay->firstWhere('date', $date->toDateString())->total_earnings ?? 0, Currency::USDT())->toInt();
            }

        return [
                'statistics' => [
                    'totalProfit' => $totalProfit->toBeauty(),
                    'totalWithdrawalAmount' => $totalWithdrawalAmount->toBeauty(),
                    'balance' => $balance->toBeauty(),
                    'successOrderCount' => $successOrderCount,
                ],
                'chart' => [
                    'labels' => $labels,
                    'data' => $data,
                ]
            ];
        });

        return Inertia::render('MainPage/Merchant/Index', $stats);
    }

    public function trader()
    {
        $stats = cache()->remember('trader-main-page-stats-'.auth()->id(), 60 * 5, function () {
            $query = Order::query()
                ->whereRelation('paymentDetail', 'user_id', auth()->id())
                ->where('status', OrderStatus::SUCCESS);

            $totalTurnover = Money::fromUnits($query->clone()->sum('total_profit'), Currency::USDT());
            $totalProfit = Money::fromUnits($query->clone()->sum('trader_profit'), Currency::USDT());

            $balance = services()->wallet()->getTotalAvailableBalance(auth()->user()->wallet, BalanceType::TRUST);

            $successOrderCount = $query->clone()->count();

            //=====

            // Определяем текущую дату и дату 30 дней назад
            $startDate = now()->subDays(29); // Дата 30 дней назад
            $endDate = now();

            // Запрос для получения суммы доходов по дням
            $earningsByDay = Order::where('status', OrderStatus::SUCCESS)
                ->whereRelation('paymentDetail', 'user_id', auth()->id())
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(trader_profit) as total_earnings')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Формируем данные для графика
            $labels = [];
            $data = [];

            // Заполняем данные для каждого из последних 30 дней
            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);
                $labels[] = $date->day; // Форматируем дату для отображения
                $data[] = Money::fromUnits($earningsByDay->firstWhere('date', $date->toDateString())->total_earnings ?? 0, Currency::USDT())->toInt();
            }

            return [
                'statistics' => [
                    'totalTurnover' => $totalTurnover->toBeauty(),
                    'totalProfit' => $totalProfit->toBeauty(),
                    'balance' => $balance->toBeauty(),
                    'successOrderCount' => $successOrderCount,
                ],
                'chart' => [
                    'labels' => $labels,
                    'data' => $data,
                ]
            ];
        });

        return Inertia::render('MainPage/Trader/Index', $stats);
    }

    public function leader()
    {
        $stats = cache()->remember('team-leader-main-page-stats-'.auth()->id(), 60 * 5, function () {
            // Сбор всех типов рефералов
            $promoCodes = \App\Models\PromoCode::where('team_leader_id', auth()->id())->pluck('id');
            
            $usersFromPromoCodes = \App\Models\User::whereIn('promo_code_id', $promoCodes)
                ->pluck('id');
                
            $usersFromRelations = \App\Models\TraderTeamLeaderRelation::where('team_leader_id', auth()->id())
                ->pluck('trader_id');
            
            $usersFromAdditional = \App\Models\User::whereJsonContains('additional_team_leader_ids', auth()->id())
                ->pluck('id');
                
            // Объединяем все ID
            $referralsIds = $usersFromPromoCodes->concat($usersFromRelations)
                ->concat($usersFromAdditional)
                ->unique();
                
            $referralsCount = $referralsIds->count();

            // Получаем статистику по заказам
            $query = \App\Models\Order::query()
                ->where('team_leader_id', auth()->id())
                ->where('status', OrderStatus::SUCCESS);

            // Общий доход тим лидера
            $totalProfit = Money::fromUnits($query->clone()->sum('team_leader_profit'), Currency::USDT());

            // Количество сделок
            $successOrderCount = $query->clone()->count();

            // Получаем процент реферальной системы для данного тим лидера
            $referralRate = auth()->user()->referral_commission_percentage;

            // Данные для графика
            $startDate = now()->subDays(29); // Дата 30 дней назад
            $endDate = now();

            // Запрос для получения суммы доходов по дням
            $earningsByDay = \App\Models\Order::where('status', OrderStatus::SUCCESS)
                ->where('team_leader_id', auth()->id())
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, SUM(team_leader_profit) as total_earnings')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Формируем данные для графика
            $labels = [];
            $data = [];

            // Заполняем данные для каждого из последних 30 дней
            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);
                $labels[] = $date->day; // Форматируем дату для отображения
                $data[] = Money::fromUnits($earningsByDay->firstWhere('date', $date->toDateString())->total_earnings ?? 0, Currency::USDT())->toInt();
            }

            return [
                'statistics' => [
                    'totalProfit' => $totalProfit->toBeauty(),
                    'successOrderCount' => $successOrderCount,
                    'referralsCount' => $referralsCount,
                    'referralRate' => $referralRate,
                ],
                'chart' => [
                    'labels' => $labels,
                    'data' => $data,
                ]
            ];
        });

        // Получаем текущий баланс тим лидера
        $balance = services()->wallet()->getTotalAvailableBalance(auth()->user()->wallet, BalanceType::TEAMLEADER);
        $stats['statistics']['balance'] = $balance->toBeauty();

        return Inertia::render('MainPage/Leader/Index', $stats);
    }

    public function admin()
    {
        // Получаем список мерчантов для фильтра
        $merchants = \App\Models\Merchant::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        // Получаем merchant_id из запроса, если он есть
        $merchantId = request()->get('merchant_id');
        $dateFrom = request()->get('date_from');
        $dateTo = request()->get('date_to');

        // Даты по умолчанию (30 дней)
        // $startDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : now()->subDays(29)->startOfDay();
        // Дата начала отсчёта - 2025-08-01
        $startDate = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->startOfDay() : \Carbon\Carbon::parse('2025-08-01')->startOfDay();
        $endDate = $dateTo ? \Carbon\Carbon::parse($dateTo)->endOfDay() : now()->endOfDay();

        $stats = [ // убираю кэширование для фильтрации по датам
            // ...
        ];
        $query = Order::query()
            ->where('status', OrderStatus::SUCCESS)
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        $totalTurnover = Money::fromUnits($query->clone()->sum('total_profit'), Currency::USDT());
        $totalProfit = Money::fromUnits($query->clone()->sum('service_profit'), Currency::USDT());
        $successOrderQuery = Order::query()
            ->where('status', OrderStatus::SUCCESS)
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($merchantId) {
            $successOrderQuery->where('merchant_id', $merchantId);
        }
        $successOrderCount = $successOrderQuery->count();
        $failedOrderQuery = Order::query()
            ->where('status', OrderStatus::FAIL)
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($merchantId) {
            $failedOrderQuery->where('merchant_id', $merchantId);
        }
        $failedOrderCount = $failedOrderQuery->count();
        $totalOrderCount = $successOrderCount + $failedOrderCount;
        $conversionRate = $totalOrderCount > 0
            ? round(($successOrderCount / $totalOrderCount) * 100, 2)
            : 0;
        // Формируем данные для графика доходов по дням
        $earningsByDayQuery = Order::where('status', OrderStatus::SUCCESS)
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($merchantId) {
            $earningsByDayQuery->where('merchant_id', $merchantId);
        }
        $earningsByDay = $earningsByDayQuery
            ->selectRaw('DATE(created_at) as date, SUM(service_profit) as total_earnings')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        $labels = [];
        $data = [];
        $days = $startDate->diffInDays($endDate) + 1;
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->format('d.m');
            $data[] = Money::fromUnits($earningsByDay->firstWhere('date', $date->toDateString())->total_earnings ?? 0, Currency::USDT())->toInt();
        }
        // График конверсии по дням
        $conversionData = [];
        $successOrdersByDayQuery = Order::where('status', OrderStatus::SUCCESS)
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($merchantId) {
            $successOrdersByDayQuery->where('merchant_id', $merchantId);
        }
        $successOrdersByDay = $successOrdersByDayQuery
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $failedOrdersByDayQuery = Order::where('status', OrderStatus::FAIL)
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($merchantId) {
            $failedOrdersByDayQuery->where('merchant_id', $merchantId);
        }
        $failedOrdersByDay = $failedOrdersByDayQuery
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            $successCount = $successOrdersByDay[$date] ?? 0;
            $failedCount = $failedOrdersByDay[$date] ?? 0;
            $totalCount = $successCount + $failedCount;
            $conversionData[] = $totalCount > 0
                ? round(($successCount / $totalCount) * 100, 2)
                : 0;
        }
        // График конверсии по часам (24 часа)
        $hourlyConversionData = [];
        $hourlyLabels = [];
        $hourlyStartDate = now()->subHours(23);
        $hourlyEndDate = now();
        $successOrdersByHourQuery = Order::where('status', OrderStatus::SUCCESS)
            ->whereBetween('created_at', [$hourlyStartDate, $hourlyEndDate]);
        if ($merchantId) {
            $successOrdersByHourQuery->where('merchant_id', $merchantId);
        }
        $successOrdersByHour = $successOrdersByHourQuery
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour');
        $failedOrdersByHourQuery = Order::where('status', OrderStatus::FAIL)
            ->whereBetween('created_at', [$hourlyStartDate, $hourlyEndDate]);
        if ($merchantId) {
            $failedOrdersByHourQuery->where('merchant_id', $merchantId);
        }
        $failedOrdersByHour = $failedOrdersByHourQuery
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour');
        for ($i = 0; $i < 24; $i++) {
            $hour = ($hourlyStartDate->copy()->addHours($i))->hour;
            $hourlyLabels[] = $hour;
            $successCount = $successOrdersByHour[$hour] ?? 0;
            $failedCount = $failedOrdersByHour[$hour] ?? 0;
            $totalCount = $successCount + $failedCount;
            $hourlyConversionData[] = $totalCount > 0
                ? round(($successCount / $totalCount) * 100, 2)
                : 0;
        }
        // График доходов по часам за выбранный день
        $hourlyEarningsLabels = [];
        $hourlyEarningsData = [];
        if ($dateFrom && $dateTo && $dateFrom === $dateTo) {
            $hourlyEarningsQuery = Order::where('status', OrderStatus::SUCCESS)
                ->whereDate('created_at', $dateFrom);
            if ($merchantId) {
                $hourlyEarningsQuery->where('merchant_id', $merchantId);
            }
            $hourlyEarnings = $hourlyEarningsQuery
                ->selectRaw('HOUR(created_at) as hour, SUM(service_profit) as total_earnings')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
            for ($i = 0; $i < 24; $i++) {
                $hourlyEarningsLabels[] = sprintf('%02d:00', $i);
                $hourlyEarningsData[] = Money::fromUnits($hourlyEarnings->firstWhere('hour', $i)->total_earnings ?? 0, Currency::USDT())->toInt();
            }
        }
        return Inertia::render('MainPage/Admin/Index', [
            'merchants' => $merchants,
            'statistics' => [
                'totalTurnover' => $totalTurnover->toBeauty(),
                'totalProfit' => $totalProfit->toBeauty(),
                'totalOrderCount' => $totalOrderCount,
                'successOrderCount' => $successOrderCount,
                'failedOrderCount' => $failedOrderCount,
                'conversionRate' => $conversionRate . '%',
            ],
            'chart' => [
                'labels' => $labels,
                'data' => $data,
            ],
            'conversionChart' => [
                'labels' => $labels,
                'data' => $conversionData,
            ],
            'hourlyConversionChart' => [
                'labels' => $hourlyLabels,
                'data' => $hourlyConversionData,
            ],
            'hourlyEarningsChart' => [
                'labels' => $hourlyEarningsLabels,
                'data' => $hourlyEarningsData,
            ],
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
