<?php

namespace App\Http\Requests\PayoutOffer;

use App\Enums\DetailType;
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
        return [
            'min_amount' => ['required', 'integer', 'min:1', 'lt:max_amount'],
            'max_amount' => ['required', 'integer', 'min:1', 'gt:min_amount'],
            'detail_types' => ['required', 'array'],
            'detail_types.*' => ['required', Rule::enum(DetailType::class)],
            'active' => ['required', 'boolean'],
        ];
    }
}
