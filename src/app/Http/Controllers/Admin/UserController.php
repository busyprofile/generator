<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreRequest;
use App\Http\Requests\Admin\User\UpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\TraderCategory;
use App\Utils\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();

        $users = User::query()
            ->with(['roles', 'wallet'])
            ->when($filters->user, function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('email', 'like', '%' . $filters->user . '%');
                    $query->orWhere('name', 'like', '%' . $filters->user . '%');
                });
            })
            ->when($filters->online, function ($query) use ($filters) {
                $query->where('is_online', true);
            })
            ->when($filters->traffic_disabled, function ($query) use ($filters) {
                $query->where('stop_traffic', true);
            })
            ->when(!empty($filters->roles), function ($query) use ($filters) {
                $query->whereHas('roles', function ($q) use ($filters) {
                    $q->whereIn('name', $filters->roles);
                });
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);

        $users = UserResource::collection($users);

        // Получаем данные для фильтров
        $filtersVariants = $this->getFiltersData();

        return Inertia::render('User/Index', compact('users', 'filters', 'filtersVariants'));
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'Merchant Support')->get();
        $traderCategories = TraderCategory::active()->orderBy('name')->get();

        return Inertia::render('User/Create', compact('roles', 'traderCategories'));
    }

    public function store(StoreRequest $request)
    {
        Transaction::run(function () use ($request) {
            $promoCodeId = null;
            $promoUsedAt = null;

            if ($request->promo_code) {
                $promoCode = PromoCode::where('code', $request->promo_code)->first();
                if ($promoCode && $promoCode->canBeUsed()) {
                    $promoCodeId = $promoCode->id;
                    $promoUsedAt = now();
                }
            }

            $roleName = Role::find($request->role_id)?->name;

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'apk_access_token' => strtolower(Str::random(32)),
                'api_access_token' => strtolower(Str::random(32)),
                'avatar_uuid' => $request->email,
                'avatar_style' => 'adventurer',
                'promo_code_id' => $promoCodeId,
                'promo_used_at' => $promoUsedAt,
                'traffic_enabled_at' => now(),
                'trader_category_id' => $request->trader_category_id,
            ]);

            $user->assignRole($request->role_id);

            // Получаем target_reserve_amount из запроса, если он есть
            $targetReserveAmount = $request->input('target_reserve_amount');

            // Если роль - Трейдер и targetReserveAmount не задан явно, можно установить дефолтное значение
            // или оставить null, чтобы WalletService использовал свой дефолт.
            // В данном случае, если не придет, WalletService поставит Wallet::RESERVE_BALANCE (1000)
            // Если придет null или пустое значение, оно также будет обработано как null.
            if ($roleName !== 'Trader') { // Если не трейдер, то это поле нерелевантно для кошелька в этом контексте
                $targetReserveAmount = null;
            }

            services()->wallet()->create($user, $targetReserveAmount ? (int)$targetReserveAmount : null);

            if ($promoCodeId && $promoCode) {
                $promoCode->incrementUsedCount();
            }
            
            // Если создается трейдер и указаны тимлидеры, сохраняем связи
            if (in_array('Trader', (array)$request->role_id) && isset($request->trader_team_leaders)) {
                $this->saveTraderTeamLeaders($user, $request->trader_team_leaders);
            }
        });

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        // Загружаем promoCode вместе с teamLeader и его процентом
        $user->load(['roles', 'meta', 'promoCode.teamLeader', 'traderCategory']); 
        $roles = Role::where('name', '!=', 'Merchant Support')->get();
        $traderCategories = TraderCategory::active()->orderBy('name')->get();

        // Загружаем список тимлидеров для мультиселекта
        $teamLeadersList = User::role('Team Leader') 
            ->where('id', '!=', $user->id) 
            ->select('id', 'name', 'email', 'referral_commission_percentage')
            ->orderBy('name')
            ->get();
        
        // Загружаем связи с тимлидерами для трейдера
        $traderTeamLeaders = [];
        if ($user->hasRole('Trader')) {
            $traderTeamLeaders = \App\Models\TraderTeamLeaderRelation::where('trader_id', $user->id)
                ->with(['teamLeader:id,name,email'])
                ->get()
                ->map(function ($relation) {
                    return [
                        'id' => $relation->id,
                        'team_leader_id' => $relation->team_leader_id,
                        'commission_percentage' => $relation->commission_percentage,
                        'is_primary' => $relation->is_primary,
                        'team_leader_name' => $relation->teamLeader->name,
                        'team_leader_email' => $relation->teamLeader->email,
                    ];
                });
        }

        // Загружаем связи с тимлидерами для мерчанта
        $merchantTeamLeaders = [];
        if ($user->hasRole('Merchant')) {
            // Находим запись мерчанта в таблице merchants
            $merchantRecord = \App\Models\Merchant::where('user_id', $user->id)->first();
            
            if ($merchantRecord) {
                \Log::debug('Загружаем тимлидеров мерчанта', [
                    'user_id' => $user->id,
                    'merchant_id' => $merchantRecord->id
                ]);
                
                // Получаем тимлидеров для этого merchant_id
                $relations = \DB::table('merchant_team_leader_relations as m')
                    ->where('m.merchant_id', $merchantRecord->id)
                    ->join('users as u', 'u.id', '=', 'm.team_leader_id')
                    ->select('u.id', 'u.name', 'u.email', 'm.commission_percentage', 'm.is_primary')
                    ->get();
                
                $merchantTeamLeaders = $relations->map(function ($relation) {
                    return [
                        'team_leader_id' => $relation->id,
                        'commission_percentage' => $relation->commission_percentage,
                        'is_primary' => (bool)$relation->is_primary,
                        'team_leader_name' => $relation->name,
                        'team_leader_email' => $relation->email,
                    ];
                });
            } else {
                \Log::warning('Не удалось найти запись в таблице merchants для пользователя с ID: ' . $user->id);
            }
        }

        $user = UserResource::make($user)->resolve();

        return Inertia::render('User/Edit', compact('user', 'roles', 'traderCategories', 'teamLeadersList', 'traderTeamLeaders', 'merchantTeamLeaders'));
    }

    public function update(UpdateRequest $request, User $user)
    {
        Transaction::run(function () use ($request, $user) {
            // Получаем текущее состояние stop_traffic
            $wasTrafficStopped = $user->stop_traffic;
            
            // Получаем валидированные данные
            $validated = $request->validated();
            
            \Log::debug('Обновление пользователя', [
                'user_id' => $user->id,
                'has_trader_team_leaders' => isset($validated['trader_team_leaders']),
                'has_merchant_team_leaders' => isset($validated['merchant_team_leaders']),
                'is_trader' => $user->hasRole('Trader') || in_array('Trader', (array)$validated['role_id']),
                'is_merchant' => $user->hasRole('Merchant') || in_array('Merchant', (array)$validated['role_id']),
                'trader_team_leaders_raw' => $validated['trader_team_leaders'] ?? null,
                'merchant_team_leaders_raw' => $validated['merchant_team_leaders'] ?? null
            ]);

            // Шаг 1: Обновляем все поля, кроме additional_team_leader_ids
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'banned_at' => $validated['banned'] ? now() : null,
                'payouts_enabled' => $validated['payouts_enabled'],
                'stop_traffic' => $validated['stop_traffic'],
                'is_vip' => $validated['is_vip'],
                'referral_commission_percentage' => $validated['referral_commission_percentage'],
                'trader_commission_rate' => $validated['trader_commission_rate'] ?? null, 
                'trader_category_id' => $validated['trader_category_id'] ?? null,
                'traffic_enabled_at' => $wasTrafficStopped && !$validated['stop_traffic'] ? now() : $user->traffic_enabled_at,
            ]);

            // Для обратной совместимости сохраняем additional_team_leader_ids
            $user->additional_team_leader_ids = $validated['additional_team_leader_ids'] ?? null;
            $user->save();

            // Обработка промокода
            if (!$user->promo_code_id && !empty($validated['promo_code'])) {
                $promoCode = PromoCode::where('code', $validated['promo_code'])->first();
                if ($promoCode && $promoCode->canBeUsed()) {
                    $user->update([
                        'promo_code_id' => $promoCode->id,
                        'promo_used_at' => now(),
                    ]);
                    $promoCode->incrementUsedCount();
                }
            }

            // Синхронизация ролей
            if ($user->id !== 1) {
                $user->syncRoles($validated['role_id']);
            }

            // Если пользователь является трейдером, обрабатываем его связи с тимлидерами
            $isTrader = $user->hasRole('Trader') || (isset($validated['role_id']) && in_array('Trader', (array)$validated['role_id']));
            
            if (isset($validated['trader_team_leaders']) && $isTrader) {
                \Log::debug('Сохраняем связи трейдер-тимлидер из формы обновления пользователя', [
                    'count' => count($validated['trader_team_leaders']),
                    'trader_team_leaders' => json_encode($validated['trader_team_leaders'])
                ]);
                
                // Передаем данные тимлидеров напрямую в метод сохранения
                $teamLeadersData = $validated['trader_team_leaders'];
                $this->saveTraderTeamLeaders($user, $teamLeadersData);
            }

            // Если пользователь является мерчантом, обрабатываем его связи с тимлидерами
            $isMerchant = $user->hasRole('Merchant') || (isset($validated['role_id']) && in_array('Merchant', (array)$validated['role_id']));
            
            if (isset($validated['merchant_team_leaders']) && $isMerchant) {
                \Log::debug('Сохраняем связи мерчант-тимлидер из формы обновления пользователя для всех его мерчантов', [
                    'user_id' => $user->id,
                    'count_team_leaders_in_request' => count($validated['merchant_team_leaders']),
                    'merchant_team_leaders_data' => json_encode($validated['merchant_team_leaders'])
                ]);
                
                // Получаем все записи мерчантов для текущего пользователя
                $merchantRecords = \App\Models\Merchant::where('user_id', $user->id)->get();
                
                if ($merchantRecords->isNotEmpty()) {
                    foreach ($merchantRecords as $merchantRecord) {
                        \Log::info('Применение тимлидеров для мерчанта', ['merchant_id' => $merchantRecord->id, 'user_id' => $user->id]);
                        // Удаляем существующие связи для данного мерчанта
                        $merchantRecord->teamLeaders()->detach();

                        // Создаем новые связи для данного мерчанта
                        foreach ($validated['merchant_team_leaders'] as $teamLeaderData) {
                            if (isset($teamLeaderData['team_leader_id']) && isset($teamLeaderData['commission_percentage'])) {
                                \App\Models\MerchantTeamLeaderRelation::create([
                                    'merchant_id' => $merchantRecord->id,
                                    'team_leader_id' => $teamLeaderData['team_leader_id'],
                                    'commission_percentage' => $teamLeaderData['commission_percentage'],
                                    'is_primary' => true,
                                ]);
                            } else {
                                \Log::warning('Отсутствуют необходимые данные для сохранения тимлидера для мерчанта', [
                                    'merchant_id' => $merchantRecord->id,
                                    'user_id' => $user->id,
                                    'data' => $teamLeaderData
                                ]);
                            }
                        }
                    }
                } else {
                    \Log::warning('Не удалось найти мерчантов для пользователя при сохранении тимлидеров из формы пользователя', ['user_id' => $user->id]);
                }
            }

            // Обновление платежных деталей при бане
            if ($user->banned_at) {
                $user->paymentDetails()->update([
                    'is_active' => false
                ]);
            }
        });

        return redirect()->route('admin.users.index');
    }

    /**
     * Сохраняет связь трейдера с тимлидерами.
     *
     * @param User $user Трейдер
     * @param array $teamLeaders Массив данных о тимлидерах
     * @return void
     */
    protected function saveTraderTeamLeaders(User $user, array $teamLeadersData): void
    {
        \Log::info('Сохранение тимлидеров для трейдера с использованием sync', [
            'trader_id' => $user->id, 
            'team_leaders_data' => $teamLeadersData
        ]);
        
        $syncData = [];
        foreach ($teamLeadersData as $relation) {
            if (empty($relation['team_leader_id'])) {
                continue;
            }
            // Убедимся, что commission_percentage является числом
            $commissionPercentage = isset($relation['commission_percentage']) ? (float)$relation['commission_percentage'] : 0.0;

            $syncData[$relation['team_leader_id']] = [
                'commission_percentage' => $commissionPercentage,
                'is_primary' => $relation['is_primary'] ?? true, // По умолчанию true, если не передано
            ];
        }
        
        \Log::debug('Данные для синхронизации (трейдер-тимлидер)', ['sync_data' => $syncData]);
        
        // Используем sync для обновления связей.
        // Это сохранит created_at для существующих связей и обновит updated_at.
        // Новые связи будут созданы с текущими created_at и updated_at.
        // Связи, отсутствующие в $syncData, будут удалены.
        $user->teamLeaders()->sync($syncData);
    }

    public function toggleOnline(Request $request, User $user)
    {
        if ((int)$user->is_online !== (int)$request->is_online) {
            if ($user->stop_traffic && (int)$request->is_online) {
                return;
            }

            $user->update(['is_online' => !$user->is_online]);
        }
        if ((int)$user->is_payout_online !== (int)$request->is_payout_online) {
            services()->payout()->toggleTraderOffersActivity($user);
        }
    }

    public function reset2fa(User $user)
    {
        $user->update([
            'google2fa_secret' => null,
        ]);

        return redirect()->back()->with('success', 'Двухфакторная аутентификация успешно сброшена');
    }

    /**
     * Получить список всех тимлидеров для выбора в форме
     */
    public function getTeamLeaders()
    {
        $this->authorize('viewAny', User::class);
        
        $teamLeaderRole = \Spatie\Permission\Models\Role::findByName('Team Leader');
        $teamLeaders = User::role($teamLeaderRole)
            ->select('id', 'name', 'email', 'referral_commission_percentage')
            ->get();
        
        return response()->json($teamLeaders);
    }

    /**
     * Получить тимлидеров конкретного пользователя
     */
    public function getUserTeamLeaders(User $user)
    {
        $this->authorize('view', $user);
        
        \Log::debug('Запрошены тимлидеры для пользователя', ['user_id' => $user->id]);
        
        $teamLeaders = \App\Models\TraderTeamLeaderRelation::where('trader_id', $user->id)
            ->with(['teamLeader:id,name,email'])
            ->get()
            ->map(function ($relation) {
                \Log::debug('Найдена связь трейдер-тимлидер', ['relation_id' => $relation->id]);
                return [
                    'id' => $relation->id,
                    'team_leader_id' => $relation->team_leader_id,
                    'commission_percentage' => $relation->commission_percentage,
                    'is_primary' => $relation->is_primary,
                    'team_leader_name' => $relation->teamLeader->name,
                    'team_leader_email' => $relation->teamLeader->email,
                ];
            });
        
        \Log::debug('Возвращаем список тимлидеров', ['count' => $teamLeaders->count()]);
        
        return response()->json($teamLeaders);
    }

    /**
     * Сохранить тимлидеров для пользователя
     */
    public function saveUserTeamLeaders(Request $request, User $user)
    {
        $this->authorize('update', $user);
        
        \Log::debug('Запрос на сохранение тимлидеров', [
            'user_id' => $user->id,
            'data' => $request->all(),
            'team_leaders_raw' => $request->input('team_leaders')
        ]);
        
        $request->validate([
            'team_leaders' => 'required|array',
            'team_leaders.*.team_leader_id' => 'required|exists:users,id',
            'team_leaders.*.commission_percentage' => 'required|numeric|min:0|max:100',
            'team_leaders.*.is_primary' => 'required|boolean',
        ]);
        
        // Проверяем, что есть только один основной тимлидер
        $primaryCount = collect($request->team_leaders)->where('is_primary', true)->count();
        \Log::debug('Количество основных тимлидеров: ' . $primaryCount);
        
        if ($primaryCount !== 1 && count($request->team_leaders) > 0) {
            \Log::warning('Ошибка валидации: неверное количество основных тимлидеров', ['primary_count' => $primaryCount]);
            return response()->json(['message' => 'Должен быть ровно один основной тимлидер'], 422);
        }
        
        // Транзакция для сохранения данных
        \App\Utils\Transaction::run(function() use ($user, $request) {
            // Удаляем все существующие связи
            $deleted = \App\Models\TraderTeamLeaderRelation::where('trader_id', $user->id)->delete();
            \Log::debug('Удалены существующие связи', ['deleted_count' => $deleted]);
            
            $createdCount = 0;
            
            // Создаем новые связи с обязательными полями
            foreach ($request->team_leaders as $data) {
                // Проверяем наличие обязательных полей
                if (isset($data['team_leader_id']) && !empty($data['team_leader_id'])) {
                    $relationData = [
                        'trader_id' => $user->id,
                        'team_leader_id' => $data['team_leader_id'],
                        'commission_percentage' => $data['commission_percentage'] ?? 0,
                        'is_primary' => $data['is_primary'] ?? false
                    ];
                    
                    \Log::debug('Создание связи', $relationData);
                    
                    $relation = \App\Models\TraderTeamLeaderRelation::create($relationData);
                    \Log::debug('Создана новая связь', ['relation_id' => $relation->id]);
                    $createdCount++;
                } else {
                    \Log::warning('Пропущена некорректная запись', $data);
                }
            }
            
            \Log::debug("Создано новых связей: {$createdCount}");
        });
        
        return response()->json(['message' => 'Тимлидеры успешно сохранены']);
    }
}
