<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\BalanceType;
use App\Enums\TransactionType;
use App\Services\Money\Money;
use App\Services\Money\Currency;
use Carbon\Carbon;
use Log;

class CheckDepositsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-deposits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверяет и обновляет статус ожидающих пополнений';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = '151ffb46-55c6-486e-9f0b-c2f671e87688'; // Укажи здесь свой API ключ
        $address = services()->settings()->getPlatformWallet();

        $lastMinutes = now()->subMinutes(40);

        // Забираем все ожидающие транзакции из БД
        $pendingInvoices = Invoice::query()
            ->where('type', InvoiceType::DEPOSIT)
            ->where('status', InvoiceStatus::PENDING)
            ->where('balance_type', BalanceType::TRUST)
            ->where('currency', Currency::USDT())
            ->where('created_at', '>=', $lastMinutes)
            ->get();

        if ($pendingInvoices->isEmpty()) {
            $this->info('Нет ожидающих пополнений.');
            return 0;
        }

        // log::info($pendingInvoices);

        $baseUrl = "https://api.trongrid.io/v1/accounts/$address/transactions/trc20";
        $queryParams = [
            'only_confirmed' => 'true',
            'limit' => 20,
            'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            'only_to' => 'true',
        ];

        $url = $baseUrl . '?' . http_build_query($queryParams);

        $response = Http::withHeaders([
            'TRON-PRO-API-KEY' => $apiKey,
            'Accept' => 'application/json',
        ])->get($url);

        if (!$response->successful()) {
            Log::error('Ошибка при запросе транзакций: ' . $response->body());
            $this->error('Ошибка при запросе транзакций.');
            return 1;
        }

        $transactions = $response->json('data') ?? [];
        // log::info($transactions);

        foreach ($transactions as $transaction) {
            $amount = (int) $transaction['value'];
            $transactionId = $transaction['transaction_id'];
            $decimals = $transaction['token_info']['decimals'] - 2; // Уменьшаем decimals на 2
        
            // Преобразуем значение в целое число с уменьшенными decimals
            $actualAmount = $amount / (10 ** $decimals); // Здесь будет учтено уменьшение decimals

            // Преобразование timestamp в Carbon объект
            $transactionTimestamp = Carbon::createFromTimestampMs($transaction['block_timestamp']);

            // Log::info($actualAmount);
            foreach ($pendingInvoices as $invoice) {
                // Преобразуем значение $invoice->amount из Money в целое число
                $invoiceAmount = (int) $invoice->amount->toUnits(); 
                // Log::info($transactionTimestamp->greaterThanOrEqualTo($invoice->created_at) ? 'true' : 'false');
                // Проверяем соответствие
                if ($invoiceAmount === (int) $actualAmount && $transactionTimestamp->greaterThanOrEqualTo($invoice->created_at)) {
                    // Проверка на уникальность transaction_id перед обновлением
                    $duplicateInvoice = Invoice::where('transaction_id', $transactionId)->first();

                    if ($duplicateInvoice) {
                        Log::warning("Дублирующийся Transaction ID: {$transactionId} уже используется для Invoice ID: {$duplicateInvoice->id}");
                        continue;
                    }

                    // Обновляем статус и записываем transaction_id
                    $invoice->update([
                        'status' => InvoiceStatus::SUCCESS,
                        'transaction_id' => $transactionId,
                    ]);

                    services()->wallet()->giveToBalance(
                        walletID: $invoice->wallet->id,
                        amount: $invoice->amount,
                        transactionType: TransactionType::DEPOSIT_BY_USER,
                        balanceType: $invoice->balance_type
                    );
                    
                    Log::info("Транзакция подтверждена. Invoice ID: {$invoice->id}, Transaction ID: {$transactionId}, Сумма: {$actualAmount}");
                    $this->info("Транзакция подтверждена для Invoice ID: {$invoice->id}");
                }
            }
        }
        
        //Log::info("Проверка завершена.");

        $this->info('Проверка завершена.');
        return 0;
    }
}