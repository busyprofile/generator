<?php

namespace App\Console\Commands;

use App\Enums\DetailType;
use App\Models\PaymentGateway;
use Illuminate\Console\Command;

class UpdateAlfabankForGarexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-alfabank-for-garex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет PaymentGateway Альфа-банка для поддержки phone (для работы с Garex)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Обновление PaymentGateway Альфа-банка для поддержки Garex...');

        $alfabank = PaymentGateway::where('code', 'alfabank_rub')->first();

        if (!$alfabank) {
            $this->error('PaymentGateway Альфа-банка не найден!');
            return 1;
        }

        $this->info("Найден PaymentGateway: {$alfabank->name} (ID: {$alfabank->id})");
        $this->info("Текущие detail_types: " . json_encode($alfabank->detail_types));

        // Добавляем поддержку phone, если её нет
        $detailTypes = $alfabank->detail_types;
        if (!in_array(DetailType::PHONE, $detailTypes)) {
            $detailTypes[] = DetailType::PHONE;
            
            $alfabank->update([
                'detail_types' => $detailTypes
            ]);

            $this->info('✅ Добавлена поддержка phone для Альфа-банка');
            $this->info("Новые detail_types: " . json_encode($alfabank->fresh()->detail_types));
        } else {
            $this->info('ℹ️ Альфа-банк уже поддерживает phone');
        }

        $this->info('✅ Обновление завершено успешно!');
        return 0;
    }
}
