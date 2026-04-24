<?php

namespace App\Http\Requests\Admin\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'promo_code' => ['nullable', 'string', 'exists:promo_codes,code'],
            'target_reserve_amount' => ['nullable', 'integer', 'min:0'],
            'trader_category_id' => ['nullable', 'integer', 'exists:trader_categories,id'],
            'trader_team_leaders' => ['nullable', 'array'],
            'trader_team_leaders.*.team_leader_id' => [
                'required_with:trader_team_leaders',
                'integer', 
                'exists:users,id',
            ],
            'trader_team_leaders.*.commission_percentage' => ['required_with:trader_team_leaders.*.team_leader_id', 'numeric', 'min:0', 'max:100'],
            'trader_team_leaders.*.is_primary' => ['required_with:trader_team_leaders.*.team_leader_id', 'boolean'],
        ];
    }

    public function attributes()
    {
        return [
            'role_id' => __('роль'),
            'promo_code' => __('промокод'),
            'trader_team_leaders' => __('тимлидеры трейдера'),
            'trader_team_leaders.*.team_leader_id' => __('ID тимлидера'),
            'trader_team_leaders.*.commission_percentage' => __('процент комиссии'),
            'trader_team_leaders.*.is_primary' => __('основной тимлидер'),
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Проверка для дополнительных тимлидеров (старый метод)
            $teamLeaderIds = $this->input('additional_team_leader_ids', []);

            if (!empty($teamLeaderIds)) {
                // Проверяем роли для выбранных ID
                $validTeamLeadersCount = User::whereIn('id', $teamLeaderIds)
                    ->whereHas('roles', fn($q) => $q->where('name', 'Team Leader'))
                    ->count();

                if ($validTeamLeadersCount !== count($teamLeaderIds)) {
                    // Если количество найденных тимлидеров не совпадает с количеством переданных ID,
                    // значит, некоторые из переданных ID не являются тимлидерами.
                    $validator->errors()->add(
                        'additional_team_leader_ids',
                        'Один или несколько выбранных пользователей не являются тимлидерами.'
                    );
                }
            }

            // Проверка для новой системы тимлидеров
            $traderTeamLeaders = $this->input('trader_team_leaders', []);
            
            if (!empty($traderTeamLeaders)) {
                // Убираем проверку на одного основного тимлидера, т.к. теперь все тимлидеры основные
                
                // Проверяем, что все ID принадлежат тимлидерам
                $teamLeaderIds = array_column($traderTeamLeaders, 'team_leader_id');
                $teamLeaderIds = array_filter($teamLeaderIds); // Удаляем пустые значения
                
                if (!empty($teamLeaderIds)) {
                    $validTeamLeadersCount = User::whereIn('id', $teamLeaderIds)
                        ->whereHas('roles', fn($q) => $q->where('name', 'Team Leader'))
                        ->count();
                        
                    if ($validTeamLeadersCount !== count($teamLeaderIds)) {
                        $validator->errors()->add(
                            'trader_team_leaders',
                            'Один или несколько выбранных пользователей не являются тимлидерами.'
                        );
                    }
                }
            }
        });
    }
}
