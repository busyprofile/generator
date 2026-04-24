<?php

namespace App\Console\Commands;

use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Sms\CurrencyConverterService;
use Illuminate\Console\Command;

class TestCurrencyConversionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-currency-conversion {from_amount} {from_currency} {to_currency}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует конвертацию валют через USDT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fromAmount = $this->argument('from_amount');
        $fromCurrency = $this->argument('from_currency');
        $toCurrency = $this->argument('to_currency');

        try {
            $converter = new CurrencyConverterService();
            
            $sourceAmount = Money::fromPrecision($fromAmount, Currency::make($fromCurrency));
            $targetCurrency = Currency::make($toCurrency);
            
            $this->info("Конвертация: {$sourceAmount->toBeauty()} {$fromCurrency} → {$toCurrency}");
            
            $convertedAmount = $converter->convert($sourceAmount, $targetCurrency);
            
            $this->info("Результат: {$convertedAmount->toBeauty()} {$toCurrency}");
            
            // Тестируем обратную конвертацию для проверки
            $backConverted = $converter->convert($convertedAmount, $sourceAmount->getCurrency());
            $this->info("Обратная конвертация: {$backConverted->toBeauty()} {$fromCurrency}");
            
            // Проверяем допуск ±0.9%
            $tolerance = $converter->isWithinTolerance($sourceAmount, $backConverted, 0.9);
            $this->info("Попадает в допуск ±0.9%: " . ($tolerance ? 'ДА' : 'НЕТ'));
            
            // Показываем текущие курсы
            $this->info("\nТекущие курсы:");
            if (!$sourceAmount->getCurrency()->equals(Currency::USDT())) {
                $rate = services()->market()->getBuyPrice($sourceAmount->getCurrency());
                $this->info("1 USDT = {$rate->toBeauty()} {$fromCurrency}");
            }
            
            if (!$targetCurrency->equals(Currency::USDT())) {
                $rate = services()->market()->getBuyPrice($targetCurrency);
                $this->info("1 USDT = {$rate->toBeauty()} {$toCurrency}");
            }
            
        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 