<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RequisiteProvidersController extends Controller
{
    public function index()
    {
        $chain = services()->requisiteProviderChain();
        
        // Получаем статистику провайдеров (исключаем внутренний)
        $providersStats = collect($chain->getProvidersStats())
            ->filter(fn($provider) => $provider['name'] !== 'internal')
            ->values()
            ->toArray();
        
        // Получаем статистику из логов за последние 24 часа (исключаем внутренний)
        $logsStats = $this->getProvidersLogsStats();
        
        // Объединяем данные
        $providers = collect($providersStats)->map(function ($provider) use ($logsStats) {
            $logStats = $logsStats->firstWhere('provider_name', $provider['name']);
            
            // Преобразуем stdClass в массив если найден, иначе используем дефолтные значения
            if ($logStats) {
                $logStats = (array) $logStats;
                
                // ВАЖНО: Приводим все числовые поля к правильным типам для фронтенда
                $logStats['total_requests'] = (int) ($logStats['total_requests'] ?? 0);
                $logStats['successful_requests'] = (int) ($logStats['successful_requests'] ?? 0);
                $logStats['failed_requests'] = (int) ($logStats['failed_requests'] ?? 0);
                $logStats['avg_response_time'] = (float) ($logStats['avg_response_time'] ?? 0);
                $logStats['success_rate'] = (float) ($logStats['success_rate'] ?? 0);
            } else {
                $logStats = [
                    'total_requests' => 0,
                    'successful_requests' => 0,
                    'failed_requests' => 0,
                    'avg_response_time' => 0.0,
                    'success_rate' => 0.0,
                ];
            }
            
            return array_merge($provider, $logStats);
        })->toArray();

        return Inertia::render('Admin/RequisiteProviders/Index', [
            'providers' => $providers,
            'summary' => $this->getProvidersSummary($providers),
            'merchants' => Merchant::select('id', 'name')->get(),
            'paymentGateways' => PaymentGateway::select('id', 'name')->get(),
            'currencies' => Currency::getAll()->map(fn($currency) => $currency->getCode())->toArray(),
            'detailTypes' => collect(DetailType::cases())->map(fn($type) => [
                'value' => $type->value,
                'label' => trans('detail-type.' . $type->value)
            ])->toArray(),
        ]);
    }

    public function test(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'merchant_id' => 'required|exists:merchants,id',
            'detail_type' => 'nullable|string',
            'gateway_id' => 'nullable|exists:payment_gateways,id',
            'provider_name' => 'nullable|string',
        ]);

        $amount = Money::fromPrecision($request->amount, Currency::make($request->currency));
        $merchant = Merchant::findOrFail($request->merchant_id);
        $detailType = $request->detail_type ? DetailType::from($request->detail_type) : null;
        $gateway = $request->gateway_id ? PaymentGateway::findOrFail($request->gateway_id) : null;
        $providerName = $request->provider_name;

        $chain = services()->requisiteProviderChain();
        $startTime = microtime(true);

        if ($providerName) {
            // Тестируем конкретного провайдера
            $result = $this->testSpecificProvider($chain, $providerName, $merchant, $amount, $detailType, $gateway);
        } else {
            // Тестируем всю цепочку
            $result = $this->testProviderChain($chain, $merchant, $amount, $detailType, $gateway);
        }

        $executionTime = round((microtime(true) - $startTime) * 1000); // в миллисекундах

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'execution_time_ms' => $executionTime,
            'tested_providers' => $result['tested_providers'] ?? [],
        ]);
    }

    public function logs(Request $request)
    {
        $query = DB::table('requisite_provider_logs')
            ->select([
                'id',
                'provider_name',
                'merchant_id',
                'request_type',
                'success',
                'error_message',
                'response_time_ms',
                'retry_attempt',
                'detail_id',
                'created_at'
            ])
            ->where('provider_name', '!=', 'internal') // Исключаем внутренний провайдер
            ->orderBy('created_at', 'desc');

        // Фильтры
        if ($request->provider_name) {
            $query->where('provider_name', $request->provider_name);
        }

        if ($request->success !== null) {
            $query->where('success', $request->boolean('success'));
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->paginate(50);

        return response()->json($logs);
    }

    public function stats(Request $request)
    {
        $period = $request->get('period', '24h'); // 24h, 7d, 30d
        
        $stats = $this->getDetailedStats($period);
        
        return response()->json($stats);
    }

    protected function getProvidersLogsStats()
    {
        return DB::table('requisite_provider_logs')
            ->select([
                'provider_name',
                DB::raw('COUNT(*) as total_requests'),
                DB::raw('COUNT(CASE WHEN success = 1 THEN 1 END) as successful_requests'),
                DB::raw('COUNT(CASE WHEN success = 0 THEN 1 END) as failed_requests'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('ROUND(COUNT(CASE WHEN success = 1 THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate')
            ])
            ->where('created_at', '>=', now()->subDay())
            ->where('provider_name', '!=', 'internal') // Исключаем внутренний провайдер
            ->groupBy('provider_name')
            ->get();
    }

    protected function getProvidersSummary(array $providers): array
    {
        $total = count($providers);
        $active = collect($providers)->where('available', true)->count();
        $totalRequests = collect($providers)->sum('total_requests');
        $avgSuccessRate = collect($providers)->avg('success_rate') ?? 0;

        return [
            'total_providers' => $total,
            'active_providers' => $active,
            'inactive_providers' => $total - $active,
            'total_requests_24h' => $totalRequests,
            'average_success_rate' => round($avgSuccessRate, 2),
        ];
    }

    protected function testSpecificProvider($chain, $providerName, $merchant, $amount, $detailType, $gateway)
    {
        $provider = $chain->getProviders()->first(fn($p) => $p->getName() === $providerName);

        if (!$provider) {
            return [
                'success' => false,
                'message' => "Провайдер '{$providerName}' не найден",
            ];
        }

        if (!$provider->isAvailable()) {
            return [
                'success' => false,
                'message' => 'Провайдер отключен',
            ];
        }

        if (!$provider->supports($amount, $amount->getCurrency(), $gateway, $detailType)) {
            return [
                'success' => false,
                'message' => 'Провайдер не поддерживает указанные параметры',
            ];
        }

        try {
            $result = $provider->getRequisites(
                merchant: $merchant,
                market: MarketEnum::BYBIT,
                amount: $amount,
                detailType: $detailType,
                currency: $amount->getCurrency(),
                gateway: $gateway
            );

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Реквизиты успешно получены',
                    'data' => [
                        'detail_id' => $result->id,
                        'trader_id' => $result->userID,
                        'gateway_id' => $result->paymentGatewayID,
                        'amount' => $result->amount->toBeauty() . ' ' . $result->currency->getCode(),
                        'total_profit' => $result->totalProfit->toBeauty() . ' USDT',
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Провайдер не вернул реквизиты',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка провайдера: ' . $e->getMessage(),
            ];
        }
    }

    protected function testProviderChain($chain, $merchant, $amount, $detailType, $gateway)
    {
        $testedProviders = [];
        $activeProviders = $chain->getActiveProviders();

        foreach ($activeProviders as $provider) {
            $providerResult = [
                'name' => $provider->getName(),
                'priority' => $provider->getPriority(),
                'supports' => false,
                'success' => false,
                'message' => '',
                'execution_time_ms' => 0,
            ];

            $startTime = microtime(true);

            if (!$provider->supports($amount, $amount->getCurrency(), $gateway, $detailType)) {
                $providerResult['message'] = 'Не поддерживает параметры';
            } else {
                $providerResult['supports'] = true;
                
                try {
                    $result = $provider->getRequisites(
                        merchant: $merchant,
                        market: MarketEnum::BYBIT,
                        amount: $amount,
                        detailType: $detailType,
                        currency: $amount->getCurrency(),
                        gateway: $gateway
                    );

                    if ($result) {
                        $providerResult['success'] = true;
                        $providerResult['message'] = 'Реквизиты найдены';
                        
                        // Возвращаем результат первого успешного провайдера
                        $providerResult['execution_time_ms'] = round((microtime(true) - $startTime) * 1000);
                        $testedProviders[] = $providerResult;
                        
                        return [
                            'success' => true,
                            'message' => "Реквизиты найдены у провайдера: {$provider->getName()}",
                            'data' => [
                                'detail_id' => $result->id,
                                'trader_id' => $result->userID,
                                'gateway_id' => $result->paymentGatewayID,
                                'amount' => $result->amount->toBeauty() . ' ' . $result->currency->getCode(),
                                'provider' => $provider->getName(),
                            ],
                            'tested_providers' => $testedProviders,
                        ];
                    } else {
                        $providerResult['message'] = 'Реквизиты не найдены';
                    }
                } catch (\Exception $e) {
                    $providerResult['message'] = 'Ошибка: ' . $e->getMessage();
                }
            }

            $providerResult['execution_time_ms'] = round((microtime(true) - $startTime) * 1000);
            $testedProviders[] = $providerResult;
        }

        return [
            'success' => false,
            'message' => 'Реквизиты не найдены ни у одного провайдера',
            'tested_providers' => $testedProviders,
        ];
    }

    protected function getDetailedStats(string $period): array
    {
        $dateCondition = match($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };

        // Статистика по времени
        $timeStats = DB::table('requisite_provider_logs')
            ->select([
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                'provider_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN success = 1 THEN 1 END) as successful'),
            ])
            ->where('created_at', '>=', $dateCondition)
            ->where('provider_name', '!=', 'internal') // Исключаем внутренний провайдер
            ->groupBy('hour', 'provider_name')
            ->orderBy('hour')
            ->get();

        // Топ ошибок
        $topErrors = DB::table('requisite_provider_logs')
            ->select([
                'error_message',
                'provider_name',
                DB::raw('COUNT(*) as count')
            ])
            ->where('created_at', '>=', $dateCondition)
            ->where('provider_name', '!=', 'internal') // Исключаем внутренний провайдер
            ->where('success', false)
            ->whereNotNull('error_message')
            ->groupBy('error_message', 'provider_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'time_stats' => $timeStats,
            'top_errors' => $topErrors,
        ];
    }
} 