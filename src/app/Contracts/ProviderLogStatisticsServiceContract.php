<?php

namespace App\Contracts;

interface ProviderLogStatisticsServiceContract
{
    /**
     * Получает статистику за сегодня и за все время
     */
    public function getStatistics(): array;
}
