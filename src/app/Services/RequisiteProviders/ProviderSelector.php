<?php

namespace App\Services\RequisiteProviders;

use App\Enums\DetailType;
use App\Enums\ProviderIntegrationEnum;
use App\Models\Merchant;
use App\Models\Provider;
use App\Models\ProviderTerminal;
use App\Services\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Быстрый сервис выбора провайдера
 * 
 * Оптимизирован для высокой нагрузки:
 * - Кэширование конфигураций провайдеров
 * - Мгновенная инвалидация при изменениях
 * - Фильтрация на уровне кэша (без N+1 запросов)
 */
class ProviderSelector
{
    public const CACHE_KEY = 'provider_selector:terminals';
    public const CACHE_TTL = 300; // 5 минут - короткий TTL для актуальности
    private const DIAGNOSTICS_TERMINALS_LIMIT = 200; // защита от взрыва логов
    
    /**
     * Балансы трейдеров для текущего запроса (in-memory, без внешнего кэша)
     */
    private array $traderBalances = [];
    
    /**
     * Выбрать подходящий провайдер терминал для запроса
     * 
     * @param int $merchantId ID мерчанта
     * @param float $amount Сумма в базовой валюте
     * @param DetailType|null $detailType Тип реквизита (card, phone, account_number)
     * @param bool $checkBalance Проверять баланс
     * @return array|null Конфигурация терминала или null
     */
    public function selectTerminal(
        int $merchantId,
        float $amount,
        ?DetailType $detailType = null,
        bool $checkBalance = true
    ): ?array {
        $terminals = $this->getActiveTerminals();
        $diagnosticsEnabled = true;

        // Предзагружаем балансы всех трейдеров одним запросом
        if ($checkBalance) {
            $this->preloadTraderBalances($terminals);
        }

        if ($diagnosticsEnabled) {
            Log::error('[ProviderSelector] Diagnostics: selectTerminal() started', [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'detail_type' => $detailType?->value,
                'check_balance' => $checkBalance,
                'active_terminals_count' => $terminals->count(),
            ]);
        }
        
        // Фильтруем по критериям
        $suitable = $terminals->filter(function (array $terminal) use ($merchantId, $amount, $detailType, $checkBalance, $diagnosticsEnabled) {
            $diag = $this->diagnoseCriteria($terminal, $merchantId, $amount, $detailType, $checkBalance);
            Log::error('[ProviderSelector] Diagnostics: terminal criteria check', [
                'terminal_id' => $terminal['id'] ?? null,
                'terminal_uuid' => $terminal['uuid'] ?? null,
                'provider_id' => $terminal['provider_id'] ?? null,
                'provider_name' => $terminal['provider_name'] ?? null,
                'integration' => $terminal['integration'] ?? null,
                'priority' => $terminal['priority'] ?? null,
                'min_sum' => $terminal['min_sum'] ?? null,
                'max_sum' => $terminal['max_sum'] ?? null,
                'enabled_detail_types' => $terminal['enabled_detail_types'] ?? null,
                'allowed_merchant_ids_count' => isset($terminal['allowed_merchant_ids']) ? count((array)$terminal['allowed_merchant_ids']) : null,
                'match' => $diag['match'],
                'reasons' => $diag['reasons'],
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'detail_type' => $detailType?->value,
                'check_balance' => $checkBalance,
            ]);

            return $diag['match'];
        });
        
        if ($suitable->isEmpty()) {
            Log::error('[ProviderSelector] No suitable terminals found', [
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'detail_type' => $detailType?->value,
                'total_active' => $terminals->count(),
            ]);
            return null;
        }
        
        // Сортируем по приоритету (меньше = лучше) и возвращаем первый
        $selected = $suitable->sortBy('priority')->first();
        
        Log::error('[ProviderSelector] Selected terminal', [
            'terminal_id' => $selected['id'],
            'provider_name' => $selected['provider_name'],
            'priority' => $selected['priority'],
            'merchant_id' => $merchantId,
        ]);
        
        return $selected;
    }
    
