<?php

namespace App\Console\Commands;

use App\Services\Sms\Parser;
use Illuminate\Console\Command;

class TestSmsParsingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-sms-parsing {sender} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует парсинг SMS сообщения';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sender = $this->argument('sender');
        $message = $this->argument('message');

        $this->info("📱 Тестирование парсинга SMS");
        $this->line("📞 Отправитель: {$sender}");
        $this->line("💬 Сообщение: {$message}");
        $this->newLine();

        $parser = new Parser();
        
        // Сначала проверяем, найдется ли платежный шлюз
        $gateway = $parser->getGatewayBySender($sender);
        
        if (!$gateway) {
            $this->error("❌ Платежный шлюз не найден для отправителя: {$sender}");
            $this->warn("💡 Убедитесь, что отправитель добавлен в sms_senders платежного шлюза");
            return 1;
        }
        
        $this->info("🏦 Найден платежный шлюз: {$gateway->name}");
        $this->line("💰 Валюта шлюза: {$gateway->currency->getCode()}");
        
        // Проверяем raw парсинг
        $rawResult = $parser->parseRaw($message);
        
        if (!$rawResult) {
            $this->error("❌ Не удалось извлечь сумму из сообщения");
            return 1;
        }
        
        $this->info("✅ Raw парсинг успешен:");
        $this->line("  💵 Сумма: {$rawResult['amount']}");
        $this->line("  💳 Карта: " . ($rawResult['card'] ?? 'не найдена'));
        
        // Полный парсинг
        $result = $parser->parse($sender, $message);
        
        if (!$result) {
            $this->error("❌ Полный парсинг не удался");
            return 1;
        }
        
        $this->newLine();
        $this->info("🎉 ПОЛНЫЙ ПАРСИНГ УСПЕШЕН!");
        $this->line("💰 Сумма: {$result->amount->toBeauty()} {$result->amount->getCurrency()->getCode()}");
        $this->line("🏦 Платежный шлюз: {$result->paymentGateway->name}");
        $this->line("🏦 Валюта шлюза: {$result->paymentGateway->currency->getCode()}");
        $this->line("📱 Валюта SMS: {$result->amount->getCurrency()->getCode()}");
        $this->line("💳 Последние 4 цифры карты: " . ($result->card_last_digits ?? 'не найдены'));
        
        // Показываем как определилась валюта
        if ($result->amount->getCurrency()->getCode() === $gateway->currency->getCode()) {
            $this->info("✅ Валюта определена корректно из шлюза");
        } else {
            $this->warn("⚠️  Валюта определена из SMS, а не из шлюза");
        }
        
        return 0;
    }
} 