<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\SmsLog;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use App\Services\Sms\CurrencyConverterService;
use App\Services\Sms\Parser;
use Illuminate\Console\Command;

class DiagnoseSmsOrderMatchingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:diagnose-sms-matching {--user-id=} {--hours=24}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Диагностирует соответствие SMS-уведомлений и ордеров';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $hours = (int) $this->option('hours');

        $this->info("🔍 Диагностика SMS-уведомлений за последние {$hours} часов");
        
        if ($userId) {
            $this->info("👤 Фильтр по пользователю: {$userId}");
        }

        // Получаем SMS логи без привязанных ордеров
        $unprocessedSms = SmsLog::whereNull('order_id')
            ->where('created_at', '>=', now()->subHours($hours))
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['user', 'device'])
            ->get();

        $this->info("📱 Найдено необработанных SMS: " . $unprocessedSms->count());

        if ($unprocessedSms->isEmpty()) {
            $this->info("✅ Все SMS обработаны успешно!");
            return 0;
        }

        $parser = new Parser();
        $converter = new CurrencyConverterService();

        foreach ($unprocessedSms as $smsLog) {
            $this->newLine();
            $this->info("📩 SMS ID: {$smsLog->id} | Пользователь: {$smsLog->user->name}");
            $this->line("📞 Отправитель: {$smsLog->sender}");
            $this->line("💬 Сообщение: {$smsLog->message}");

            // Пытаемся распарсить SMS
            $result = $parser->parse($smsLog->sender, $smsLog->message);
            
            if (!$result) {
                $this->error("❌ SMS не удалось распарсить");
                continue;
            }

            $this->info("✅ Распарсен: {$result->amount->toBeauty()} {$result->amount->getCurrency()->getCode()}");
            $this->info("🏦 Платежный шлюз: {$result->paymentGateway->name}");

            // Ищем соответствующие pending ордера
            $pendingOrders = Order::where('trader_id', $smsLog->user->id)
                ->where('status', OrderStatus::PENDING)
                ->where('payment_gateway_id', $result->paymentGateway->id)
                ->whereRelation('paymentDetail', 'user_device_id', $smsLog->user_device_id)
                ->get();

            // Для диагностики - покажем все pending заказы этого пользователя
            $allUserPendingOrders = Order::where('trader_id', $smsLog->user->id)
                ->where('status', OrderStatus::PENDING)
                ->with(['paymentGateway', 'paymentDetail.userDevice'])
                ->get();

            $this->info("🔍 Все pending заказы пользователя: " . $allUserPendingOrders->count());
            foreach ($allUserPendingOrders as $userOrder) {
                $this->line("   📋 Заказ #{$userOrder->id}: {$userOrder->amount->toBeauty()} {$userOrder->currency->getCode()}");
                $this->line("      🏦 Шлюз: {$userOrder->paymentGateway->name} (ID: {$userOrder->payment_gateway_id})");
                $this->line("      📱 Устройство: {$userOrder->paymentDetail->userDevice->name} (ID: {$userOrder->paymentDetail->user_device_id})");
                
                // Проверяем условия
                $gatewayMatch = $userOrder->payment_gateway_id === $result->paymentGateway->id;
                $deviceMatch = $userOrder->paymentDetail->user_device_id === $smsLog->user_device_id;
                
                $this->line("      ✓ Шлюз совпадает: " . ($gatewayMatch ? "ДА" : "НЕТ (нужен {$result->paymentGateway->id})"));
                $this->line("      ✓ Устройство совпадает: " . ($deviceMatch ? "ДА" : "НЕТ (нужен {$smsLog->user_device_id})"));
            }

            if ($pendingOrders->isEmpty()) {
                $this->warn("⚠️  Не найдено pending ордеров для этого пользователя и шлюза");
                $this->line("📋 SMS: user_id={$smsLog->user->id}, device_id={$smsLog->user_device_id}, gateway_id={$result->paymentGateway->id}");
                continue;
            }

            $this->info("🔍 Найдено pending ордеров: " . $pendingOrders->count());

            $matchFound = false;
            foreach ($pendingOrders as $order) {
                $this->line("   🎯 Ордер #{$order->id}: {$order->amount->toBeauty()} {$order->currency->getCode()}");

                // Проверяем точное совпадение
                if ($order->amount->equals($result->amount) && 
                    $order->currency->equals($result->amount->getCurrency())) {
                    $this->info("   ✅ Точное совпадение!");
                    $matchFound = true;
                    continue;
                }

                // Проверяем совпадение с конвертацией
                try {
                    if ($converter->isWithinTolerance($order->amount, $result->amount, 1.2)) {
                        $this->info("   ✅ Совпадение с конвертацией (±1.2%)!");
                        
                        // Показываем детали конвертации
                        $convertedAmount = $converter->convert($order->amount, $result->amount->getCurrency());
                        $this->line("   📊 Конвертация: {$order->amount->toBeauty()} → {$convertedAmount->toBeauty()}");
                        
                        $matchFound = true;
                    } else {
                        $this->line("   ❌ Не попадает в допуск ±1.2%");
                        
                        // Показываем, насколько не попадает
                        $convertedAmount = $converter->convert($order->amount, $result->amount->getCurrency());
                        $difference = abs((float) $convertedAmount->toPrecision() - (float) $result->amount->toPrecision());
                        $percentDiff = ($difference / (float) $result->amount->toPrecision()) * 100;
                        $this->line("   📊 Разница: {$difference} (" . number_format($percentDiff, 2) . "%)");
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Ошибка конвертации: " . $e->getMessage());
                }
            }

            if (!$matchFound) {
                $this->warn("❌ Подходящий ордер не найден");
                
                // Предлагаем возможные причины
                $this->line("🤔 Возможные причины:");
                $this->line("   • Ордер уже обработан");
                $this->line("   • Сумма не совпадает с допуском");
                $this->line("   • Неправильный платежный шлюз");
                $this->line("   • Ордер создан на другом устройстве");
            }
        }

        // Статистика
        $this->newLine();
        $this->info("📊 Статистика:");
        
        $totalSms = SmsLog::where('created_at', '>=', now()->subHours($hours))
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();
            
        $processedSms = SmsLog::whereNotNull('order_id')
            ->where('created_at', '>=', now()->subHours($hours))
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        $successRate = $totalSms > 0 ? ($processedSms / $totalSms) * 100 : 0;

        $this->line("📱 Всего SMS: {$totalSms}");
        $this->line("✅ Обработано: {$processedSms}");
        $this->line("❌ Необработано: " . ($totalSms - $processedSms));
        $this->line("📈 Успешность: " . number_format($successRate, 1) . "%");

        return 0;
    }
} 