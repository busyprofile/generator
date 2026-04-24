<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class LoadPaymentMethodsListCommand extends Command
{
    protected $signature = 'app:load-payment-methods-list';

    protected $description = 'Load ByBit payment methods list into cache';

    public function handle(): int
    {
        try {
            services()->market()->loadPaymentMethodsList();
            $this->info('Payment methods list loaded successfully.');
            return 0;
        } catch (Throwable $e) {
            $this->error('Failed to load payment methods list: ' . $e->getMessage());
            return 1;
        }
    }
}
