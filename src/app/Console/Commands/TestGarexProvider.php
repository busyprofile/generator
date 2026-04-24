<?php

namespace App\Console\Commands;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Console\Command;

class TestGarexProvider extends Command
{
    protected $signature = 'test:garex-provider 
                            {--amount=1000 : Сумма в копейках}
                            {--currency=RUB : Код валюты}
                            {--merchant-id=1 : ID мерчанта}
                            {--detail-type= : Тип реквизита (card, phone, account_number)}
                            {--gateway-id= : ID платежного шлюза}
                            {--transgran= : Трансграничный перевод (true/false)}';

    protected $description = 'Тестирование Garex провайдера реквизитов';

    public function handle()
    {
        $this->info('🧪 Тестирование Garex провайдера...');

        // Получаем параметры
        $amount = Money::fromUnitsInt($this->option('amount'), Currency::make($this->option('currency')));
        $merchant = Merchant::find($this->option('merchant-id'));
        $detailType = $this->option('detail-type') ? DetailType::from($this->option('detail-type')) : null;
        $gateway = $this->option('gateway-id') ? PaymentGateway::find($this->option('gateway-id')) : null;
        $transgran = $this->option('transgran') ? filter_var($this->option('transgran'), FILTER_VALIDATE_BOOLEAN) : null;

        if (!$merchant) {
            $this->error('❌ Мерчант не найден');
            return 1;
        }

        $this->info("📊 Параметры теста:");
        $this->line("   Сумма: {$amount->toBeauty()}");
        $this->line("   Мерчант: {$merchant->name} (ID: {$merchant->id})");
        $this->line("   Тип реквизита: " . ($detailType?->value ?? 'не указан'));
        $this->line("   Гейтвей: " . ($gateway?->name ?? 'не указан'));
        $this->line("   Трансграничный: " . ($transgran ? 'да' : 'нет'));

        // Получаем цепочку провайдеров
        $chain = services()->requisiteProviderChain();
        
        // Находим Garex провайдер
        $garexProvider = $chain->getProviders()->first(function ($provider) {
            return $provider->getName() === 'garex';
        });

        if (!$garexProvider) {
            $this->error('❌ Garex провайдер не найден в цепочке');
            return 1;
        }

        $this->info("🔍 Проверка поддержки параметров...");

        // Проверяем поддержку
        if (!$garexProvider->supports($amount, $amount->getCurrency(), $gateway, $detailType, $transgran)) {
            $this->error('❌ Garex провайдер не поддерживает указанные параметры');
            return 1;
        }

        $this->info('✅ Garex провайдер поддерживает параметры');

        // Проверяем доступность
        if (!$garexProvider->isAvailable()) {
            $this->error('❌ Garex провайдер недоступен');
            $this->line('Проверьте настройки в .env файле:');
            $this->line('   GAREX_ENABLED=true');
            $this->line('   GAREX_API_TOKEN=your_token');
            $this->line('   GAREX_MERCHANT_ID=your_merchant_id');
            return 1;
        }

        $this->info('✅ Garex провайдер доступен');

        // Пытаемся получить реквизиты
        $this->info("🚀 Запрос реквизитов...");
        
        try {
            $startTime = microtime(true);
            
            $detail = $garexProvider->getRequisites(
                merchant: $merchant,
                market: MarketEnum::BYBIT, // Используем BYBIT как пример
                amount: $amount,
                detailType: $detailType,
                currency: $amount->getCurrency(),
                gateway: $gateway,
                transgran: $transgran
            );

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            if ($detail) {
                $this->info("✅ Реквизиты получены успешно!");
                $this->line("   ID: {$detail->id}");
                $this->line("   Тип: {$detail->detailType->value}");
                $this->line("   Реквизит: {$detail->detail}");
                $this->line("   Сумма: {$detail->amount->toBeauty()}");
                $this->line("   Время ответа: {$responseTime}ms");
                
                if ($detail->gateway) {
                    $this->line("   Гейтвей: {$detail->gateway->code}");
                }
                
                if ($detail->trader) {
                    $this->line("   Трейдер: {$detail->trader->name}");
                }
            } else {
                $this->warn("⚠️ Реквизиты не получены (вернулся null)");
                $this->line("   Время ответа: {$responseTime}ms");
            }

        } catch (\Exception $e) {
            $this->error("❌ Ошибка при получении реквизитов: {$e->getMessage()}");
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }

        // Показываем статистику провайдеров
        $this->info("\n📈 Статистика провайдеров:");
        $stats = $chain->getProvidersStats();
        
        foreach ($stats as $provider) {
            $status = $provider['available'] ? '✅' : '❌';
            $this->line("   {$status} {$provider['name']} (приоритет: {$provider['priority']})");
        }

        return 0;
    }
}