    /**
     * Получить все подходящие провайдер терминалы (отсортированные по приоритету)
     */
    public function selectAllSuitableTerminals(
        int $merchantId,
        float $amount,
        ?DetailType $detailType = null,
        bool $checkBalance = true
    ): Collection {
        $terminals = $this->getActiveTerminals();
        $diagnosticsEnabled = true;

        // Предзагружаем балансы всех трейдеров одним запросом
        if ($checkBalance) {
            $this->preloadTraderBalances($terminals);
        }

        Log::error('[ProviderSelector] Diagnostics: selectAllSuitableTerminals() started', [
            'merchant_id' => $merchantId,
            'amount' => $amount,
            'detail_type' => $detailType?->value,
            'check_balance' => $checkBalance,
            'active_terminals_count' => $terminals->count(),
        ]);

        $matched = [];
        $checkedCount = 0;
        $skippedCount = 0;
        foreach ($terminals as $terminal) {
            $checkedCount++;
            if ($checkedCount > self::DIAGNOSTICS_TERMINALS_LIMIT) {
                $skippedCount++;
                continue;
            }
            $diag = $this->diagnoseCriteria($terminal, $merchantId, $amount, $detailType, $checkBalance);

            Log::error('[ProviderSelector] Diagnostics: terminal criteria check', [
                'terminal_id' => $terminal['id'] ?? null,
                'terminal_uuid' => $terminal['uuid'] ?? null,
                'provider_id' => $terminal['provider_id'] ?? null,
                'provider_name' => $terminal['provider_name'] ?? null,
                'integration' => $terminal['integration'] ?? null,
                'priority' => $terminal['priority'] ?? null,
                'min_sum' => $terminal['min_sum'] ?? null,
                'max_sum' => $terminal['max_sum'] ?? null,
                'enabled_detail_types' => $terminal['enabled_detail_types'] ?? null,
                'allowed_merchant_ids_count' => isset($terminal['allowed_merchant_ids']) ? count((array)$terminal['allowed_merchant_ids']) : null,
                'match' => $diag['match'],
                'reasons' => $diag['reasons'],
                'merchant_id' => $merchantId,
                'amount' => $amount,
                'detail_type' => $detailType?->value,
                'check_balance' => $checkBalance,
            ]);

            if ($diag['match']) {
                $matched[] = $terminal;
            }
        }

        $result = collect($matched)->sortBy('priority')->values();

        Log::error('[ProviderSelector] Diagnostics: selectAllSuitableTerminals() result', [
            'matched_count' => $result->count(),
            'matched_terminal_ids' => $result->pluck('id')->all(),
            'checked_terminals' => min($checkedCount, self::DIAGNOSTICS_TERMINALS_LIMIT),
            'skipped_due_to_limit' => $skippedCount,
        ]);

        return $result;
    }
    
