<?php

namespace App\Http\Requests\Admin\ProviderTerminal;

use App\Enums\DetailType;
use App\Enums\ProviderIntegrationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $detailTypes = collect(DetailType::cases())->map->value->toArray();
        $integrationValues = collect(ProviderIntegrationEnum::cases())->map->value->toArray();

        return [
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'integration' => ['nullable', 'string', Rule::in($integrationValues)],
            'name' => ['required', 'string', 'max:255'],
            'min_sum' => ['nullable', 'numeric', 'min:0'],
            'max_sum' => ['nullable', 'numeric', 'min:0'],
            'time_for_order' => ['nullable', 'integer', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'max_response_time_ms' => ['nullable', 'integer', 'min:0'],
            'number_of_retries' => ['nullable', 'integer', 'min:0'],
            'retry_delay_ms' => ['nullable', 'integer', 'min:0'],
            'enabled_detail_types' => ['nullable', 'array'],
            'enabled_detail_types.*' => ['string', Rule::in($detailTypes)],
            'integration_fields' => ['nullable', 'array'],
            'integration_fields.*.key' => ['nullable', 'string', 'max:255'],
            'integration_fields.*.value' => ['nullable', 'string', 'max:1024'],
            'integration_settings' => ['nullable', 'array'],
            'integration_settings.*' => ['nullable', 'string', 'max:2048'],
            'additional_settings' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'provider_id' => 'провайдер',
            'integration' => 'интеграция',
            'name' => 'название',
            'min_sum' => 'минимальная сумма',
            'max_sum' => 'максимальная сумма',
            'time_for_order' => 'время на сделку',
            'rate' => 'ставка',
            'max_response_time_ms' => 'максимальное время ответа',
            'number_of_retries' => 'количество повторов',
            'retry_delay_ms' => 'задержка между повторами',
            'enabled_detail_types' => 'типы реквизитов',
            'integration_fields' => 'поля интеграции',
            'additional_settings' => 'дополнительные настройки',
            'is_active' => 'активен',
        ];
    }
}
