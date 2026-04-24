<?php

namespace App\Contracts;

interface ServiceBuilderContract
{
    public function order(): OrderServiceContract;

    public function sms(): SmsServiceContract;

    public function callback(): CallbackServiceContract;

    public function market(): MarketServiceContract;

    public function dispute(): DisputeServiceContract;

    public function wallet(): WalletServiceContract;

    public function invoice(): InvoiceServiceContract;

    public function settings(): SettingsServiceContract;

    public function telegramBot(): TelegramBotServiceContract;

    public function payout(): PayoutServiceContract;

    public function fundsHolder(): FundsHolderServiceContract;

    public function loginHistory(): LoginHistoryServiceContract;

    public function merchantApiLog(): MerchantApiLogServiceContract;

    public function orderPooling(): OrderPoolingServiceContract;

    public function device(): DeviceServiceContract;

    public function merchantApiStatistics(): MerchantApiStatisticsServiceContract;
}
