<?php

namespace App\Http\Requests\PaymentDetail;

use App\Enums\DetailType;
use App\Models\PaymentGateway;
use App\Rules\UniquePaymentDetail;
use App\Rules\UniquePhonePaymentDetail;
use App\Services\Money\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LVR\CreditCard\CardNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

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
        $isVipUser = Auth::check() && Auth::user()->is_vip;
        
        if (DetailType::PHONE->equals($this->detail_type)) {
            $detail = [
                'required',
                'phone:RU,KZ,UZ,KG,TJ,AZ',
                new UniquePhonePaymentDetail($this->payment_gateway_ids),
                // Дополнительная логика: определяем страну по префиксу
                function ($attribute, $value, $fail) {
                    // Удаляем пробелы/дефисы, чтобы не мешали при проверке
                    $normalized = preg_replace('/\\s+|-/u', '', $value);

                    // Пытаемся определить страну по префиксу
                    $country = $this->guessCountryByPrefix($normalized);

                    if (!$country) {
                        $fail('Не удалось определить страну по номеру телефона.');
                        return;
                    }
                },
            ];
        } else if (DetailType::CARD->equals($this->detail_type)) {
            $detail = [
                'required',
                new CardNumber(),
                new UniquePaymentDetail()
            ];
        } else if (DetailType::ACCOUNT_NUMBER->equals($this->detail_type)) {
            $detail = [
                'required',
                'digits:20',
                new UniquePaymentDetail()
            ];
        } else if (DetailType::SIM->equals($this->detail_type)) {
            $detail = [
                'required',
                'phone:RU,KZ,UZ,KG,TJ,AZ',
                new UniquePhonePaymentDetail($this->payment_gateway_ids),
                // Дополнительная логика: определяем страну по префиксу
                function ($attribute, $value, $fail) {
                    // Удаляем пробелы/дефисы, чтобы не мешали при проверке
                    $normalized = preg_replace('/\\s+|-/u', '', $value);

                    // Пытаемся определить страну по префиксу
                    $country = $this->guessCountryByPrefix($normalized);

                    if (!$country) {
                        $fail('Не удалось определить страну по номеру телефона.');
                        return;
                    }
                },
            ];
        } else {
            $detail = [
                'required',
                'digits:16',
                new UniquePaymentDetail()
            ];
        }

        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:30'],
            'detail' => $detail,
            'detail_type' => ['required', new Enum(DetailType::class)],
            'initials' => ['required', 'string', 'min:3', 'max:40'],
            'is_active' => ['required', 'boolean'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:100000000'],
            'currency' => ['required', 'string', Rule::in(Currency::getAllCodes())],
            'payment_gateway_ids' => ['required', 'array', 'min:1'],
            'payment_gateway_ids.*' => [
                'required',
                'exists:payment_gateways,id',
                function ($attribute, $value, $fail) {
                    $gateway = PaymentGateway::find($value);
                    if ($gateway && $gateway->currency->getCode() !== $this->currency) {
                        $fail('Валюта платежного метода не соответствует выбранной валюте.');
                    }
                }
            ],
            'max_pending_orders_quantity' => ['required', 'integer', 'min:1', 'max:100000000'],
            'order_interval_minutes' => ['nullable', 'integer', 'min:1'],
            'user_device_id' => ['required', 'exists:user_devices,id'],
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
            'detail' => __('реквизит'),
            'initials' => __('инициалы'),
            'is_active' => __('активность'),
            'daily_limit' => __('дневной лимит'),
            'order_interval_minutes' => __('интервал между сделками'),
            'payment_gateway_ids' => __('платежные методы'),
            'payment_gateway_ids.*' => __('платежный метод'),
            'unique_amount_percentage' => __('процент отклонения для уникальности суммы'),
            'unique_amount_seconds' => __('интервал проверки уникальности суммы'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'detail' => preg_replace('~\D+~','', $this->detail),
            'currency' => strtolower($this->currency),
        ]);

        $paymentGatewayIdsInput = $this->input('payment_gateway_ids');

        // Если это скалярное значение (не null и не массив), оборачиваем его в массив.
        if (!is_null($paymentGatewayIdsInput) && !is_array($paymentGatewayIdsInput)) {
            $this->merge([
                'payment_gateway_ids' => [$paymentGatewayIdsInput],
            ]);
        }
        // Если $paymentGatewayIdsInput изначально был null, он останется null. Правило 'required' его обработает.
        // Если $paymentGatewayIdsInput изначально был массивом, он останется массивом.
    }

    private function guessCountryByPrefix(string $number): ?string
    {
        if (Str::startsWith($number, '77')) {
            return 'KZ'; // Казахстан
        } elseif (Str::startsWith($number, '7')) {
            return 'RU'; // Россия
        } elseif (Str::startsWith($number, '998')) {
            return 'UZ'; // Узбекистан
        } elseif (Str::startsWith($number, '996')) {
            return 'KG'; // Киргизия
        } elseif (Str::startsWith($number, '992')) {
            return 'TJ'; // Таджикистан
        } elseif (Str::startsWith($number, '994')) {
            return 'AZ'; // Азербайджан
        }

        // Если префикс не подходит ни под одну известную страну
        return null;
    }
}
