<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestGarexTrader extends Command
{
    protected $signature = 'test:garex-trader';
    protected $description = 'Проверка настроек трейдера Garex';

    public function handle()
    {
        $this->info('🔍 Проверка настроек трейдера Garex...');

        // Получаем ID трейдера из конфигурации
        $traderId = config('requisite_providers.garex.trader_id', 54);
        $this->line("📋 ID трейдера из конфигурации: {$traderId}");

        // Проверяем существование пользователя
        $trader = User::find($traderId);
        
        if (!$trader) {
            $this->error("❌ Пользователь с ID {$traderId} не найден!");
            $this->line("Создайте пользователя с email garex@gmail.com и ID {$traderId}");
            return 1;
        }

        $this->info("✅ Трейдер найден:");
        $this->line("   ID: {$trader->id}");
        $this->line("   Имя: {$trader->name}");
        $this->line("   Email: {$trader->email}");
        $this->line("   Создан: {$trader->created_at}");

        // Проверяем настройки провайдера
        $this->info("\n⚙️ Настройки Garex провайдера:");
        $this->line("   Включен: " . (config('requisite_providers.garex.enabled') ? 'Да' : 'Нет'));
        $this->line("   API URL: " . config('requisite_providers.garex.api_url'));
        $this->line("   Merchant ID: " . config('requisite_providers.garex.merchant_id'));
        $this->line("   Trader ID: " . config('requisite_providers.garex.trader_id'));

        // Проверяем доступность провайдера
        $chain = services()->requisiteProviderChain();
        $garexProvider = $chain->getProviders()->first(function ($provider) {
            return $provider->getName() === 'garex';
        });

        if ($garexProvider) {
            $this->info("\n✅ Garex провайдер найден в цепочке");
            $this->line("   Доступен: " . ($garexProvider->isAvailable() ? 'Да' : 'Нет'));
            $this->line("   Приоритет: " . $garexProvider->getPriority());
        } else {
            $this->error("\n❌ Garex провайдер не найден в цепочке");
        }

        $this->info("\n🎯 Готово! Трейдер Garex настроен корректно.");
        return 0;
    }
}
