<?php

namespace App\Services\Sms;

use App\Enums\MarketEnum;
use App\Services\Money\Currency;
use App\Services\Money\Money;

class CurrencyConverterService
{
    /**
     * Конвертирует сумму из одной валюты в другую через USDT
     */
    public function convert(Money $amount, Currency $targetCurrency): Money
    {
        // Если валюты одинаковые, возвращаем исходную сумму
        if ($amount->getCurrency()->getCode() === $targetCurrency->getCode()) {
            return $amount;
        }

        $sourceCurrency = $amount->getCurrency();
        
        // Шаг 1: Конвертируем исходную валюту в USDT
        $amountInUsdt = $this->convertToUsdt($amount, $sourceCurrency);
        
        // Шаг 2: Конвертируем USDT в целевую валюту
        $convertedAmount = $this->convertFromUsdt($amountInUsdt, $targetCurrency);
        
        return $convertedAmount;
    }

    /**
     * Конвертирует сумму используя зафиксированный курс из заказа
     */
    public function convertWithFixedRate(Money $amount, Currency $targetCurrency, ?array $fixedRates = null): Money
    {
        // Если валюты одинаковые, возвращаем исходную сумму
        if ($amount->getCurrency()->getCode() === $targetCurrency->getCode()) {
            return $amount;
        }

        if ($fixedRates) {
            return $this->convertWithCustomRates($amount, $targetCurrency, $fixedRates);
        }

        // Если курсы не переданы, используем текущие из API
        return $this->convert($amount, $targetCurrency);
    }

    /**
     * Проверяет, находится ли сумма в пределах допустимого отклонения
     */
    public function isWithinTolerance(Money $expected, Money $actual, float $tolerancePercent = 1.2): bool
    {
        // Конвертируем expected (заказ) в валюту actual (SMS) для сравнения
        if ($expected->getCurrency()->getCode() !== $actual->getCurrency()->getCode()) {
            $expectedConverted = $this->convert($expected, $actual->getCurrency());
        } else {
            $expectedConverted = $expected;
        }

        $expectedValue = (float) $expectedConverted->toPrecision();
        $actualValue = (float) $actual->toPrecision();

        // Вычисляем допустимое отклонение от ожидаемой суммы
        $tolerance = $expectedValue * ($tolerancePercent / 100);
        $lowerBound = $expectedValue - $tolerance;
        $upperBound = $expectedValue + $tolerance;

        return $actualValue >= $lowerBound && $actualValue <= $upperBound;
    }

    /**
     * Проверяет толерантность используя зафиксированный курс
     */
    public function isWithinToleranceWithFixedRate(Money $expected, Money $actual, float $tolerancePercent = 1.2, ?array $fixedRates = null): bool
    {
        // Конвертируем expected (заказ) в валюту actual (SMS) для сравнения
        if ($expected->getCurrency()->getCode() !== $actual->getCurrency()->getCode()) {
            $expectedConverted = $this->convertWithFixedRate($expected, $actual->getCurrency(), $fixedRates);
        } else {
            $expectedConverted = $expected;
        }

        $expectedValue = (float) $expectedConverted->toPrecision();
        $actualValue = (float) $actual->toPrecision();

        // Вычисляем допустимое отклонение от ожидаемой суммы
        $tolerance = $expectedValue * ($tolerancePercent / 100);
        $lowerBound = $expectedValue - $tolerance;
        $upperBound = $expectedValue + $tolerance;

        return $actualValue >= $lowerBound && $actualValue <= $upperBound;
    }

    /**
     * Конвертирует сумму из любой валюты в USDT
     */
    private function convertToUsdt(Money $amount, Currency $sourceCurrency): Money
    {
        if ($sourceCurrency->equals(Currency::USDT())) {
            return $amount;
        }

        // Получаем курс продажи (цену покупки USDT за фиатную валюту)
        $exchangeRate = services()->market()->getBuyPrice($sourceCurrency);
        
        if ((float) $exchangeRate->toPrecision() <= 0) {
            throw new \Exception("Не удалось получить курс обмена для валюты: " . $sourceCurrency->getCode());
        }

        // Делим сумму на курс для получения USDT
        $usdtAmount = $amount->div($exchangeRate);
        
        return $usdtAmount;
    }

    /**
     * Конвертирует USDT в любую валюту
     */
    private function convertFromUsdt(Money $usdtAmount, Currency $targetCurrency): Money
    {
        if ($targetCurrency->equals(Currency::USDT())) {
            return $usdtAmount;
        }

        // Получаем курс покупки (цену продажи USDT за фиатную валюту)
        $exchangeRate = services()->market()->getBuyPrice($targetCurrency);
        
        if ((float) $exchangeRate->toPrecision() <= 0) {
            throw new \Exception("Не удалось получить курс обмена для валюты: " . $targetCurrency->getCode());
        }

        // Умножаем USDT на курс для получения фиатной валюты
        $convertedAmount = $usdtAmount->mul($exchangeRate);
        
        return new Money($convertedAmount->toUnits(), $targetCurrency);
    }

    /**
     * Конвертирует используя переданные курсы
     */
    private function convertWithCustomRates(Money $amount, Currency $targetCurrency, array $fixedRates): Money
    {
        $sourceCurrency = $amount->getCurrency()->getCode();
        $targetCurrencyCode = $targetCurrency->getCode();

        if (!isset($fixedRates[$sourceCurrency]) || !isset($fixedRates[$targetCurrencyCode])) {
            throw new \Exception("Не найден фиксированный курс для конвертации {$sourceCurrency} -> {$targetCurrencyCode}");
        }

        // Конвертируем через USDT используя фиксированные курсы
        $sourceRateToUsdt = Money::fromPrecision($fixedRates[$sourceCurrency], Currency::USDT());
        $targetRateFromUsdt = Money::fromPrecision($fixedRates[$targetCurrencyCode], Currency::USDT());

        // Шаг 1: конвертируем в USDT
        $usdtAmount = $amount->div($sourceRateToUsdt);
        
        // Шаг 2: конвертируем из USDT в целевую валюту
        $convertedAmount = $usdtAmount->mul($targetRateFromUsdt);
        
        return new Money($convertedAmount->toUnits(), $targetCurrency);
    }
} 