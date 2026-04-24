<?php

namespace App\DTO\Order;

use App\DTO\BaseDTO;
use App\Enums\DetailType;
use App\Exceptions\OrderException;
use App\Models\Merchant;
use App\Models\PaymentGateway;
use App\Services\Money\Money;
use Illuminate\Support\Facades\Log;

readonly class CreateOrderDTO extends BaseDTO
{
    public function __construct(
        public Money       $amount,
        public Merchant    $merchant,
        public bool        $h2h = false,
        public bool        $manually = false,
        public ?string     $externalID = null,
        public ?string     $callbackURL = null,
        public ?string     $successURL = null,
        public ?string     $failURL = null,
        public ?PaymentGateway $paymentGateway = null,
        public ?DetailType $paymentDetailType = null,
        public ?bool       $transgran = null,
    )
    {}

    public static function makeFromRequest(array $data): static
    {
        Log::info('[CreateOrderDTO] makeFromRequest started.', ['data_keys' => array_keys($data)]);
        $transgran = isset($data['transgran']) ? filter_var($data['transgran'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
        Log::debug('[CreateOrderDTO] Transgran flag processed.', ['raw_transgran' => $data['transgran'] ?? 'not_set', 'parsed_transgran' => $transgran]);

        $paymentGatewayModel = null;

        if (! empty($data['payment_gateway'])) {
            $pgCode = $data['payment_gateway'];
            Log::info('[CreateOrderDTO] Attempting to get PaymentGateway by code.', ['code' => $pgCode, 'transgran_flag' => $transgran]);
            $paymentGatewayModel = queries()->paymentGateway()->getByCode($pgCode, $transgran);

            if (!$paymentGatewayModel) {
                Log::warning('[CreateOrderDTO] PaymentGateway not found or does not match transgran flag.', ['code' => $pgCode, 'transgran_flag' => $transgran]);
            } else {
                Log::info('[CreateOrderDTO] PaymentGateway found.', ['id' => $paymentGatewayModel->id, 'code' => $paymentGatewayModel->code, 'is_transgran' => $paymentGatewayModel->is_transgran]);
            }
            
            if ($paymentGatewayModel) {
                 $data['amount'] = Money::fromPrecision($data['amount'], $paymentGatewayModel->currency);
            } else if (isset($data['currency'])) { 
                 Log::warning('[CreateOrderDTO] PaymentGateway not found by code, but currency is set. Using currency for amount.', ['currency' => $data['currency']]);
                 $data['amount'] = Money::fromPrecision($data['amount'], $data['currency']);
            } else {
                 Log::error('[CreateOrderDTO] Cannot determine currency for amount. PaymentGateway not found and no currency provided.', ['code' => $pgCode]);
                 throw new OrderException('Невозможно определить валюту для суммы заказа.');
            }

        } else if (! empty($data['currency'])) {
            Log::info('[CreateOrderDTO] PaymentGateway code not provided, using currency for amount.', ['currency' => $data['currency']]);
            $data['amount'] = Money::fromPrecision($data['amount'], $data['currency']);
        } else {
             Log::error('[CreateOrderDTO] Neither payment_gateway code nor currency provided in request data.');
             throw new OrderException('Не указан ни код платежного шлюза, ни валюта.');
        }
        
        $data['payment_detail_type'] = ! empty($data['payment_detail_type']) ? DetailType::from($data['payment_detail_type']) : null;
        
        if (empty($data['merchant'])) {
            $merchantUUID = $data['merchant_id'] ?? 'not_set';
            Log::info('[CreateOrderDTO] Merchant object not in data, finding by UUID.', ['merchant_uuid' => $merchantUUID]);
            $merchantModel = Merchant::where('uuid', $merchantUUID)->first();
            if (! $merchantModel) {
                Log::error('[CreateOrderDTO] Merchant not found by UUID.', ['merchant_uuid' => $merchantUUID]);
                throw new OrderException('Мерчант не найден по UUID: ' . $merchantUUID);
            }
            $data['merchant'] = $merchantModel;
        }
        Log::info('[CreateOrderDTO] Merchant prepared.', ['merchant_id' => $data['merchant']->id]);

        $dto = new static(
            amount: $data['amount'],
            merchant: $data['merchant'],
            h2h: $data['h2h'] ?? false,
            manually: $data['manually'] ?? false,
            externalID: $data['external_id'] ?? null,
            callbackURL: $data['callback_url'] ?? null,
            successURL: $data['success_url'] ?? null,
            failURL: $data['fail_url'] ?? null,
            paymentGateway: $paymentGatewayModel,
            paymentDetailType: $data['payment_detail_type'] ?? null,
            transgran: $transgran,
        );
        Log::info('[CreateOrderDTO] DTO created successfully.', ['external_id' => $dto->externalID]);
        return $dto;
    }
}
