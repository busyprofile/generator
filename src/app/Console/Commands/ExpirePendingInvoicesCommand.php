<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\BalanceType;
use Carbon\Carbon;
use Log;

class ExpirePendingInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-pending-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Переводит инвойсы со статусом PENDING в статус FAIL через 30 минут после создания';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $halfHourAgo = now()->subMinutes(30);

        // Выбираем инвойсы со статусом PENDING, созданные более 30 минут назад
        $expiredInvoices = Invoice::where('status', InvoiceStatus::PENDING)
            ->where('type', InvoiceType::DEPOSIT)
            ->where('balance_type', BalanceType::TRUST)
            ->where('created_at', '<', $halfHourAgo)
            ->get();

        if ($expiredInvoices->isEmpty()) {
            $this->info('Нет инвойсов для перевода в статус FAIL.');
            return 0;
        }

        foreach ($expiredInvoices as $invoice) {
            $invoice->update(['status' => InvoiceStatus::FAIL]);
            Log::info("Инвойс ID: {$invoice->id} переведён в статус FAIL.");
            $this->info("Инвойс ID: {$invoice->id} переведён в статус FAIL.");
        }

        $this->info('Проверка завершена.');
        return 0;
    }
}