    /**
     * Проверить соответствие терминала критериям
     */
    protected function matchesCriteria(
        array $terminal,
        int $merchantId,
        float $amount,
        ?DetailType $detailType,
        bool $checkBalance
    ): bool {
        // 1. Проверка активности (уже отфильтровано в кэше, но для надёжности)
        if (empty($terminal['is_active'])) {
            return false;
        }
        
        // 2. Проверка мерчанта (должен быть привязан и активен)
        // Терминал доступен ТОЛЬКО для привязанных мерчантов (пустой список = недоступен никому)
        $allowedMerchants = $terminal['allowed_merchant_ids'] ?? [];
        if (empty($allowedMerchants) || !in_array($merchantId, $allowedMerchants)) {
            return false;
        }
        
        // 3. Проверка метода (detail type)
        if ($detailType !== null) {
            $enabledTypes = $terminal['enabled_detail_types'] ?? [];
            if (!empty($enabledTypes) && !in_array($detailType->value, $enabledTypes)) {
                return false;
            }
        }
        
        // 4. Проверка min/max суммы
        $minSum = $terminal['min_sum'] ?? null;
        $maxSum = $terminal['max_sum'] ?? null;
        
        if ($minSum !== null && $amount < (float)$minSum) {
            return false;
        }
        
        if ($maxSum !== null && $amount > (float)$maxSum) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Получить активные терминалы из кэша
     */
    public function getActiveTerminals(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->loadActiveTerminalsFromDb();
        });
    }
    
    /**
     * Загрузить активные терминалы из БД
     */
    protected function loadActiveTerminalsFromDb(): Collection
    {
        $startTime = microtime(true);
        
        // Оптимизированный запрос с JOIN вместо N+1
        $terminals = DB::table('provider_terminals as pt')
            ->join('providers as p', 'p.id', '=', 'pt.provider_id')
            ->where('pt.is_active', true)
            ->where('p.is_active', true)
            ->select([
                'pt.id',
                'pt.uuid',
                'pt.name',
                'pt.provider_id',
                'pt.min_sum',
                'pt.max_sum',
                'pt.time_for_order',
                'pt.rate',
                'pt.max_response_time_ms',
                'pt.number_of_retries',
                'pt.additional_settings',
                'pt.enabled_detail_types',
                'p.name as provider_name',
                'p.integration',
                'p.trader_id',
            ])
            ->get();
        
        // Загружаем связи мерчантов одним запросом
        $terminalIds = $terminals->pluck('id')->all();
        
        $merchantLinks = DB::table('provider_terminal_merchant')
            ->whereIn('provider_terminal_id', $terminalIds)
            ->where('is_active', true)
            ->select('provider_terminal_id', 'merchant_id')
            ->get()
            ->groupBy('provider_terminal_id');
        
        // Формируем результат
        $result = $terminals->map(function ($row) use ($merchantLinks) {
            $terminalId = $row->id;
            $additionalSettings = is_string($row->additional_settings) 
                ? json_decode($row->additional_settings, true) 
                : ($row->additional_settings ?? []);
            
            $enabledDetailTypes = is_string($row->enabled_detail_types)
                ? json_decode($row->enabled_detail_types, true)
                : ($row->enabled_detail_types ?? []);
            
            // Приоритет = rate × 10 (меньше rate = выше приоритет)
            $rate = (float)($row->rate ?? 0);
            $priority = (int)round($rate * 10);
            
            return [
                'id' => $terminalId,
                'uuid' => $row->uuid,
                'name' => $row->name,
                'provider_id' => $row->provider_id,
                'provider_name' => $row->provider_name,
                'integration' => $row->integration,
                'trader_id' => $row->trader_id,
                'min_sum' => $row->min_sum,
                'max_sum' => $row->max_sum,
                'time_for_order' => $row->time_for_order,
                'rate' => $rate,
                'priority' => $priority,
                'max_response_time_ms' => $row->max_response_time_ms,
                'number_of_retries' => $row->number_of_retries ?? $additionalSettings['number_of_retries'] ?? 3,
                'enabled_detail_types' => $enabledDetailTypes,
                'is_active' => true,
                'allowed_merchant_ids' => $merchantLinks->get($terminalId, collect())->pluck('merchant_id')->all(),
                'additional_settings' => $additionalSettings,
                'callback_url' => rtrim(config('app.url'), '/') . '/api/callback/' . $row->uuid,
            ];
        });
        
        $loadTime = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::error('[ProviderSelector] Loaded terminals from DB', [
            'count' => $result->count(),
            'load_time_ms' => $loadTime,
        ]);
        
        return $result;
    }
    
    /**
     * Инвалидировать кэш (вызывать при изменении настроек)
     */
    public static function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        
        Log::error('[ProviderSelector] Cache invalidated');
    }
    
    /**
     * Прогреть кэш (вызывать при старте приложения или после инвалидации)
     */
    public function warmCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->getActiveTerminals();
        
        Log::error('[ProviderSelector] Cache warmed');
    }
    
    /**
     * Создать провайдер по конфигурации терминала
     */
    public function createProviderFromTerminal(array $terminal): ?\App\Contracts\RequisiteProviderContract
    {
        $integration = $terminal['integration'] ?? null;
        
        if (!$integration) {
            return null;
        }
        
        try {
            $integrationEnum = $integration instanceof ProviderIntegrationEnum
                ? $integration
                : ProviderIntegrationEnum::from($integration);
            
            // Собираем полную конфигурацию
            $config = array_merge($terminal['additional_settings'] ?? [], [
                'provider_id' => $terminal['provider_id'],
                'provider_terminal_id' => $terminal['id'],
                'provider_terminal_uuid' => $terminal['uuid'],
                'integration' => $integrationEnum,
                'rate' => $terminal['rate'],
                'min_sum' => $terminal['min_sum'],
                'max_sum' => $terminal['max_sum'],
                'time_for_order' => $terminal['time_for_order'],
                'number_of_retries' => $terminal['number_of_retries'],
                'max_response_time_ms' => $terminal['max_response_time_ms'],
                'enabled_detail_types' => $terminal['enabled_detail_types'],
                'trader_id' => $terminal['trader_id'],
                'allowed_merchant_ids' => $terminal['allowed_merchant_ids'],
                'callback_url' => $terminal['callback_url'],
                'enabled' => true,
            ]);
            
            return match ($integrationEnum) {
                ProviderIntegrationEnum::GAREX => new GarexProvider($config),
                ProviderIntegrationEnum::METHODPAY => new MethodPayProvider($config),
                ProviderIntegrationEnum::ALPHAPAY => new AlphaPayProvider($config),
                ProviderIntegrationEnum::X023 => new X023Provider($config),
                default => null,
            };
            
        } catch (\Throwable $e) {
            Log::error('[ProviderSelector] Failed to create provider', [
                'terminal_id' => $terminal['id'] ?? null,
                'terminal_uuid' => $terminal['uuid'] ?? null,
                'provider_id' => $terminal['provider_id'] ?? null,
                'integration' => $integration,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Диагностика причин, почему терминал не подходит.
     *
     * @return array{match: bool, reasons: string[]}
     */
    protected function diagnoseCriteria(
        array $terminal,
        int $merchantId,
        float $amount,
        ?DetailType $detailType,
        bool $checkBalance
    ): array {
        $reasons = [];

        if (empty($terminal['is_active'])) {
            $reasons[] = 'terminal_inactive';
        }

        // Терминал доступен ТОЛЬКО для привязанных мерчантов (пустой список = недоступен никому)
        $allowedMerchants = $terminal['allowed_merchant_ids'] ?? [];
        if (empty($allowedMerchants)) {
            $reasons[] = 'no_merchants_attached';
        } elseif (!in_array($merchantId, $allowedMerchants)) {
            $reasons[] = 'merchant_not_allowed';
        }

        if ($detailType !== null) {
            $enabledTypes = $terminal['enabled_detail_types'] ?? [];
            if (!empty($enabledTypes) && !in_array($detailType->value, $enabledTypes)) {
                $reasons[] = 'detail_type_not_enabled';
            }
        }

        $minSum = $terminal['min_sum'] ?? null;
        if ($minSum !== null && $amount < (float) $minSum) {
            $reasons[] = 'amount_below_min_sum';
        }

        $maxSum = $terminal['max_sum'] ?? null;
        if ($maxSum !== null && $amount > (float) $maxSum) {
            $reasons[] = 'amount_above_max_sum';
        }

        // Проверка положительного баланса трейдера
        if ($checkBalance) {
            $traderId = $terminal['trader_id'] ?? null;
            if ($traderId !== null) {
                $traderBalance = $this->getTraderBalance($traderId);
                if ($traderBalance !== null && $traderBalance <= 0) {
                    $reasons[] = 'trader_balance_not_positive';
                }
            }
        }

        return [
            'match' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Предзагрузить балансы всех трейдеров из терминалов одним запросом
     * Сохраняет в in-memory массив на время текущего HTTP-запроса
     */
    protected function preloadTraderBalances(Collection $terminals): void
    {
        // Если уже загружены - не делаем повторный запрос
        if (!empty($this->traderBalances)) {
            return;
        }

        // Собираем уникальные trader_id
        $traderIds = $terminals
            ->pluck('trader_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($traderIds)) {
            return;
        }

        // Загружаем из БД одним запросом
        $wallets = DB::table('wallets')
            ->whereIn('user_id', $traderIds)
            ->select('user_id', 'trust_balance')
            ->get();

        foreach ($wallets as $wallet) {
            // trust_balance хранится в центах, конвертируем в доллары
            $this->traderBalances[$wallet->user_id] = ((int) $wallet->trust_balance) / 100;
        }
    }

    /**
     * Получить баланс трейдера (trust_balance) по его ID
     * Использует предзагруженные данные
     */
    protected function getTraderBalance(int $traderId): ?float
    {
        return $this->traderBalances[$traderId] ?? null;
    }
}
