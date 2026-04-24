<?php

namespace App\Services;

use App\Contracts\CallbackServiceContract;
use App\Contracts\DeviceServiceContract;
use App\Contracts\DisputeServiceContract;
use App\Contracts\FundsHolderServiceContract;
use App\Contracts\InvoiceServiceContract;
use App\Contracts\LoginHistoryServiceContract;
use App\Contracts\MarketServiceContract;
use App\Contracts\MerchantApiLogServiceContract;
use App\Contracts\MerchantApiStatisticsServiceContract;
use App\Contracts\OrderPoolingServiceContract;
use App\Contracts\OrderServiceContract;
use App\Contracts\PayoutServiceContract;
use App\Contracts\ServiceBuilderContract;
use App\Contracts\SettingsServiceContract;
use App\Contracts\SmsServiceContract;
use App\Contracts\TelegramBotServiceContract;
use App\Contracts\WalletServiceContract;

class ServiceBuilder implements ServiceBuilderContract
{
    public function order(): OrderServiceContract
    {
        return make(OrderServiceContract::class);
    }

    public function sms(): SmsServiceContract
    {
        return make(SmsServiceContract::class);
    }

    public function callback(): CallbackServiceContract
    {
        return make(CallbackServiceContract::class);
    }

    public function market(): MarketServiceContract
    {
        return make(MarketServiceContract::class);
    }

    public function dispute(): DisputeServiceContract
    {
        return make(DisputeServiceContract::class);
    }

    public function wallet(): WalletServiceContract
    {
        return make(WalletServiceContract::class);
    }

    public function invoice(): InvoiceServiceContract
    {
        return make(InvoiceServiceContract::class);
    }

    public function settings(): SettingsServiceContract
    {
        return make(SettingsServiceContract::class);
    }

    public function telegramBot(): TelegramBotServiceContract
    {
        return make(TelegramBotServiceContract::class);
    }

    public function payout(): PayoutServiceContract
    {
        return make(PayoutServiceContract::class);
    }

    public function fundsHolder(): FundsHolderServiceContract
    {
        return make(FundsHolderServiceContract::class);
    }

    public function loginHistory(): LoginHistoryServiceContract
    {
        return make(LoginHistoryServiceContract::class);
    }

    public function merchantApiLog(): MerchantApiLogServiceContract
    {
        return make(MerchantApiLogServiceContract::class);
    }

    public function orderPooling(): OrderPoolingServiceContract
    {
        return make(OrderPoolingServiceContract::class);
    }

    public function device(): DeviceServiceContract
    {
        return make(DeviceServiceContract::class);
    }

    public function merchantApiStatistics(): MerchantApiStatisticsServiceContract
    {
        return make(MerchantApiStatisticsServiceContract::class);
    }

    public function requisiteProviderChain(): \App\Services\RequisiteProviders\RequisiteProviderChain
    {
        return make(\App\Services\RequisiteProviders\RequisiteProviderChain::class);
    }
}
