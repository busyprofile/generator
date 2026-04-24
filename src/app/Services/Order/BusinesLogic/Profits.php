<?php

namespace App\Services\Order\BusinesLogic;

use App\Services\Money\Money;

class Profits
{
    public static function calculate(Money $amount, Money $exchangeRate, float $totalCommissionRate, float $traderCommissionRate, ?float $teamLeaderCommissionRate = null)
    {
        // amount = 1000р
        // exchangeRate = 100р (за 1$)
        // totalCommissionRate = 10%
        // $traderCommissionRate = 7%
        // $teamLeaderCommissionRate = 1% (может быть null или 0)

        if ($totalCommissionRate < $traderCommissionRate) {
            throw new \Exception("The total commission cannot be less than the trader's commission.");
        }

        // Обработка null значения для комиссии тимлидера
        $teamLeaderCommissionRate = $teamLeaderCommissionRate ?? 0;

        // Рассчитываем комиссию сервиса (учитывая трейдера, без тимлидера)
        $serviceCommissionRate = $totalCommissionRate - $traderCommissionRate; // 10% - 7% = 3%

        // Проверяем, что комиссия тимлидера не превышает комиссию сервиса
        if ($teamLeaderCommissionRate > $serviceCommissionRate) {
            throw new \Exception("The team leader commission cannot be greater than the service commission.");
        }

        // Финальная комиссия сервиса после вычета комиссии тимлидера
        $serviceCommissionRate = $serviceCommissionRate - $teamLeaderCommissionRate; // 3% - 1% = 2%

        // Конвертируем сумму по обменному курсу
        $totalProfit = $amount->div($exchangeRate); // 1000р / 100р = 10$

        // Рассчитываем общую сумму комиссии
        $totalCommissionAmount = $totalProfit->mul($totalCommissionRate / 100); // 10$ * 10% = 1$

        // Вычисляем чистую прибыль мерчанта
        $merchantProfit = $totalProfit->sub($totalCommissionAmount); // 10$ - 1$

        // Рассчитываем прибыль тимлидера
        $teamLeaderProfit = $teamLeaderCommissionRate > 0
            ? $totalCommissionAmount->mul($teamLeaderCommissionRate / $totalCommissionRate)
            : Money::zero($totalProfit->getCurrency());

        // Разделяем комиссии между сервисом и трейдером
        $serviceProfit = $totalCommissionAmount->mul($serviceCommissionRate / $totalCommissionRate);

        // Вычисляем прибыль трейдера как разницу
        $traderProfit = $totalCommissionAmount->sub($serviceProfit)->sub($teamLeaderProfit);

        // Альтернативный подход: проверка и корректировка
        // $calculatedTotal = $merchantProfit->add($serviceProfit)->add($traderProfit)->add($teamLeaderProfit);
        // if (!$calculatedTotal->equals($totalProfit)) {
        //     $diff = $totalProfit->sub($calculatedTotal);
        //     $traderProfit = $traderProfit->add($diff);
        // }

        return (object) [
            'totalProfit' => $totalProfit,
            'merchantProfit' => $merchantProfit,
            'serviceProfit' => $serviceProfit,
            'traderProfit' => $traderProfit,
            'teamLeaderProfit' => $teamLeaderProfit,
        ];
    }

    /**
     * Рассчитывает прибыль для дополнительного тимлидера.
     *
     * @param Money $baseAmount Базовая сумма заказа, от которой считается процент.
     * @param float $commissionRate Стандартная процентная ставка дополнительного тимлидера.
     * @return Money Рассчитанная сумма прибыли.
     */
    public function calculateAdditionalTeamLeaderProfit(Money $baseAmount, float $commissionRate): Money
    {
        // Проверяем, что ставка не отрицательная
        if ($commissionRate <= 0) {
            return Money::zero($baseAmount->getCurrency());
        }

        // Рассчитываем прибыль: базовая_сумма * (ставка / 100)
        // Используем метод multiply для Money, передавая ставку как строку для точности
        $profit = $baseAmount->mul((string)($commissionRate / 100));

        return $profit;
    }
}
