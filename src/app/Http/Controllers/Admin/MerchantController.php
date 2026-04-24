<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MarketEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MerchantResource;
use App\Models\Category;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MerchantController extends Controller
{
    public function index()
    {
        $merchants = Merchant::query()
            ->with('user')
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);

        $merchants = MerchantResource::collection($merchants);

        return Inertia::render('Merchant/Index', compact('merchants'));
    }

    public function show(Merchant $merchant)
    {
        $merchant = new MerchantResource($merchant->load('categories'));
        $categories = CategoryResource::collection(Category::orderBy('name')->get())->resolve();
        $markets = MarketEnum::cases();

        return Inertia::render('Merchant/Show', compact('merchant', 'categories', 'markets'));
    }

    public function ban(Merchant $merchant)
    {
        $merchant->update([
            'banned_at' => now(),
            'validated_at' => now(),
        ]);
    }

    public function unban(Merchant $merchant)
    {
        $merchant->update([
            'banned_at' => null,
            'validated_at' => now(),
        ]);
    }

    public function validated(Merchant $merchant)
    {
        $merchant->update([
            'validated_at' => now(),
        ]);
    }

    public function updateSettings(Request $request, Merchant $merchant)
    {
        $request->validate([
            'market' => ['required', Rule::enum(MarketEnum::class)],
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'max_order_wait_time' => 'nullable|integer|min:1000',
            'min_order_amounts' => 'nullable|array',
            'min_order_amounts.*' => 'numeric|min:0',
            'max_order_amounts' => 'nullable|array',
            'max_order_amounts.*' => 'numeric|min:0',
        ]);

        // Проверка: max >= min для каждой валюты
        if ($request->min_order_amounts && $request->max_order_amounts) {
            foreach ($request->min_order_amounts as $currency => $min) {
                $max = $request->max_order_amounts[$currency] ?? null;
                if ($max !== null && $min !== null && $max < $min) {
                    return back()->withErrors(['max_order_amounts.' . $currency => 'Максимальная сумма не может быть меньше минимальной']);
                }
            }
        }

        $merchant->update([
            'market' => $request->market,
            'max_order_wait_time' => $request->max_order_wait_time,
            'min_order_amounts' => $request->min_order_amounts,
            'max_order_amounts' => $request->max_order_amounts,
        ]);

        if ($request->has('categories')) {
            $merchant->categories()->sync($request->categories);
        }

        return back()->with([
            'merchant' => new MerchantResource($merchant->fresh()->load('categories')),
        ]);
    }
}
