<?php

namespace App\Http\Controllers;

use App\Enums\BalanceType;
use App\Enums\InvoiceType;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\TransactionResource;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Money\Currency;
use App\Services\Money\Money;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;


class WalletController extends Controller
{
    public function index(Request $request)
    {
        if ($request->route()->action['as'] === 'wallet.index') {
            $balanceType = BalanceType::TRUST;
        } else if ($request->route()->action['as'] === 'merchant.finances.index') {
            $balanceType = BalanceType::MERCHANT;
        } else if ($request->route()->action['as'] === 'leader.finances.index') {
            $balanceType = BalanceType::TEAMLEADER;
        }

        /**
         * @var Wallet $wallet
         */
        $wallet = $request->user()->wallet;

        $tabs = [
            'invoices' => [
                'key' => 'invoices',
                'name' => 'Инвойсы',
            ],
            'transactions' => [
                'key' => 'transactions',
                'name' => 'Транзакции',
            ]
        ];

        $filters = [
            'invoices' => [
                'invoiceTypes' => [
                    'all' => [
                        'key' => 'all',
                        'name' => 'Тип инвойса',
                    ],
                    InvoiceType::DEPOSIT->value => [
                        'key' => InvoiceType::DEPOSIT->value,
                        'name' => 'Пополнение',
                    ],
                    InvoiceType::WITHDRAWAL->value => [
                        'key' => InvoiceType::WITHDRAWAL->value,
                        'name' => 'Вывод',
                    ],
                ],
            ],
        ];

        $currentTab = request()->input('tab', 'invoices');
        if (empty($tabs[$currentTab])) {
            $currentTab = 'invoices';
        }

        $currentFilters = [
            'invoices' => [
                'invoiceTypes' => request()->input('currentFilters.invoices.invoiceTypes', 'all'),
            ],
        ];

        $walletStats = services()->wallet()->getWalletStats($wallet)->toArray();
        $depositLink = services()->settings()->getDepositLink();

        $invoices = null;
        $transactions = null;

        if ($currentTab === 'invoices') {
            $invoices = queries()->invoice()->paginate(
                wallet: $wallet,
                invoiceType: InvoiceType::tryFrom($currentFilters['invoices']['invoiceTypes']),
                balanceType: $balanceType,
            );
            $invoices = InvoiceResource::collection($invoices);
        } else if ($currentTab === 'transactions') {
            $transactions = queries()->transaction()->paginate(
                wallet: $wallet,
                balanceType: $balanceType,
            );
            $transactions = TransactionResource::collection($transactions);
        }
        $platformWallet = services()->settings()->getPlatformWallet();



        return Inertia::render('Wallet/Index', compact('walletStats', 'invoices', 'transactions', 'tabs', 'filters', 'currentTab', 'currentFilters', 'depositLink', 'platformWallet'));
    }

    public function getUniqAmount(Request $request)
    {
        try {
            $data = $request->validate(['amount' => 'required|numeric|min:1']);
            
            $amount = Money::fromPrecision((string)$data['amount'], Currency::USDT());
            
            $lastMinutes = now()->subMinutes(40); // Последние 40 минут

            $existingAmounts = Invoice::query()
                ->where('type', InvoiceType::DEPOSIT)
                ->where('status', InvoiceStatus::PENDING)
                ->where('balance_type', BalanceType::TRUST)
                ->where('currency', Currency::USDT())
                ->where('created_at', '>=', $lastMinutes)
                ->get()
                ->map(fn($invoice) => $invoice->amount->toUnits()) // Преобразуем Money в число
                ->toArray();

     
            
            $minimalIncrement = Money::fromPrecision('0.01', Currency::USDT()); // Минимальный инкремент

            while (in_array($amount->toUnits(), $existingAmounts)) {
                $amount = $amount->add($minimalIncrement);
            }

            // Возвращаем JSON ответ с уникальным amount
            return response()->json([
                'success' => true,
                'uniq_amount' => $amount->toPrecision() // Отправляем точное значение
            ]);

        } catch (\Throwable $e) {
       
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

        public function qrcode(Request $request)
    {
        try {
            if (!class_exists(Builder::class)) {
                throw new \Exception("Библиотека Endroid QR-Code недоступна.");
            }
            $data = $request->validate(['amount' => 'required|numeric|min:1']);

            $amount = Money::fromPrecision((string)$data['amount'], Currency::USDT());
        
            $balanceType = $request->route()->action['as'] === 'merchant.finances.index'
                ? BalanceType::MERCHANT
                : BalanceType::TRUST;
        
            $wallet = $request->user()->wallet;

            //Запрос в базу с проверкой сумм
            //$uniq_amount = $this->getUniqAmount($amount);
            
            $invoice = Invoice::create([
                'amount' => $amount->toUnits(),
                'currency' => Currency::USDT(),
                'type' => InvoiceType::DEPOSIT,
                'balance_type' => $balanceType,
                'status' => InvoiceStatus::PENDING,
                'wallet_id' => $wallet->id,
            ]);

            // Данные для QR-кода
            $address = services()->settings()->getPlatformWallet();
            // $data_qr = "tron:$address?amount=$amount&contract=TXLAQ63Xg1NAzckPwKHvzw7CSEmLMEqcdj";
            $data_qr = $address;
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($data_qr)
                ->size(300)
                ->margin(10)
                ->build();

            $imageString = $result->getString();

            return response($imageString)
                ->header('Content-Type', 'image/png');

        } catch (\Throwable $e) {
          
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}


