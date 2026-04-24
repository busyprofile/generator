<?php

namespace App\Http\Requests\Dispute;

use Illuminate\Foundation\Http\FormRequest;

class CancelRequest extends FormRequest
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
            'cancel_reason' => ['required', 'string', 'in:requires_bank_statement,requires_video_proof,wrong_payment_refund_required,incorrect_amount_received'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
