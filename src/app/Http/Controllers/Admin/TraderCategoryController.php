<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TraderCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TraderCategoryController extends Controller
{
    /**
     * Список категорий трейдеров
     */
    public function index()
    {
        $categories = TraderCategory::withCount(['traders', 'activeTraders'])
            ->orderBy('name')
            ->paginate(10);

        return Inertia::render('TraderCategory/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Создание новой категории
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:trader_categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category = TraderCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back()->with('success', 'Категория трейдеров создана');
    }

    /**
     * Обновление категории
     */
    public function update(Request $request, TraderCategory $traderCategory)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('trader_categories', 'name')->ignore($traderCategory->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $traderCategory->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->back()->with('success', 'Категория трейдеров обновлена');
    }

    /**
     * Удаление категории
     */
    public function destroy(TraderCategory $traderCategory)
    {
        // Проверяем, есть ли трейдеры в этой категории
        if ($traderCategory->traders()->exists()) {
            return redirect()->back()->with('error', 'Нельзя удалить категорию, в которой есть трейдеры');
        }

        $traderCategory->delete();

        return redirect()->back()->with('success', 'Категория трейдеров удалена');
    }

    /**
     * Управление трейдерами в категории
     */
    public function manageTraders(TraderCategory $traderCategory)
    {
        // Трейдеры в категории
        $tradersInCategory = $traderCategory->traders()
            ->select('id', 'name', 'email')
            ->get();

        // Доступные трейдеры (только те, кто не находится ни в какой категории)
        $availableTraders = User::whereHas('roles', function ($query) {
                $query->where('name', 'Trader');
            })
            ->whereNull('trader_category_id') // Только трейдеры без категории
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('TraderCategory/ManageTraders', [
            'category' => $traderCategory,
            'tradersInCategory' => $tradersInCategory,
            'availableTraders' => $availableTraders,
        ]);
    }

    /**
     * Добавить трейдера в категорию
     */
    public function addTrader(Request $request, TraderCategory $traderCategory)
    {
        $request->validate([
            'trader_id' => ['required', 'exists:users,id'],
        ]);

        $trader = User::findOrFail($request->trader_id);
        
        // Проверяем, что это трейдер
        if (!$trader->hasRole('Trader')) {
            return redirect()->back()->with('error', 'Пользователь не является трейдером');
        }

        // Проверяем, что трейдер не находится уже в другой категории
        if ($trader->trader_category_id && $trader->trader_category_id != $traderCategory->id) {
            $currentCategory = TraderCategory::find($trader->trader_category_id);
            return redirect()->back()->with('error', "Трейдер {$trader->name} уже находится в категории \"{$currentCategory->name}\"");
        }

        // Проверяем, что трейдер не находится уже в этой категории
        if ($trader->trader_category_id == $traderCategory->id) {
            return redirect()->back()->with('error', "Трейдер {$trader->name} уже находится в этой категории");
        }

        $trader->update(['trader_category_id' => $traderCategory->id]);

        return redirect()->back()->with('success', "Трейдер {$trader->name} добавлен в категорию");
    }

    /**
     * Удалить трейдера из категории
     */
    public function removeTrader(Request $request, TraderCategory $traderCategory)
    {
        $request->validate([
            'trader_id' => ['required', 'exists:users,id'],
        ]);

        $trader = User::findOrFail($request->trader_id);
        $trader->update(['trader_category_id' => null]);

        return redirect()->back()->with('success', "Трейдер {$trader->name} удален из категории");
    }

    /**
     * Массовое добавление трейдеров в категорию
     */
    public function bulkAddTraders(Request $request, TraderCategory $traderCategory)
    {
        $request->validate([
            'trader_ids' => ['required', 'array'],
            'trader_ids.*' => ['required', 'exists:users,id'],
        ]);

        $traders = User::whereIn('id', $request->trader_ids)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Trader');
            })
            ->get();

        $addedCount = 0;
        $skippedTraders = [];

        foreach ($traders as $trader) {
            // Проверяем, что трейдер не находится уже в какой-либо категории
            if ($trader->trader_category_id) {
                if ($trader->trader_category_id == $traderCategory->id) {
                    $skippedTraders[] = "{$trader->name} (уже в этой категории)";
                } else {
                    $currentCategory = TraderCategory::find($trader->trader_category_id);
                    $skippedTraders[] = "{$trader->name} (уже в категории \"{$currentCategory->name}\")";
                }
                continue;
            }

            $trader->update(['trader_category_id' => $traderCategory->id]);
            $addedCount++;
        }

        $message = "Добавлено {$addedCount} трейдеров в категорию";
        if (!empty($skippedTraders)) {
            $message .= ". Пропущено: " . implode(', ', $skippedTraders);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Удалить всех трейдеров из категории
     */
    public function removeAllTraders(TraderCategory $traderCategory)
    {
        $count = $traderCategory->traders()->count();
        
        $traderCategory->traders()->update(['trader_category_id' => null]);

        return redirect()->back()->with('success', "Удалено {$count} трейдеров из категории");
    }
} 