<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromoCodeResource;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromoCodeController extends Controller
{
    public function index()
    {
        // Получаем фильтры из запроса
        $filters = $this->getTableFilters();

        // Строим запрос на получение промокодов
        $query = PromoCode::query()
            ->with('teamLeader') // Загружаем связанного тимлидера
            ->latest();

        // Применяем фильтры
        if (!empty($filters->search)) {
            $query->where('code', 'like', '%' . $filters->search . '%');
        }

        if (!empty($filters->user)) {
            $query->whereHas('teamLeader', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters->user . '%')
                  ->orWhere('email', 'like', '%' . $filters->user . '%');
            });
        }

        if ($filters->active) {
            $query->where('is_active', $filters->active);
        }

        // Пагинация результатов
        $promoCodes = $query->paginate(request()->per_page ?? 10);

        // Преобразуем результаты в ресурсы
        $promoCodes = PromoCodeResource::collection($promoCodes);

        // Получаем список тимлидеров для фильтра
        $teamLeaders = User::whereHas('roles', function ($q) {
            $q->where('name', 'team-leader');
        })->select('id', 'name')->get();

        // Используем ту же страницу, что и для тимлидера
        return Inertia::render('PromoCode/Index', [
            'promoCodes' => $promoCodes,
            'filters' => $filters,
            'teamLeaders' => $teamLeaders,
            'isAdmin' => true
        ]);
    }
}
