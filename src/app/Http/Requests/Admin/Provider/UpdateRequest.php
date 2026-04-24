<?php

namespace App\Http\Requests\Admin\Provider;

use App\Enums\ProviderIntegrationEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $integrationValues = collect(ProviderIntegrationEnum::cases())->map->value->toArray();

        return [
            'name' => ['required', 'string', 'max:255'],
            'integration' => ['required', 'string', Rule::in($integrationValues)],
            'trader_id' => ['nullable', 'integer', 'exists:users,id'],
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
            'name' => 'название',
            'integration' => 'интеграция',
            'trader_id' => 'ID трейдера',
            'is_active' => 'активен',
        ];
    }
}
