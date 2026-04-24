<?php

namespace App\Http\Requests\PayoutGateway;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => ['required', 'string', 'min:5', 'max:30'],
            'domain' => ['required', 'string', 'url:https', 'min:5', 'max:120'],
            'callback_url' => ['nullable', 'string', 'url:https', 'max:256'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
