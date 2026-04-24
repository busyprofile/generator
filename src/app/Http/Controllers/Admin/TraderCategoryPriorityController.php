<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\TraderCategory;
use App\Models\MerchantTraderCategoryPriority;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TraderCategoryPriorityController extends Controller
{
    /**
     * Список мерчантов с назначенными категориями
     */
    public function index()
    {
        $merchants = Merchant::with(['user', 'traderCategoryPriorities.traderCategory'])
            ->withCount('traderCategoryPriorities as priorities_count')
            ->paginate(150);

        $categories = TraderCategory::active()
            ->withCount(['traders', 'activeTraders'])
            ->get();

        return Inertia::render('TraderCategoryPriority/Index', [
            'merchants' => $merchants,
            'categories' => $categories,
        ]);
    }

    /**
     * Управление приоритетами для конкретного мерчанта
     */
    public function show(Merchant $merchant)
    {
        // Доступные категории
        $availableCategories = TraderCategory::active()
            ->withCount('activeTraders')
            ->select('id', 'name', 'slug')
            ->get();

        // Назначенные категории с приоритетами
        $assignedCategories = $merchant->traderCategories()
            ->withCount('activeTraders')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'trader_category_id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'priority' => $category->pivot->priority,
                    'active_traders_count' => $category->active_traders_count,
                    'trader_category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ]
                ];
            })
            ->sortBy('priority')
            ->values();

        // Если это AJAX запрос, возвращаем JSON
        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'merchant' => $merchant->only(['id', 'name']),
                'available_categories' => $availableCategories,
                'assigned_categories' => $assignedCategories,
            ]);
        }

        return Inertia::render('Admin/TraderCategoryPriorities/Show', [
            'merchant' => $merchant->only(['id', 'name']),
            'available_categories' => $availableCategories,
            'assigned_categories' => $assignedCategories,
        ]);
    }

    /**
     * Сохранить приоритеты для мерчанта
     */
    public function update(Request $request, Merchant $merchant)
    {
        $request->validate([
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'exists:trader_categories,id'],
            'categories.*.priority' => ['required', 'integer', 'min:0'],
        ]);

        // Удаляем старые приоритеты
        $merchant->traderCategoryPriorities()->delete();

        // Создаем новые приоритеты
        foreach ($request->categories as $categoryData) {
            MerchantTraderCategoryPriority::create([
                'merchant_id' => $merchant->id,
                'trader_category_id' => $categoryData['id'],
                'priority' => $categoryData['priority'],
            ]);
        }

        return redirect()->back()->with('success', 'Приоритеты категорий обновлены');
    }

    /**
     * Получить приоритеты для конкретного мерчанта (API)
     */
    public function getPriorities(Merchant $merchant)
    {
        $priorities = $merchant->traderCategories()
            ->withCount('activeTraders')
            ->orderByPivot('priority')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'priority' => $category->pivot->priority,
                    'active_traders_count' => $category->active_traders_count,
                ];
            });

        return response()->json($priorities);
    }

    /**
     * Добавить категорию в приоритеты мерчанта
     */
    public function addCategory(Request $request, Merchant $merchant)
    {
        $request->validate([
            'category_id' => ['required', 'exists:trader_categories,id'],
        ]);

        // Проверяем, что категория еще не назначена
        if ($merchant->traderCategories()->where('trader_category_id', $request->category_id)->exists()) {
            return response()->json(['error' => 'Категория уже назначена'], 400);
        }

        // Получаем максимальный приоритет и добавляем новый с приоритетом +1
        $maxPriority = $merchant->traderCategoryPriorities()->max('priority') ?? -1;

        MerchantTraderCategoryPriority::create([
            'merchant_id' => $merchant->id,
            'trader_category_id' => $request->category_id,
            'priority' => $maxPriority + 1,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Удалить категорию из приоритетов мерчанта
     */
    public function removeCategory(Request $request, Merchant $merchant)
    {
        $request->validate([
            'category_id' => ['required', 'exists:trader_categories,id'],
        ]);

        $merchant->traderCategoryPriorities()
            ->where('trader_category_id', $request->category_id)
            ->delete();

        return response()->json(['success' => true]);
    }
} 