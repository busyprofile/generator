<?php

namespace App\Http\Requests\Admin\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'banned' => ['required', 'boolean'],
            'payouts_enabled' => ['required', 'boolean'],
            'stop_traffic' => ['required', 'boolean'],
            'is_vip' => ['required', 'boolean'],
            'referral_commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'trader_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'promo_code' => ['nullable', 'string', 'exists:promo_codes,code'],
            'additional_team_leader_ids' => ['nullable', 'array'],
            'additional_team_leader_ids.*' => [
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([$user->id]),
            ],
            'trader_category_id' => ['nullable', 'integer', 'exists:trader_categories,id'],
            'trader_team_leaders' => ['nullable', 'array'],
            'trader_team_leaders.*.team_leader_id' => [
                'required_with:trader_team_leaders',
                'integer', 
                Rule::exists('users', 'id'),
                Rule::notIn([$user->id]),
            ],
            'trader_team_leaders.*.commission_percentage' => ['required_with:trader_team_leaders.*.team_leader_id', 'numeric', 'min:0', 'max:100'],
            'trader_team_leaders.*.is_primary' => ['required_with:trader_team_leaders.*.team_leader_id', 'boolean'],
            'merchant_team_leaders' => ['nullable', 'array'],
            'merchant_team_leaders.*.team_leader_id' => [
                'required_with:merchant_team_leaders',
                'integer',
                Rule::exists('users', 'id'),
                Rule::notIn([$user->id]),
            ],
            'merchant_team_leaders.*.commission_percentage' => ['required_with:merchant_team_leaders.*.team_leader_id', 'numeric', 'min:0', 'max:100'],
            'merchant_team_leaders.*.is_primary' => ['required_with:merchant_team_leaders.*.team_leader_id', 'boolean'],
        ];
    }

    public function attributes()
    {
        return [
            'role_id' => __('роль'),
            'payouts_enabled' => __('функционал выплат'),
            'stop_traffic' => __('остановка трафика'),
            'is_vip' => __('VIP статус'),
            'referral_commission_percentage' => __('процент комиссии от рефералов'),
            'trader_commission_rate' => __('индивидуальная комиссия трейдера'),
            'promo_code' => __('промокод'),
            'additional_team_leader_ids' => __('дополнительные тимлидеры'),
            'trader_team_leaders' => __('тимлидеры трейдера'),
            'trader_team_leaders.*.team_leader_id' => __('ID тимлидера'),
            'trader_team_leaders.*.commission_percentage' => __('процент комиссии'),
            'trader_team_leaders.*.is_primary' => __('основной тимлидер'),
            'merchant_team_leaders' => __('тимлидеры мерчанта'),
            'merchant_team_leaders.*.team_leader_id' => __('ID тимлидера'),
            'merchant_team_leaders.*.commission_percentage' => __('процент комиссии'),
            'merchant_team_leaders.*.is_primary' => __('основной тимлидер'),
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

            // Проверка для новой системы тимлидеров трейдера
            $traderTeamLeaders = $this->input('trader_team_leaders', []);
            
            if (!empty($traderTeamLeaders)) {
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

            // Проверка для тимлидеров мерчанта
            $merchantTeamLeaders = $this->input('merchant_team_leaders', []);
            
            if (!empty($merchantTeamLeaders)) {
                // Проверяем, что все ID принадлежат тимлидерам
                $teamLeaderIds = array_column($merchantTeamLeaders, 'team_leader_id');
                $teamLeaderIds = array_filter($teamLeaderIds); // Удаляем пустые значения
                
                if (!empty($teamLeaderIds)) {
                    $validTeamLeadersCount = User::whereIn('id', $teamLeaderIds)
                        ->whereHas('roles', fn($q) => $q->where('name', 'Team Leader'))
                        ->count();
                        
                    if ($validTeamLeadersCount !== count($teamLeaderIds)) {
                        $validator->errors()->add(
                            'merchant_team_leaders',
                            'Один или несколько выбранных пользователей не являются тимлидерами.'
                        );
                    }
                }
            }
        });
    }
}
