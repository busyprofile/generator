<?php

namespace App\Http\Resources;

use App\Models\PaymentGateway;
use App\Models\SmsLog;
use App\Services\Sms\Parser;
use App\Services\Sms\CurrencyConverterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * @var SmsLog $this
         * @var PaymentGateway $paymentGateway
         */

        $paymentGateway = PaymentGateway::find((new Parser())->getGatewayBySender($this->sender)?->id);
        $parser = new Parser();
        $parsingResult = $parser->parseRaw($this->message);
        
        // Получаем полную информацию о парсинге включая валюту SMS
        $fullParsingResult = $parser->parse($this->sender, $this->message);
        
        $conversionInfo = null;
        
        // Если есть результат парсинга и платежный шлюз
        if ($fullParsingResult && $paymentGateway) {
            $smsAmount = (float)$fullParsingResult->amount->toPrecision();
            $smsCurrency = $fullParsingResult->amount->getCurrency()->getCode();
            $gatewayCurrency = $paymentGateway->currency->getCode();
            
            // Если валюты разные, показываем информацию о конверсии
            if ($smsCurrency !== $gatewayCurrency) {
                try {
                    $converter = new CurrencyConverterService();
                    
                    $conversionInfo = [
                        'sms_amount' => $smsAmount,
                        'sms_currency' => $smsCurrency,
                        'gateway_currency' => $gatewayCurrency,
                        'conversion_examples' => [
                            '100_to_sms_currency' => round((float)$converter->convert(
                                \App\Services\Money\Money::fromPrecision('100', $gatewayCurrency), 
                                \App\Services\Money\Currency::make($smsCurrency)
                            )->toPrecision(), 2),
                            '1000_to_sms_currency' => round((float)$converter->convert(
                                \App\Services\Money\Money::fromPrecision('1000', $gatewayCurrency), 
                                \App\Services\Money\Currency::make($smsCurrency)
                            )->toPrecision(), 2),
                        ]
                    ];
                    
                    // Если есть заказ, показываем конверсию для него
                    if ($this->order) {
                        $orderAmount = $this->order->amount;
                        
                        // Используем зафиксированный курс из заказа если есть
                        if ($this->order->conversion_price) {
                            $orderConversionPrice = $this->order->conversion_price;
                            $smsTargetRate = services()->market()->getBuyPrice(\App\Services\Money\Currency::make($smsCurrency));
                            
                            $fixedRates = [
                                $gatewayCurrency => (float) $orderConversionPrice->toPrecision(),
                                $smsCurrency => (float) $smsTargetRate->toPrecision(),
                            ];
                            
                            $convertedOrderMoney = $converter->convertWithFixedRate($orderAmount, \App\Services\Money\Currency::make($smsCurrency), $fixedRates);
                        } else {
                            // Если нет зафиксированного курса, используем текущий
                            $convertedOrderMoney = $converter->convert($orderAmount, \App\Services\Money\Currency::make($smsCurrency));
                        }
                        
                        $convertedOrderAmount = (float)$convertedOrderMoney->toPrecision();
                        
                        $difference = abs($convertedOrderAmount - $smsAmount);
                        $percentDiff = ($difference / $smsAmount) * 100;
                        
                        $conversionInfo['order_conversion'] = [
                            'order_amount' => $orderAmount->toPrecision(),
                            'order_currency' => $gatewayCurrency,
                            'converted_amount' => round($convertedOrderAmount, 2),
                            'difference' => round($difference, 2),
                            'percent_difference' => round($percentDiff, 3),
                            'within_tolerance' => $percentDiff <= 1.2,
                            'tolerance' => 1.2,
                            'using_fixed_rate' => isset($this->order->conversion_price),
                            'fixed_rate' => isset($this->order->conversion_price) ? $this->order->conversion_price->toPrecision() : null,
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $conversionInfo = [
                        'error' => 'Ошибка получения курса: ' . $e->getMessage()
                    ];
                }
            }
        }

        return [
            'id' => $this->id,
            'device' => $this->whenLoaded('device', function() {
                return [
                    'id' => $this->device->id,
                    'name' => $this->device->name,
                    'android_id' => $this->device->android_id,
                ];
            }),
            'order' => $this->whenLoaded('order', function() {
                return [
                    'id' => $this->order->id,
                    'uuid' => $this->order->uuid,
                    'amount' => $this->order->amount,
                ];
            }),
            'sender' => $this->sender,
            'message' => $this->message,
            'sender_exists' => (bool)$paymentGateway,
            'payment_gateway' => $this->when(!empty($paymentGateway), function () use ($paymentGateway) {
                return [
                    'name' => $paymentGateway->name,
                    'currency' => $paymentGateway->currency->getCode(),
                    'logo_path' => $paymentGateway?->logo ? asset('storage/logos/'.$paymentGateway->logo) : null,
                ];
            }),
            'parsing_result' => $parsingResult,
            'full_parsing_result' => $fullParsingResult ? [
                'amount' => $fullParsingResult->amount->toBeauty(),
                'currency' => $fullParsingResult->amount->getCurrency()->getCode(),
                'card_last_digits' => $fullParsingResult->card_last_digits,
            ] : null,
            'conversion_info' => $conversionInfo,
            'timestamp' => Carbon::createFromTimestamp($this->timestamp)->toDateTimeString(),
            'type' => $this->type->value,
            'created_at' => $this->created_at->toDateTimeString(),
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
