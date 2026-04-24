<?php

namespace App\Console\Commands;

use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Sms\CurrencyConverterService;
use Illuminate\Console\Command;

class TestToleranceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-tolerance {order_amount} {order_currency} {sms_amount} {sms_currency}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверяет, попадает ли SMS в допуск ордера';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderAmount = (float) $this->argument('order_amount');
        $orderCurrency = $this->argument('order_currency');
        $smsAmount = (float) $this->argument('sms_amount');
        $smsCurrency = $this->argument('sms_currency');

        try {
            $converter = new CurrencyConverterService();
            
            $orderMoney = Money::fromPrecision($orderAmount, Currency::make($orderCurrency));
            $smsMoney = Money::fromPrecision($smsAmount, Currency::make($smsCurrency));
            
            $this->info("🎯 Ордер: {$orderMoney->toBeauty()} {$orderCurrency}");
            $this->info("📱 SMS: {$smsMoney->toBeauty()} {$smsCurrency}");
            
            // Конвертируем ордер в валюту SMS для наглядности
            if ($orderCurrency !== $smsCurrency) {
                $convertedOrder = $converter->convert($orderMoney, Currency::make($smsCurrency));
                $this->info("🔄 Ордер в валюте SMS: {$convertedOrder->toBeauty()} {$smsCurrency}");
                
                $difference = abs((float) $convertedOrder->toPrecision() - (float) $smsMoney->toPrecision());
                $percentDiff = ($difference / (float) $smsMoney->toPrecision()) * 100;
                
                $this->info("📊 Разница: {$difference} {$smsCurrency} (" . number_format($percentDiff, 3) . "%)");
            }
            
            // Проверяем различные допуски
            $tolerances = [0.5, 0.9, 1.0, 1.5, 2.0];
            
            foreach ($tolerances as $tolerance) {
                $withinTolerance = $converter->isWithinTolerance($orderMoney, $smsMoney, $tolerance);
                $status = $withinTolerance ? '✅ ДА' : '❌ НЕТ';
                $this->line("Допуск ±{$tolerance}%: {$status}");
            }
            
            // Окончательный вердикт для ±1.2%
            $finalResult = $converter->isWithinTolerance($orderMoney, $smsMoney, 1.2);
            $this->newLine();
            if ($finalResult) {
                $this->info("\n✅ Ордер будет закрыт автоматически (допуск ±1.2%)");
            } else {
                $this->warn("\n⚠️  Ордер НЕ будет закрыт автоматически");
            }
            
        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 