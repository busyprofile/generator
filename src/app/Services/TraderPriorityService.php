<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\User;
use App\Models\TraderCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class TraderPriorityService
{
    /**
     * Получить трейдеров для мерчанта по приоритетам категорий
     */
    public function getTradersForMerchant(Merchant $merchant, array $filters = []): Collection
    {
        // Получаем приоритеты категорий для мерчанта
        $priorities = $merchant->traderCategoryPriorities()
            ->with('traderCategory')
            ->orderBy('priority')
            ->get();

        if ($priorities->isEmpty()) {
            // Если у мерчанта нет настроенных приоритетов, возвращаем всех активных трейдеров
            return $this->getActiveTraders($filters);
        }

        $traders = new Collection();

        // Проходим по категориям в порядке приоритета
        foreach ($priorities as $priority) {
            $categoryTraders = $this->getTradersFromCategory(
                $priority->traderCategory,
                $filters,
                $traders->pluck('id')->toArray() // Исключаем уже добавленных трейдеров
            );

            // Объединяем коллекции правильно
            foreach ($categoryTraders as $trader) {
                $traders->push($trader);
            }
        }

        // Добавляем трейдеров без категории в конце
        $tradersWithoutCategory = $this->getTradersWithoutCategory(
            $filters,
            $traders->pluck('id')->toArray()
        );

        // Объединяем коллекции правильно
        foreach ($tradersWithoutCategory as $trader) {
            $traders->push($trader);
        }

        return $traders;
    }

    /**
     * Получить активных трейдеров из определенной категории
     */
    public function getTradersFromCategory(
        TraderCategory $category, 
        array $filters = [], 
        array $excludeIds = []
    ): Collection {
        $query = $category->activeTraders();

        $this->applyFilters($query, $filters);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->get();
    }

    /**
     * Получить трейдеров без категории
     */
    public function getTradersWithoutCategory(array $filters = [], array $excludeIds = []): Collection
    {
        $query = User::role('Trader')
            ->whereNull('trader_category_id')
            ->where('is_online', true)
            ->where('stop_traffic', false)
            ->whereNull('banned_at');

        $this->applyFilters($query, $filters);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->get();
    }

    /**
     * Получить всех активных трейдеров (когда нет приоритетов)
     */
    public function getActiveTraders(array $filters = []): Collection
    {
        $query = User::role('Trader')
            ->where('is_online', true)
            ->where('stop_traffic', false)
            ->whereNull('banned_at');

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Применить фильтры к запросу трейдеров
     */
    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['is_online'])) {
            $query->where('is_online', $filters['is_online']);
        }

        if (isset($filters['stop_traffic'])) {
            $query->where('stop_traffic', $filters['stop_traffic']);
        }

        if (isset($filters['banned_at'])) {
            if (is_null($filters['banned_at'])) {
                $query->whereNull('banned_at');
            } else {
                $query->where('banned_at', $filters['banned_at']);
            }
        }

        // Фильтр по минимальному балансу доверия
        if (isset($filters['min_trust_balance'])) {
            $query->whereHas('wallet', function ($q) use ($filters) {
                $q->where('trust_balance', '>=', $filters['min_trust_balance']);
            });
        }

        // Фильтр по максимальному количеству pending споров
        if (isset($filters['max_pending_disputes']) && $filters['max_pending_disputes'] > 0) {
            $query->withCount(['disputes as pending_disputes_count' => function ($q) {
                $q->where('status', \App\Enums\DisputeStatus::PENDING);
            }])
            ->having('pending_disputes_count', '<', $filters['max_pending_disputes']);
        }

        // Дополнительные фильтры можно добавить здесь
        if (isset($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        if (isset($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }
    }

    /**
     * Получить статистику по категориям трейдеров для мерчанта
     */
    public function getCategoryStatistics(Merchant $merchant): array
    {
        $priorities = $merchant->traderCategoryPriorities()
            ->with('traderCategory')
            ->orderBy('priority')
            ->get();

        $statistics = [];

        foreach ($priorities as $priority) {
            $category = $priority->traderCategory;
            $activeTraders = $category->activeTraders()->count();
            $totalTraders = $category->traders()->count();

            $statistics[] = [
                'id' => $category->id,
                'name' => $category->name,
                'priority' => $priority->priority,
                'active_traders' => $activeTraders,
                'total_traders' => $totalTraders,
                'percentage_active' => $totalTraders > 0 ? round(($activeTraders / $totalTraders) * 100, 1) : 0,
            ];
        }

        return $statistics;
    }

    /**
     * Обновить приоритеты категорий для мерчанта
     */
    public function updateMerchantPriorities(Merchant $merchant, array $priorities): bool
    {
        try {
            // Удаляем старые приоритеты
            $merchant->traderCategoryPriorities()->delete();

            // Создаем новые приоритеты
            foreach ($priorities as $index => $categoryId) {
                $merchant->traderCategoryPriorities()->create([
                    'trader_category_id' => $categoryId,
                    'priority' => $index,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Ошибка при обновлении приоритетов категорий', [
                'merchant_id' => $merchant->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
} 