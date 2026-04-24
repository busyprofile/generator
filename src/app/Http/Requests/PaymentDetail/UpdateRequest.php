<?php

namespace App\Http\Requests\PaymentDetail;

use App\Enums\DetailType;
use App\Rules\CardNumberValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

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
        $isVipUser = Auth::check() && Auth::user()->is_vip;
        
        $rules = [
            'name' => ['required', 'string'],
            'detail_type' => ['required', new Enum(DetailType::class)],
            'detail' => ['required', 'string'],
            'daily_limit' => ['required', 'numeric', 'min:0'],
            'max_pending_orders_quantity' => ['nullable', 'integer', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0'],
            'order_interval_minutes' => ['nullable', 'integer', 'min:0'],
            'initials' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'payment_gateway_ids' => ['required', 'array'],
            'payment_gateway_ids.*' => ['integer', 'exists:payment_gateways,id'],
        ];
        
        // Добавляем правила валидации для VIP пользователей
        if ($isVipUser) {
            $rules['unique_amount_percentage'] = ['nullable', 'numeric', 'min:0', 'max:10'];
            $rules['unique_amount_seconds'] = ['nullable', 'integer', 'min:0', 'max:3600'];
        }
        
        return $rules;
    }

    public function attributes()
    {
        return [
            'name' => __('название'),
            'detail_type' => __('тип реквизита'),
            'detail' => __('реквизит'),
            'daily_limit' => __('дневной лимит'),
            'max_pending_orders_quantity' => __('макс. кол-во ожидающих заказов'),
            'min_order_amount' => __('мин. сумма заказа'),
            'max_order_amount' => __('макс. сумма заказа'),
            'order_interval_minutes' => __('интервал между заказами'),
            'unique_amount_percentage' => __('процент отклонения для уникальности суммы'),
            'unique_amount_seconds' => __('интервал проверки уникальности суммы'),
            'initials' => __('инициалы'),
            'is_active' => __('активен'),
            'payment_gateway_ids' => __('платежные шлюзы'),
            'payment_gateway_ids.*' => __('платежный шлюз'),
        ];
    }
}
