<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProviderIntegrationEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Provider\StoreRequest;
use App\Http\Requests\Admin\Provider\UpdateRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProviderController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();

        $providers = Provider::query()
            ->when($filters->search, function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters->search}%")
                        ->orWhere('uuid', 'like', "%{$filters->search}%")
                        ->orWhere('trader_id', $filters->search);
                });
            })
            ->when(!is_null($filters->status), function ($query) use ($filters) {
                $query->where('is_active', $filters->status);
            })
            ->leftJoin('wallets', 'wallets.user_id', '=', 'providers.trader_id')
            ->select('providers.*', DB::raw('COALESCE(wallets.trust_balance, 0) as trusted_balance_cents'))
            ->withCount(['providerTerminals as provider_terminals_count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderByDesc('providers.id')
            ->paginate(request()->per_page ?? 15);

        $providers = ProviderResource::collection($providers);

        $filtersVariants = $this->getFiltersData();

        return Inertia::render('Admin/Providers/Index', [
            'providers' => $providers,
            'filters' => $filters,
            'filtersVariants' => $filtersVariants,
            'integrations' => collect(ProviderIntegrationEnum::cases())->map(fn($item) => [
                'label' => $item->value,
                'value' => $item->value,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Providers/Create', [
            'integrations' => collect(ProviderIntegrationEnum::cases())->map(fn($item) => [
                'label' => $item->value,
                'value' => $item->value,
            ]),
        ]);
    }

    public function edit(Provider $provider)
    {
        return Inertia::render('Admin/Providers/Edit', [
            'provider' => (new ProviderResource($provider))->resolve(),
            'integrations' => collect(ProviderIntegrationEnum::cases())->map(fn($item) => [
                'label' => $item->value,
                'value' => $item->value,
            ]),
        ]);
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['uuid'] = Str::uuid()->toString();
        $data['is_active'] = false; // Провайдер всегда создаётся неактивным

        Provider::create($data);

        return redirect()->route('admin.providers.index')
            ->with('success', 'Провайдер создан');
    }

    public function update(UpdateRequest $request, Provider $provider)
    {
        $data = $request->validated();

        $provider->update($data);
        Provider::clearCache();

        return redirect()->back()->with('success', 'Провайдер обновлен');
    }

    public function destroy(Provider $provider)
    {
        $provider->delete();
        Provider::clearCache();

        return redirect()->route('admin.providers.index')
            ->with('success', 'Провайдер удален');
    }

    public function toggle(Provider $provider)
    {
        $provider->update(['is_active' => !$provider->is_active]);
        Provider::clearCache();

        return redirect()->back()->with('success', 'Статус провайдера изменен');
    }
}
