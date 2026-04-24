<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DetailType;
use App\Enums\OrderStatus;
use App\Enums\ProviderIntegrationEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProviderTerminal\StoreRequest;
use App\Http\Requests\Admin\ProviderTerminal\UpdateRequest;
use App\Http\Resources\ProviderTerminalResource;
use App\Models\Provider;
use App\Models\ProviderTerminal;
use App\Services\RequisiteProviders\IntegrationFieldsConfig;
use App\Utils\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProviderTerminalController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'query' => $request->get('query'),
            'integration' => $request->get('integration'),
            'status' => $request->get('status'),
        ];

        $terminalQuery = ProviderTerminal::query()
            ->with('provider')
            ->leftJoin('providers', 'providers.id', '=', 'provider_terminals.provider_id')
            ->leftJoin('wallets', 'wallets.user_id', '=', 'providers.trader_id')
            ->select(
                'provider_terminals.*',
                DB::raw('COALESCE(wallets.trust_balance, 0) as provider_balance_cents')
            );

        if ($filters['query']) {
            $q = $filters['query'];
            $terminalQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhereHas('provider', fn($p) => $p->where('name', 'like', "%{$q}%"));
            });
        }

        if ($filters['integration'] && $filters['integration'] !== 'all') {
            $terminalQuery->whereHas('provider', fn($p) => $p->where('integration', $filters['integration']));
        }

        if ($filters['status'] === 'active') {
            $terminalQuery->where('is_active', true);
        } elseif ($filters['status'] === 'inactive') {
            $terminalQuery->where('is_active', false);
        }

        $logsAll = $this->getLogAggregates();
        $logs24h = $this->getLogAggregates(Carbon::now()->subDay());
        $dealsAll = $this->getOrderAggregates();
        $deals24h = $this->getOrderAggregates(Carbon::now()->subDay());

        $paginated = $terminalQuery->orderByDesc('id')->paginate(request()->per_page ?? 15)->withQueryString();

        $terminals = $paginated->through(function (ProviderTerminal $terminal) use ($logsAll, $logs24h, $dealsAll, $deals24h) {
            $log = $logsAll[$terminal->id] ?? [];
            $log24 = $logs24h[$terminal->id] ?? [];
            $deal = $dealsAll[$terminal->id] ?? [];
            $deal24 = $deals24h[$terminal->id] ?? [];

            return [
                'id' => $terminal->id,
                'uuid' => $terminal->uuid,
                'name' => $terminal->name,
                'provider_id' => $terminal->provider_id,
                'provider_name' => $terminal->provider?->name,
                'provider_integration' => $terminal->provider?->integration?->value,
                'available' => $terminal->is_active,
                'total_requests' => $log['total_requests'] ?? 0,
                'successful_requests' => $log['successful_requests'] ?? 0,
                'success_rate' => $log['success_rate'] ?? 0,
                'avg_response_time' => $log['avg_response_time'] ?? 0,
                'total_requests_24h' => $log24['total_requests'] ?? 0,
                'successful_requests_24h' => $log24['successful_requests'] ?? 0,
                'success_rate_24h' => $log24['success_rate'] ?? 0,
                'avg_response_time_24h' => $log24['avg_response_time'] ?? 0,
                'successful_deals' => $deal['successful_deals'] ?? 0,
                'successful_deals_24h' => $deal24['successful_deals'] ?? 0,
                'total_deals' => $deal['total_deals'] ?? 0,
                'total_deals_24h' => $deal24['total_deals'] ?? 0,
                'deal_conversion_rate' => $deal['deal_conversion_rate'] ?? 0,
                'deal_conversion_rate_24h' => $deal24['deal_conversion_rate'] ?? 0,
                'max_sum' => $terminal->max_sum,
                'trader_balance' => round(($terminal->provider_balance_cents ?? 0) / 100, 2),
                'config' => [
                    'timeout' => data_get($terminal->additional_settings, 'timeout'),
                    'retry_attempts' => data_get($terminal->additional_settings, 'retry_attempts'),
                    'retry_delay_ms' => data_get($terminal->additional_settings, 'retry_delay_ms'),
                    'partner_platform_retry_delay' => data_get($terminal->additional_settings, 'partner_platform_retry_delay'),
                ],
            ];
        });

        $summary = [
            'total_terminals' => $paginated->total(),
            'active_terminals' => ProviderTerminal::where('is_active', true)->count(),
            'total_requests_24h' => collect($logs24h)->sum('total_requests'),
            'success_24h' => collect($logs24h)->sum('successful_requests'),
            'average_success_rate_24h' => round(collect($logs24h)->avg('success_rate'), 2),
        ];

        return Inertia::render('Admin/ProviderTerminals/Index', [
            'terminals' => $terminals,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        // Преобразуем провайдеров в массив с явным строковым значением integration
        $providers = Provider::select('id', 'name', 'integration')->get()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'integration' => $p->integration?->value,
        ]);

        return Inertia::render('Admin/ProviderTerminals/Create', [
            'providers' => $providers,
            'integrations' => collect(ProviderIntegrationEnum::cases())->map(fn($item) => [
                'label' => IntegrationFieldsConfig::getIntegrationName($item),
                'value' => $item->value,
            ])->values(),
            'integrationFields' => IntegrationFieldsConfig::getAllIntegrations(),
        ]);
    }

    public function store(StoreRequest $request)
    {
        try {
            Transaction::run(function () use ($request) {
                $data = $request->validated();
                
                \Log::info('[ProviderTerminal Store] Initial data', ['data' => $data]);
                
                // ВАЖНО: mergeIntegrationFields должен вызваться ДО удаления полей
                $data['additional_settings'] = $this->mergeIntegrationFields($data);
                $data['enabled_detail_types'] = []; // Все типы реквизитов выключены по умолчанию
                $data['is_active'] = false; // Провайдер терминал всегда создаётся неактивным
                $data['uuid'] = Str::uuid()->toString();

                \Log::info('[ProviderTerminal Store] After merge', [
                    'additional_settings' => $data['additional_settings'],
                    'uuid' => $data['uuid']
                ]);

                // Убираем поля, которых нет в fillable модели
                unset($data['integration'], $data['integration_settings'], $data['integration_fields'], $data['retry_delay_ms']);

                \Log::info('[ProviderTerminal Store] Before create', ['data' => $data]);

                $terminal = ProviderTerminal::create($data);
                
                \Log::info('[ProviderTerminal Store] Created successfully', [
                    'id' => $terminal->id,
                    'uuid' => $terminal->uuid,
                    'name' => $terminal->name
                ]);
            });

            return redirect()->route('admin.provider-terminals.index')
                ->with('success', 'Терминал создан');
                
        } catch (\Exception $e) {
            \Log::error('[ProviderTerminal Store] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Ошибка создания терминала: ' . $e->getMessage()]);
        }
    }

    public function show(ProviderTerminal $providerTerminal)
    {
        $providerTerminal->load(['provider', 'merchants']);

        // Получаем все привязанные мерчанты с их статусом
        $attachedMerchantsData = $providerTerminal->merchants
            ->keyBy('id')
            ->map(fn($m) => ['pivot_active' => (bool) $m->pivot->is_active])
            ->toArray();

        // Получаем ВСЕ мерчанты из БД
        $merchants = \App\Models\Merchant::select('id', 'name', 'user_id as owner_id', 'active', 'domain', 'gateway_settings', 'banned_at')
            ->get()
            ->map(function ($merchant) use ($attachedMerchantsData) {
                $isAttached = isset($attachedMerchantsData[$merchant->id]);
                
                // Берём комиссию из gateway_settings (первый элемент)
                $feePercent = 0;
                $gatewaySettings = $merchant->gateway_settings;
                if (is_array($gatewaySettings) && !empty($gatewaySettings)) {
                    $firstSetting = reset($gatewaySettings);
                    $feePercent = $firstSetting['custom_gateway_commission'] ?? 0;
                }
                
                return [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                    'owner' => $merchant->owner_id,
                    'domain' => $merchant->domain ?? '',
                    'active' => (bool) $merchant->active,
                    'banned_at' => $merchant->banned_at,
                    'is_attached' => $isAttached,
                    'pivot_active' => $isAttached ? $attachedMerchantsData[$merchant->id]['pivot_active'] : false,
                    'fee_percent' => $feePercent,
                ];
            });

        // Статистика по заказам для этого терминала
        $terminalId = $providerTerminal->id;
        
        $todayStart = Carbon::today();
        $yesterdayStart = Carbon::yesterday();
        $yesterdayEnd = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        // Сегодня
        $todayStats = DB::table('orders')
            ->where('provider_terminal_id', $terminalId)
            ->where('status', OrderStatus::SUCCESS->value)
            ->where('created_at', '>=', $todayStart)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_profit), 0) as turnover')
            ->first();

        // Вчера
        $yesterdayStats = DB::table('orders')
            ->where('provider_terminal_id', $terminalId)
            ->where('status', OrderStatus::SUCCESS->value)
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_profit), 0) as turnover')
            ->first();

        // Месяц
        $monthStats = DB::table('orders')
            ->where('provider_terminal_id', $terminalId)
            ->where('status', OrderStatus::SUCCESS->value)
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_profit), 0) as turnover')
            ->first();

        // Всего
        $totalStats = DB::table('orders')
            ->where('provider_terminal_id', $terminalId)
            ->where('status', OrderStatus::SUCCESS->value)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total_profit), 0) as turnover')
            ->first();

        $formatMoney = fn($value) => number_format(($value ?? 0) / 100, 2, '.', '');

        $statistics = [
            'currency' => 'usdt',
            'today_turnover' => $formatMoney($todayStats->turnover ?? 0),
            'today_orders_count' => $todayStats->count ?? 0,
            'yesterday_turnover' => $formatMoney($yesterdayStats->turnover ?? 0),
            'yesterday_orders_count' => $yesterdayStats->count ?? 0,
            'month_turnover' => $formatMoney($monthStats->turnover ?? 0),
            'month_orders_count' => $monthStats->count ?? 0,
            'total_turnover' => $formatMoney($totalStats->turnover ?? 0),
            'total_orders_count' => $totalStats->count ?? 0,
        ];

        $orders = $this->getOrdersForTerminal($providerTerminal->id);

        // Получаем поля для интеграции этого терминала
        $integration = $providerTerminal->provider?->integration;
        $integrationFields = $integration 
            ? IntegrationFieldsConfig::getFields($integration) 
            : [];

        return Inertia::render('Admin/ProviderTerminals/Show', [
            'terminal' => (new ProviderTerminalResource($providerTerminal))->resolve(),
            'merchants' => $merchants,
            'detailTypes' => [
                ['label' => 'СБП', 'value' => DetailType::PHONE->value],
                ['label' => 'Карта', 'value' => DetailType::CARD->value],
                ['label' => 'БА', 'value' => DetailType::ACCOUNT_NUMBER->value],
            ],
            'statistics' => $statistics,
            'orders' => $orders,
            'integrationFields' => $integrationFields,
        ]);
    }

    public function update(UpdateRequest $request, ProviderTerminal $providerTerminal)
    {
        Transaction::run(function () use ($request, $providerTerminal) {
            $data = $request->validated();
            
            $data['additional_settings'] = $this->mergeIntegrationFields($data);
            $data['enabled_detail_types'] = $data['enabled_detail_types'] ?? [];
            $data['is_active'] = $request->boolean('is_active', $providerTerminal->is_active);

            // Убираем поля, которых нет в fillable модели
            unset($data['integration'], $data['integration_settings'], $data['integration_fields'], $data['retry_delay_ms']);

            $providerTerminal->update($data);
            
            ProviderTerminal::clearCache();
        });

        return redirect()->back()->with('success', 'Терминал обновлен');
    }

    public function destroy(ProviderTerminal $providerTerminal)
    {
        Transaction::run(function () use ($providerTerminal) {
            $providerTerminal->delete();
            ProviderTerminal::clearCache();
        });

        return redirect()->route('admin.provider-terminals.index')
            ->with('success', 'Терминал удален');
    }

    public function toggle(ProviderTerminal $providerTerminal)
    {
        Transaction::run(function () use ($providerTerminal) {
            $providerTerminal->update(['is_active' => !$providerTerminal->is_active]);
            ProviderTerminal::clearCache();
        });

        return redirect()->back()->with('success', 'Статус переключен');
    }

    public function attachMerchant(Request $request, ProviderTerminal $providerTerminal, $merchantId)
    {
        $merchantId = (int) $merchantId;
        
        // Проверяем, не привязан ли уже мерчант
        if ($providerTerminal->merchants()->where('merchant_id', $merchantId)->exists()) {
            return redirect()->back()->withErrors(['error' => 'Мерчант уже привязан к терминалу']);
        }

        Transaction::run(function () use ($providerTerminal, $merchantId) {
            $providerTerminal->merchants()->attach($merchantId, [
                'is_active' => false, // Все мерчанты создаются выключенными по умолчанию
            ]);
        });

        return redirect()->back()->with('success', 'Мерчант успешно привязан к терминалу');
    }

    public function detachMerchant(ProviderTerminal $providerTerminal, $merchantId)
    {
        $merchantId = (int) $merchantId;
        
        Transaction::run(function () use ($providerTerminal, $merchantId) {
            $providerTerminal->merchants()->detach($merchantId);
        });

        return redirect()->back()->with('success', 'Мерчант отвязан от терминала');
    }

    public function toggleMerchant(Request $request, ProviderTerminal $providerTerminal, $merchantId)
    {
        $merchantId = (int) $merchantId;
        $isActive = $request->boolean('is_active', true);
        
        // Проверяем, существует ли связь в pivot таблице
        $exists = $providerTerminal->merchants()->where('merchant_id', $merchantId)->exists();
        
        if (!$exists) {
            // Если связи нет - создаём её с нужным статусом
            Transaction::run(function () use ($providerTerminal, $merchantId, $isActive) {
                $providerTerminal->merchants()->attach($merchantId, [
                    'is_active' => $isActive,
                ]);
            });
            
            return redirect()->back()->with('success', 'Мерчант привязан и статус установлен');
        }
        
        // Если связь есть - обновляем статус
        Transaction::run(function () use ($providerTerminal, $merchantId, $isActive) {
            $providerTerminal->merchants()->updateExistingPivot($merchantId, [
                'is_active' => $isActive,
            ]);
        });

        return redirect()->back()->with('success', 'Статус мерчанта обновлен');
    }

    /**
     * Получить поля для интеграции (API endpoint)
     */
    public function getIntegrationFields(Request $request)
    {
        $integration = $request->get('integration');
        
        if (!$integration) {
            return response()->json(['fields' => []]);
        }

        try {
            $integrationEnum = ProviderIntegrationEnum::from($integration);
            $fields = IntegrationFieldsConfig::getFields($integrationEnum);
            
            return response()->json([
                'fields' => $fields,
                'name' => IntegrationFieldsConfig::getIntegrationName($integrationEnum),
            ]);
        } catch (\ValueError $e) {
            return response()->json(['fields' => [], 'error' => 'Unknown integration'], 400);
        }
    }

    /**
     * Получить все интеграции с полями (API endpoint)
     */
    public function getAllIntegrations()
    {
        return response()->json([
            'integrations' => IntegrationFieldsConfig::getAllIntegrations(),
        ]);
    }

    private function getLogAggregates($since = null): array
    {
        $query = DB::table('requisite_provider_logs')
            ->select([
                'provider_terminal_id',
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('COUNT(CASE WHEN success = 1 THEN 1 END) as successful_requests'),
                DB::raw('ROUND(COUNT(CASE WHEN success = 1 THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as success_rate'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
            ])
            ->whereNotNull('provider_terminal_id')
            ->groupBy('provider_terminal_id');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query->get()
            ->keyBy('provider_terminal_id')
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    private function getOrderAggregates($since = null): array
    {
        $query = DB::table('orders')
            ->select([
                'provider_terminal_id',
                DB::raw('COUNT(CASE WHEN status = \'' . OrderStatus::SUCCESS->value . '\' THEN 1 END) as successful_deals'),
                DB::raw('COUNT(*) as total_deals'),
                DB::raw('ROUND(COUNT(CASE WHEN status = \'' . OrderStatus::SUCCESS->value . '\' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as deal_conversion_rate'),
            ])
            ->whereIn('status', [OrderStatus::SUCCESS->value, OrderStatus::FAIL->value])
            ->whereNotNull('provider_terminal_id')
            ->groupBy('provider_terminal_id');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return $query->get()
            ->keyBy('provider_terminal_id')
            ->map(fn($row) => (array) $row)
            ->toArray();
    }

    private function mergeIntegrationFields(array $data): ?array
    {
        $additional = [];
        
        // Если передан JSON additional_settings - парсим его
        if (!empty($data['additional_settings']) && is_string($data['additional_settings'])) {
            $decoded = json_decode($data['additional_settings'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $additional = $decoded;
            }
        } elseif (!empty($data['additional_settings']) && is_array($data['additional_settings'])) {
            $additional = $data['additional_settings'];
        }

        // Поля интеграции сохраняются напрямую в additional_settings
        // Например: api_url, api_token, merchant_id и т.д.
        if (!empty($data['integration_settings']) && is_array($data['integration_settings'])) {
            foreach ($data['integration_settings'] as $key => $value) {
                if ($value !== null && $value !== '') {
                    $additional[$key] = $value;
                }
            }
        }

        // Всегда возвращаем массив (объект в JSON), даже если он пустой
        // Это важно для корректной работы с JSON полем в БД
        return $additional;
    }

    private function getOrdersForTerminal(int $terminalId): array
    {
        $paginator = DB::table('orders')
            // В БД нет колонок base_currency / service_commission_amount_total (они виртуальные в API ресурсах).
            // Поэтому выбираем реальные поля и вычисляем нужные для UI значения ниже.
            ->select('uuid', 'amount', 'merchant_profit', 'service_profit', 'currency', 'total_profit', 'created_at')
            ->where('provider_terminal_id', $terminalId)
            ->where('status', OrderStatus::SUCCESS->value)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $items = collect($paginator->items())->map(function ($row) {
            $row = (array) $row;

            // UI ожидает base_currency и service_commission_amount_total.
            // base_currency: базовая валюта прибыли/комиссий (у нас это USDT).
            // service_commission_amount_total: отображаем сумму комиссии сервиса (service_profit).
            $row['base_currency'] = 'usdt';
            
            // Форматируем суммы из центов в нормальный вид
            $row['amount'] = round(($row['amount'] ?? 0) / 100, 2);
            $row['merchant_profit'] = round(($row['merchant_profit'] ?? 0) / 100, 2);
            $row['total_profit'] = round(($row['total_profit'] ?? 0) / 100, 2);
            $row['service_commission_amount_total'] = round(($row['service_profit'] ?? 0) / 100, 2);

            return $row;
        })->all();

        return [
            'data' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
            ],
        ];
    }
}
