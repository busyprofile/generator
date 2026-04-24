<?php

namespace App\Http\Requests\Admin\PriceParser;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'amount' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'integer'],
            'ad_quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'payment_method' => 'платежный метод',
            'ad_quantity' => 'количество объявлений',
        ];
    }
}
