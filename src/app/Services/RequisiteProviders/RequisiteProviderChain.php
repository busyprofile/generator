<?php

namespace App\Services\RequisiteProviders;

use App\Contracts\RequisiteProviderContract;
use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Order\Features\OrderDetailProvider\Values\Detail;
use App\Services\RequisiteProviders\InternalRequisiteProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RequisiteProviderChain
{
    /** @var Collection<RequisiteProviderContract> */
    protected Collection $providers;
    
    protected ProviderSelector $selector;
    protected array $lastDiagnostics = [];

    public function __construct()
    {
        $this->providers = collect();
        $this->selector = new ProviderSelector();
    }

    public function getLastDiagnostics(): array
    {
        return $this->lastDiagnostics;
    }

    /**
     * Добавить провайдера в цепочку
     */
    public function addProvider(RequisiteProviderContract $provider): self
    {
        $this->providers->push($provider);
        
        // Сортируем по приоритету после добавления
        $this->sortProviders();
        
        return $this;
    }

    /**
     * Получить все провайдеры
     */
    public function getProviders(): Collection
    {
        return $this->providers;
    }

    /**
     * Возвращает внутреннего провайдера (internal)
     */
    public function getInternalProvider(): ?RequisiteProviderContract
    {
        return $this->providers->first(function (RequisiteProviderContract $provider) {
            return $provider instanceof InternalRequisiteProvider || $provider->getName() === 'internal';
        });
    }

    /**
     * Получить активные провайдеры
     */
    public function getActiveProviders(): Collection
    {
        return $this->providers->filter(fn(RequisiteProviderContract $provider) => $provider->isAvailable());
    }

    /**
     * Получить активных внешних провайдеров с учетом supports()
     * 
     * ОПТИМИЗИРОВАНО: Использует ProviderSelector для быстрого выбора из кэша
     */
    public function getActiveExternalProviders(
        Money $amount,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?DetailType $detailType = null,
        ?bool $transgran = null,
        ?Merchant $merchant = null
    ): Collection {
        // Если есть мерчант - используем оптимизированный селектор
        if ($merchant) {
            $amountValue = (float)$amount->toPrecision();
            
            // Получаем подходящие терминалы из кэша (очень быстро!)
            $suitableTerminals = $this->selector->selectAllSuitableTerminals(
                merchantId: $merchant->id,
                amount: $amountValue,
                detailType: $detailType,
                checkBalance: true
            );

            $this->lastDiagnostics['external_selection'] = [
                'merchant_id' => $merchant->id,
                'amount_precision' => $amount->toPrecision(),
                'amount_value_float' => $amountValue,
                'currency' => $currency?->getCode(),
                'detail_type' => $detailType?->value,
                'gateway_id' => $gateway?->id,
                'suitable_terminals_count' => $suitableTerminals->count(),
                'suitable_terminals' => $suitableTerminals->take(50)->map(fn(array $t) => [
                    'terminal_id' => $t['id'] ?? null,
                    'terminal_uuid' => $t['uuid'] ?? null,
                    'provider_id' => $t['provider_id'] ?? null,
                    'provider_name' => $t['provider_name'] ?? null,
                    'integration' => $t['integration'] ?? null,
                    'priority' => $t['priority'] ?? null,
                    'min_sum' => $t['min_sum'] ?? null,
                    'max_sum' => $t['max_sum'] ?? null,
                    'enabled_detail_types' => $t['enabled_detail_types'] ?? null,
                ])->values()->all(),
            ];
            
            // Создаём провайдеров "на лету" только для подходящих терминалов
            $created = $suitableTerminals->map(function (array $terminal) {
                $provider = $this->selector->createProviderFromTerminal($terminal);
                return [
                    'terminal' => $terminal,
                    'provider' => $provider,
                ];
            });

            $this->lastDiagnostics['external_instantiation'] = $created->take(50)->map(function ($row) {
                $t = $row['terminal'] ?? [];
                $p = $row['provider'] ?? null;
                return [
                    'terminal_id' => $t['id'] ?? null,
                    'terminal_uuid' => $t['uuid'] ?? null,
                    'integration' => $t['integration'] ?? null,
                    'provider_name' => $t['provider_name'] ?? null,
                    'created' => $p !== null,
                    'provider_class' => $p ? get_class($p) : null,
                    'provider_get_name' => $p ? $p->getName() : null,
                    'provider_priority' => $p ? $p->getPriority() : null,
                    'provider_id' => method_exists($p, 'getConfig') ? ($p->getConfig()['provider_id'] ?? null) : null,
                    'provider_terminal_id' => method_exists($p, 'getConfig') ? ($p->getConfig()['provider_terminal_id'] ?? null) : null,
                ];
            })->values()->all();

            return $created
                ->pluck('provider')
                ->filter()
                ->values();
        }
        
        // Fallback на старую логику для legacy кода без мерчанта
        return $this->getActiveProviders()
            ->reject(fn(RequisiteProviderContract $provider) => $provider instanceof InternalRequisiteProvider || $provider->getName() === 'internal')
            ->filter(fn(RequisiteProviderContract $provider) => $provider->supports($amount, $currency, $gateway, $detailType, $transgran, $merchant))
            ->values();
    }

    /**
     * Получить реквизиты с каскадированием по цепочке провайдеров
     * 
     * Логика:
     * 1. Сначала пробуем Internal провайдер (настройки терминалов НЕ применяются)
     * 2. Если Internal не вернул результат - идём по внешним провайдерам по приоритету
     * 3. Внешние провайдеры фильтруются по: активен, метод, мерчант, мин/макс сумма, баланс
     */
    public function getRequisites(
        Merchant $merchant,
        MarketEnum $market,
        Money $amount,
        ?DetailType $detailType = null,
        ?Currency $currency = null,
        ?PaymentGateway $gateway = null,
        ?bool $transgran = null,
        ?Order $order = null
    ): ?Detail {
        $startTime = microtime(true);
        $this->lastDiagnostics = [
            'request' => [
                'merchant_id' => $merchant->id,
                'order_id' => $order?->id,
                'order_uuid' => $order?->uuid,
                'amount_toPrecision' => $amount->toPrecision(),
                'currency' => $currency?->getCode(),
                'detail_type' => $detailType?->value,
                'gateway_id' => $gateway?->id,
                'transgran' => $transgran,
            ],
            'internal' => null,
            'external' => [
                'providers_count' => 0,
                'providers' => [],
                'attempts' => [],
            ],
        ];
        
        Log::error('[RequisiteProviderChain] Starting requisite search', [
            'merchant_id' => $merchant->id,
            'order_id' => $order?->id,
            'order_uuid' => $order?->uuid,
            'amount' => $amount->toBeauty(),
            'currency' => $currency?->getCode(),
            'detail_type' => $detailType?->value,
            'gateway_id' => $gateway?->id,
        ]);

        // Шаг 1: Internal провайдер (БЕЗ фильтрации по настройкам терминала)
        if ($internal = $this->getInternalProvider()) {
            // Internal провайдер проверяет только базовые условия (isAvailable)
            // НЕ применяем фильтры терминалов (мерчант, сумма и т.д.)
            if ($internal->isAvailable()) {
                try {
                    $this->lastDiagnostics['internal'] = [
                        'available' => true,
                        'provider' => $internal->getName(),
                        'result' => 'started',
                    ];
                    $detail = $internal->getRequisites(
                        merchant: $merchant,
                        market: $market,
                        amount: $amount,
                        detailType: $detailType,
                        currency: $currency,
                        gateway: $gateway,
                        transgran: $transgran,
                        order: $order
                    );

                    if ($detail !== null) {
                        $this->lastDiagnostics['internal']['result'] = 'succeeded';
                        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
                        Log::error('[RequisiteProviderChain] Internal provider succeeded', [
                            'provider' => $internal->getName(),
                            'merchant_id' => $merchant->id,
                            'elapsed_ms' => $elapsed,
                        ]);
                        return $detail;
                    }

                    $this->lastDiagnostics['internal']['result'] = 'returned_null';
                    Log::error('[RequisiteProviderChain] Internal provider returned null');
                } catch (\Exception $e) {
                    $this->lastDiagnostics['internal'] = [
                        'available' => true,
                        'provider' => $internal->getName(),
                        'result' => 'failed',
                        'error' => $e->getMessage(),
                        'exception' => get_class($e),
                    ];
                    Log::error('[RequisiteProviderChain] Internal provider failed', [
                        'provider' => $internal->getName(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Шаг 2: Внешние провайдеры (С фильтрацией по настройкам терминала)
        $activeExternal = $this->getActiveExternalProviders($amount, $currency, $gateway, $detailType, $transgran, $merchant);
        $this->lastDiagnostics['external']['providers_count'] = $activeExternal->count();
        $this->lastDiagnostics['external']['providers'] = $activeExternal->map(function ($p) {
            return [
                'name' => $p->getName(),
                'priority' => $p->getPriority(),
                'provider_id' => method_exists($p, 'getConfig') ? ($p->getConfig()['provider_id'] ?? null) : null,
                'provider_terminal_id' => method_exists($p, 'getConfig') ? ($p->getConfig()['provider_terminal_id'] ?? null) : null,
            ];
        })->values()->all();

        if ($activeExternal->isEmpty()) {
            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('[RequisiteProviderChain] No suitable external providers', [
                'merchant_id' => $merchant->id,
                'elapsed_ms' => $elapsed,
            ]);

            $activeTerminalsCount = $this->selector->getActiveTerminals()->count();
            Log::error('[RequisiteProviderChain] Diagnostics: external providers empty', [
                'merchant_id' => $merchant->id,
                'amount_precision' => $amount->toPrecision(),
                'amount_value_float' => (float) $amount->toPrecision(),
                'currency' => $currency?->getCode(),
                'detail_type' => $detailType?->value,
                'gateway_id' => $gateway?->id,
                'active_terminals_cached_count' => $activeTerminalsCount,
                'hint' => 'If you expected METHODPAY to be tried: check provider/provider_terminal is_active, terminal merchant binding (provider_terminal_merchant.is_active), enabled_detail_types, min_sum/max_sum, and balance limits.',
            ]);
            return null;
        }

        Log::error('[RequisiteProviderChain] Found external providers', [
            'count' => $activeExternal->count(),
            'providers' => $activeExternal->map(fn($p) => $p->getName())->all(),
        ]);

        foreach ($activeExternal as $provider) {
            Log::error('[RequisiteProviderChain] Trying external provider', [
                'provider' => $provider->getName(),
                'priority' => $provider->getPriority(),
                'merchant_id' => $merchant->id,
                'order_uuid' => $order?->uuid,
            ]);

            try {
                $detail = $provider->getRequisites(
                    merchant: $merchant,
                    market: $market,
                    amount: $amount,
                    detailType: $detailType,
                    currency: $currency,
                    gateway: $gateway,
                    transgran: $transgran,
                    order: $order
                );

                if ($detail !== null) {
                    // Проставляем связку с внешним провайдером в detail,
                    // чтобы OrderDetailAssigner мог сохранить provider_id/provider_terminal_id в orders.
                    $detail->providerId = method_exists($provider, 'getProviderId') ? $provider->getProviderId() : null;
                    $detail->providerTerminalId = method_exists($provider, 'getProviderTerminalId') ? $provider->getProviderTerminalId() : null;

                    $this->lastDiagnostics['external']['attempts'][] = [
                        'provider' => $provider->getName(),
                        'priority' => $provider->getPriority(),
                        'result' => 'succeeded',
                        'debug' => method_exists($provider, 'getLastAttemptMeta') ? $provider->getLastAttemptMeta() : null,
                    ];
                    $elapsed = round((microtime(true) - $startTime) * 1000, 2);
                Log::error('[RequisiteProviderChain] External provider succeeded', [
                        'provider' => $provider->getName(),
                        'merchant_id' => $merchant->id,
                        'detail_id' => $detail->id,
                        'elapsed_ms' => $elapsed,
                    ]);
                    return $detail;
                }

                $this->lastDiagnostics['external']['attempts'][] = [
                    'provider' => $provider->getName(),
                    'priority' => $provider->getPriority(),
                    'result' => 'returned_null',
                    'debug' => method_exists($provider, 'getLastAttemptMeta') ? $provider->getLastAttemptMeta() : null,
                ];
                Log::error('[RequisiteProviderChain] External provider returned null', [
                    'provider' => $provider->getName(),
                ]);

            } catch (\Exception $e) {
                $this->lastDiagnostics['external']['attempts'][] = [
                    'provider' => $provider->getName(),
                    'priority' => $provider->getPriority(),
                    'result' => 'failed',
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'debug' => method_exists($provider, 'getLastAttemptMeta') ? $provider->getLastAttemptMeta() : null,
                ];
                Log::error('[RequisiteProviderChain] External provider failed', [
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        Log::error('[RequisiteProviderChain] All providers exhausted', [
            'merchant_id' => $merchant->id,
            'tried_count' => $activeExternal->count(),
            'elapsed_ms' => $elapsed,
        ]);

        return null;
    }

    /**
     * Получить статистику по провайдерам
     */
    public function getProvidersStats(): array
    {
        return $this->providers->map(function (RequisiteProviderContract $provider) {
            return [
                'name' => $provider->getName(),
                'priority' => $provider->getPriority(),
                'available' => $provider->isAvailable(),
                'config' => $provider->getConfig(),
            ];
        })->toArray();
    }

    /**
     * Сортировка провайдеров по приоритету (меньше число = выше приоритет)
     */
    protected function sortProviders(): void
    {
        $this->providers = $this->providers->sortBy(fn(RequisiteProviderContract $provider) => $provider->getPriority());
    }

    /**
     * Инвалидировать цепочку и кеш конфигураций
     */
    public static function invalidate(): void
    {
        ProviderSelector::invalidateCache();
        app()->forgetInstance(self::class);
    }
    
    /**
     * Получить селектор провайдеров
     */
    public function getSelector(): ProviderSelector
    {
        return $this->selector;
    }
} 