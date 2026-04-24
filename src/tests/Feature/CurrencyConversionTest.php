<?php

namespace Tests\Feature;

use App\DTO\SMS\SmsDTO;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Sms\CurrencyConverterService;
use App\Services\Sms\Parser;
use App\Services\Sms\SmsService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyConversionTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyConverterService $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new CurrencyConverterService();
    }

    /**
     * Тест конвертации RUB в TJS
     */
    public function test_convert_rub_to_tjs()
    {
        // Мокаем рыночные курсы
        $this->mockMarketService();

        $rubAmount = Money::fromPrecision(1000, Currency::RUB());
        $tjsAmount = $this->converter->convert($rubAmount, Currency::TJS());

        $this->assertInstanceOf(Money::class, $tjsAmount);
        $this->assertTrue($tjsAmount->getCurrency()->equals(Currency::TJS()));
        $this->assertGreaterThan(0, (float) $tjsAmount->toPrecision());
    }

    /**
     * Тест проверки допуска ±0.9%
     */
    public function test_tolerance_check()
    {
        $this->mockMarketService();

        $originalAmount = Money::fromPrecision(1000, Currency::RUB());
        $convertedAmount = $this->converter->convert($originalAmount, Currency::TJS());
        $backConverted = $this->converter->convert($convertedAmount, Currency::RUB());

        $this->assertTrue(
            $this->converter->isWithinTolerance($originalAmount, $backConverted, 0.9),
            'Конвертация должна быть в пределах допуска ±0.9%'
        );
    }

    /**
     * Тест парсинга SMS с TJS
     */
    public function test_parse_sms_with_tjs()
    {
        $gateway = $this->createPaymentGateway();
        $parser = new Parser();

        $smsMessage = "Поступление 150.50 TJS на карту *1234 от Ivan Petrov";
        $result = $parser->parse($gateway->sms_senders[0], $smsMessage);

        $this->assertNotNull($result);
        $this->assertEquals(150.50, (float) $result->amount->toPrecision());
        $this->assertTrue($result->amount->getCurrency()->equals(Currency::TJS()));
    }

    /**
     * Тест автоматического подтверждения ордера с конвертацией валют
     */
    public function test_automatic_order_confirmation_with_currency_conversion()
    {
        $this->mockMarketService();

        $user = User::factory()->create();
        $device = UserDevice::factory()->create(['user_id' => $user->id]);
        $gateway = $this->createPaymentGateway();

        // Создаем ордер в RUB
        $order = Order::factory()->create([
            'trader_id' => $user->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => Money::fromPrecision(1000, Currency::RUB())->toUnits(),
            'currency' => Currency::RUB()->getCode(),
            'status' => OrderStatus::PENDING,
        ]);

        // Эмулируем SMS в TJS (примерно эквивалентную сумму)
        $smsAmount = Money::fromPrecision(150, Currency::TJS());
        
        // Проверяем, что ордер найден с учетом конвертации
        $foundOrder = queries()->order()->findPending(
            $smsAmount, 
            $user, 
            $gateway, 
            $device
        );

        $this->assertNotNull($foundOrder);
        $this->assertEquals($order->id, $foundOrder->id);
    }

    /**
     * Тест полного цикла обработки SMS
     */
    public function test_full_sms_processing_cycle()
    {
        $this->mockMarketService();

        $user = User::factory()->create();
        $device = UserDevice::factory()->create(['user_id' => $user->id]);
        $gateway = $this->createPaymentGateway();

        // Создаем ордер в RUB
        Order::factory()->create([
            'trader_id' => $user->id,
            'payment_gateway_id' => $gateway->id,
            'amount' => Money::fromPrecision(1000, Currency::RUB())->toUnits(),
            'currency' => Currency::RUB()->getCode(),
            'status' => OrderStatus::PENDING,
        ]);

        // Создаем SMS DTO с суммой в TJS
        $smsDto = new SmsDTO(
            deviceID: $device->id,
            sender: $gateway->sms_senders[0],
            message: "Поступление 150.00 TJS на карту *1234",
            timestamp: now()->timestamp * 1000,
            type: 'income'
        );

        // Обрабатываем SMS
        $smsService = new SmsService();
        $smsService->handleSms($smsDto);

        // Проверяем, что ордер был автоматически подтвержден
        $order = Order::where('trader_id', $user->id)->first();
        $this->assertTrue($order->status->equals(OrderStatus::SUCCESS));
    }

    private function createPaymentGateway(): PaymentGateway
    {
        return PaymentGateway::factory()->create([
            'name' => 'Test Gateway',
            'code' => 'test',
            'currency' => Currency::RUB()->getCode(),
            'sms_senders' => ['TEST-BANK', 'TEST-GATEWAY']
        ]);
    }

    private function mockMarketService()
    {
        // Мокаем курсы валют
        $this->mock('App\Services\Market\MarketServiceInterface', function ($mock) {
            $mock->shouldReceive('getBuyPrice')
                ->with(\Mockery::on(function ($currency) {
                    return $currency->equals(Currency::RUB());
                }))
                ->andReturn(Money::fromPrecision(95.50, Currency::RUB()));

            $mock->shouldReceive('getBuyPrice')
                ->with(\Mockery::on(function ($currency) {
                    return $currency->equals(Currency::TJS());
                }))
                ->andReturn(Money::fromPrecision(11.20, Currency::TJS()));
        });
    }
} 