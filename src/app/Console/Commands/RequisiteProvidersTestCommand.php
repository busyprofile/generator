<?php

namespace App\Console\Commands;

use App\Enums\DetailType;
use App\Enums\MarketEnum;
use App\Models\Merchant;
use App\Models\PaymentGateway;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Illuminate\Console\Command;

class RequisiteProvidersTestCommand extends Command
{
    protected $signature = 'requisite-providers:test 
                           {--amount=10000 : Сумма для тестирования}
                           {--currency=RUB : Валюта для тестирования}
                           {--merchant-id=1 : ID мерчанта}
                           {--detail-type= : Тип реквизита (card, phone, account_number)}
                           {--gateway-id= : ID платежного шлюза}
                           {--provider= : Тестировать конкретного провайдера}
                           {--stats : Показать статистику провайдеров}';

    protected $description = 'Тестирование провайдеров реквизитов';

    public function handle()
    {
        if ($this->option('stats')) {
            $this->showProvidersStats();
            return;
        }

        $this->info('🔍 Тестирование провайдеров реквизитов...');
        $this->newLine();

        $amount = Money::fromPrecision($this->option('amount'), Currency::make($this->option('currency')));
        $merchant = Merchant::findOrFail($this->option('merchant-id'));
        $detailType = $this->option('detail-type') ? DetailType::from($this->option('detail-type')) : null;
        $gateway = $this->option('gateway-id') ? PaymentGateway::findOrFail($this->option('gateway-id')) : null;
        $providerName = $this->option('provider');

        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Мерчант', "{$merchant->name} (ID: {$merchant->id})"],
                ['Сумма', $amount->toBeauty() . ' ' . $amount->getCurrency()->getCode()],
                ['Тип реквизита', $detailType?->value ?? 'Любой'],
                ['Платежный шлюз', $gateway ? "{$gateway->name} (ID: {$gateway->id})" : 'Любой'],
                ['Конкретный провайдер', $providerName ?? 'Все по порядку'],
            ]
        );

        $this->newLine();

        $chain = services()->requisiteProviderChain();

        if ($providerName) {
            $this->testSpecificProvider($chain, $providerName, $merchant, $amount, $detailType, $gateway);
        } else {
            $this->testAllProviders($chain, $merchant, $amount, $detailType, $gateway);
        }
    }

    protected function testAllProviders($chain, $merchant, $amount, $detailType, $gateway)
    {
        $this->info('📋 Тестирование всех провайдеров по цепочке:');
        $this->newLine();

        $result = $chain->getRequisites(
            merchant: $merchant,
            market: MarketEnum::BYBIT,
            amount: $amount,
            detailType: $detailType,
            currency: $amount->getCurrency(),
            gateway: $gateway
        );

        if ($result) {
            $this->info('✅ Реквизиты найдены!');
            $this->showResult($result);
        } else {
            $this->error('❌ Реквизиты не найдены ни в одном провайдере');
        }

        $this->newLine();
        $this->showProvidersStats();
    }

    protected function testSpecificProvider($chain, $providerName, $merchant, $amount, $detailType, $gateway)
    {
        $provider = $chain->getProviders()->first(fn($p) => $p->getName() === $providerName);

        if (!$provider) {
            $this->error("❌ Провайдер '{$providerName}' не найден");
            $this->newLine();
            $this->info('Доступные провайдеры:');
            foreach ($chain->getProviders() as $p) {
                $this->line("  - {$p->getName()}");
            }
            return;
        }

        $this->info("🔍 Тестирование провайдера: {$providerName}");
        $this->newLine();

        if (!$provider->isAvailable()) {
            $this->error('❌ Провайдер отключен');
            return;
        }

        if (!$provider->supports($amount, $amount->getCurrency(), $gateway, $detailType)) {
            $this->error('❌ Провайдер не поддерживает указанные параметры');
            return;
        }

        $result = $provider->getRequisites(
            merchant: $merchant,
            market: MarketEnum::BYBIT,
            amount: $amount,
            detailType: $detailType,
            currency: $amount->getCurrency(),
            gateway: $gateway
        );

        if ($result) {
            $this->info('✅ Реквизиты найдены!');
            $this->showResult($result);
        } else {
            $this->error('❌ Провайдер не вернул реквизиты');
        }
    }

    protected function showResult($result)
    {
        $this->table(
            ['Поле', 'Значение'],
            [
                ['ID реквизита', $result->id],
                ['Трейдер ID', $result->userID],
                ['Шлюз', $result->gateway->name ?? $result->paymentGatewayID],
                ['Сумма', $result->amount->toBeauty() . ' ' . $result->currency->getCode()],
                ['Общая прибыль', $result->totalProfit->toBeauty() . ' USDT'],
                ['Прибыль трейдера', $result->traderProfit->toBeauty() . ' USDT'],
                ['Прибыль мерчанта', $result->merchantProfit->toBeauty() . ' USDT'],
                ['Прибыль сервиса', $result->serviceProfit->toBeauty() . ' USDT'],
            ]
        );
    }

    protected function showProvidersStats()
    {
        $this->info('📊 Статистика провайдеров:');
        $this->newLine();

        $chain = services()->requisiteProviderChain();
        $stats = $chain->getProvidersStats();

        $tableData = [];
        foreach ($stats as $stat) {
            $tableData[] = [
                $stat['name'],
                $stat['priority'],
                $stat['available'] ? '✅ Да' : '❌ Нет',
                json_encode($stat['config'], JSON_UNESCAPED_UNICODE),
            ];
        }

        $this->table(
            ['Провайдер', 'Приоритет', 'Доступен', 'Конфигурация'],
            $tableData
        );
    }
} 