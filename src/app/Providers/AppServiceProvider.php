<?php

namespace App\Providers;

use App\Contracts\DeviceServiceContract;
use App\Contracts\DisputeServiceContract;
use App\Contracts\FundsHolderServiceContract;
use App\Contracts\InvoiceServiceContract;
use App\Contracts\LoginHistoryServiceContract;
use App\Contracts\MarketServiceContract;
use App\Contracts\CallbackServiceContract;
use App\Contracts\MerchantApiLogServiceContract;
use App\Contracts\RequisiteProviderCallbackLogServiceContract;
use App\Contracts\MerchantApiStatisticsServiceContract;
use App\Contracts\OrderPoolingServiceContract;
use App\Contracts\OrderServiceContract;
use App\Contracts\PayoutServiceContract;
use App\Contracts\QueriesBuilderContract;
use App\Contracts\ServiceBuilderContract;
use App\Contracts\SettingsServiceContract;
use App\Contracts\SmsServiceContract;
use App\Contracts\TelegramBotServiceContract;
use App\Contracts\WalletServiceContract;
use App\Mixins\ResponseMixins;
use App\Models\Dispute;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\PaymentDetail;
use App\Models\Payout;
use App\Models\PayoutGateway;
use App\Models\PayoutOffer;
use App\Models\User;
use App\Queries\Cache\MerchantQueriesCache;
use App\Queries\Eloquent\DisputeQueriesEloquent;
use App\Queries\Eloquent\InvoiceQueriesEloquent;
use App\Queries\Eloquent\MerchantQueriesEloquent;
use App\Queries\Eloquent\OrderQueriesEloquent;
use App\Queries\Eloquent\PaymentDetailQueriesEloquent;
use App\Queries\Eloquent\PaymentGatewayQueriesEloquent;
use App\Queries\Eloquent\TransactionQueriesEloquent;
use App\Queries\Eloquent\MerchantApiLogQueriesEloquent;
use App\Queries\Eloquent\CallbackLogQueriesEloquent;
use App\Queries\Interfaces\DisputeQueries;
use App\Queries\Interfaces\InvoiceQueries;
use App\Queries\Interfaces\MerchantQueries;
use App\Queries\Interfaces\OrderQueries;
use App\Queries\Interfaces\PaymentDetailQueries;
use App\Queries\Interfaces\PaymentGatewayQueries;
use App\Queries\Interfaces\TransactionQueries;
use App\Queries\Interfaces\MerchantApiLogQueries;
use App\Queries\Interfaces\CallbackLogQueries;
use App\Queries\Interfaces\ProviderLogQueries;
use App\Queries\Interfaces\ProviderCallbackLogQueries;
use App\Queries\Eloquent\ProviderLogQueriesEloquent;
use App\Queries\Eloquent\ProviderCallbackLogQueriesEloquent;
use App\Contracts\ProviderLogStatisticsServiceContract;
use App\Services\Statistics\ProviderLogStatisticsService;
use App\Queries\QueriesBuilder;
use App\Services\Auth\LoginHistoryService;
use App\Services\Device\DeviceService;
use App\Services\Dispute\DisputeService;
use App\Services\Invoice\InvoiceService;
use App\Services\Market\MarketService;
use App\Services\MoneyHolder\FundsHolderService;
use App\Services\Order\OrderService;
use App\Services\OrderCallback\CallbackService;
use App\Services\OrderPooling\OrderPoolingService;
use App\Services\Payout\PayoutService;
use App\Services\ServiceBuilder;
use App\Services\Settings\SettingsService;
use App\Services\Sms\SmsService;
use App\Services\Statistics\MerchantApiStatisticsService;
use App\Services\TelegramBot\TelegramBotService;
use App\Services\Wallet\WalletService;
use App\Services\Logging\MerchantApiLogService;
use App\Services\Logging\RequisiteProviderCallbackLogService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //services
        $this->app->singleton(ServiceBuilderContract::class, function () {
            return new ServiceBuilder();
        });
        $this->app->bind(OrderServiceContract::class, function ($app) {
            return new OrderService($app->make(WalletServiceContract::class));
        });
        $this->app->bind(SmsServiceContract::class, function () {
            return new SmsService();
        });
        $this->app->bind(CallbackServiceContract::class, function () {
            return new CallbackService();
        });
        $this->app->singleton(MarketServiceContract::class, function () {
            return new MarketService();
        });
        $this->app->singleton(DisputeServiceContract::class, function () {
            return new DisputeService();
        });
        $this->app->singleton(WalletServiceContract::class, function () {
            return new WalletService();
        });
        $this->app->singleton(InvoiceServiceContract::class, function () {
            return new InvoiceService();
        });
        $this->app->singleton(SettingsServiceContract::class, function () {
            return new SettingsService();
        });
        $this->app->singleton(TelegramBotServiceContract::class, function () {
            return new TelegramBotService(
                config('telegram.bots.mybot.webhook_token')
            );
        });
        $this->app->singleton(PayoutServiceContract::class, function () {
            return new PayoutService();
        });
        $this->app->singleton(FundsHolderServiceContract::class, function () {
            return new FundsHolderService();
        });
        $this->app->bind(LoginHistoryServiceContract::class, function () {
            return new LoginHistoryService();
        });
        $this->app->singleton(MerchantApiLogServiceContract::class, function () {
            return new MerchantApiLogService();
        });
        $this->app->singleton(RequisiteProviderCallbackLogServiceContract::class, function () {
            return new RequisiteProviderCallbackLogService();
        });
        $this->app->singleton(OrderPoolingServiceContract::class, function () {
            return new OrderPoolingService();
        });
        $this->app->singleton(DeviceServiceContract::class, function () {
            return new DeviceService();
        });
        $this->app->singleton(MerchantApiStatisticsServiceContract::class, function () {
            return new MerchantApiStatisticsService();
        });
        $this->app->singleton(ProviderLogStatisticsServiceContract::class, function () {
            return new ProviderLogStatisticsService();
        });

        // Регистрация цепочки провайдеров реквизитов
        // Internal провайдер добавляется сразу, внешние загружаются через ProviderSelector "на лету"
        $this->app->singleton(\App\Services\RequisiteProviders\RequisiteProviderChain::class, function ($app) {
            $chain = new \App\Services\RequisiteProviders\RequisiteProviderChain();
            $chain->addProvider(new \App\Services\RequisiteProviders\InternalRequisiteProvider());
            return $chain;
        });

        // Регистрация LoginLogger
        $this->app->singleton('login-logger', function () {
            return new \App\Support\LoginLogger();
        });

        //queries
        $this->app->singleton(QueriesBuilderContract::class, function () {
            return new QueriesBuilder();
        });
        $this->app->bind(OrderQueries::class, function () {
            return new OrderQueriesEloquent();
        });
        $this->app->bind(PaymentGatewayQueries::class, function () {
            return new PaymentGatewayQueriesEloquent();
        });
        $this->app->bind(PaymentDetailQueries::class, function () {
            return new PaymentDetailQueriesEloquent();
        });
        $this->app->bind(DisputeQueries::class, function () {
            return new DisputeQueriesEloquent();
        });
        $this->app->bind(MerchantQueries::class, function () {
            return new MerchantQueriesCache(
                eloquentQueries: new MerchantQueriesEloquent(),
                cacheTtl: 60
            );
        });
        $this->app->bind(InvoiceQueries::class, function () {
            return new InvoiceQueriesEloquent();
        });
        $this->app->bind(TransactionQueries::class, function () {
            return new TransactionQueriesEloquent();
        });
        $this->app->bind(MerchantApiLogQueries::class, function () {
            return new MerchantApiLogQueriesEloquent();
        });
        $this->app->bind(CallbackLogQueries::class, function () {
            return new CallbackLogQueriesEloquent();
        });
        $this->app->bind(ProviderLogQueries::class, function () {
            return new ProviderLogQueriesEloquent();
        });
        $this->app->bind(ProviderCallbackLogQueries::class, function () {
            return new ProviderCallbackLogQueriesEloquent();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::failing(function (JobFailed $event) {
            if ($event->job->getQueue() === 'conversion-prices-parser') {
                // Удаляем задачу, чтобы она не сохранялась в failed_jobs
                $event->job->delete();
            }
        });

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('Super Admin');
        });

        Response::mixin(new ResponseMixins());

        Gate::define('access-to-payment-detail', function (User $user, PaymentDetail $paymentDetail) {
            return $user->id === $paymentDetail->user_id || $user->hasRole('Super Admin');
        });
        Gate::define('access-to-order', function (User $user, Order $order) {
            return $user->id === $order->paymentDetail?->user_id || $user->id === $order->merchant?->user_id || $user->hasRole('Super Admin');
        });
        Gate::define('access-to-order-for-merchant-support', function (User $user, Order $order) {
            // Сначала проверяем, является ли пользователь Super Admin
            if ($user->hasRole('Super Admin')) {
                return true;
            }
            
            // Проверяем, что у заказа есть связанный мерчант
            // и ID пользователя совпадает с user_id мерчанта заказа
            return $user->id === $order->merchant?->user_id;
        });
        Gate::define('access-to-merchant', function (User $user, Merchant $merchant) {
            return $user->id === $merchant->user_id || $user->hasRole('Super Admin');
        });
        Gate::define('access-to-dispute', function (User $user, Dispute $dispute) {
            return $user->id === optional($dispute->order->paymentDetail)->user_id || $user->hasRole('Super Admin');
        });
        Gate::define('access-to-dispute-receipt', function (User $user, Dispute $dispute) {
            return $user->id === optional($dispute->order->paymentDetail)->user_id || $user->hasRole('Super Admin') || $user->hasRole('Support');
        });
        Gate::define('access-to-self', function (User $user) {
            return $user->id === auth()->id() || $user->hasRole('Super Admin');
        });
        Gate::define('access-to-payout', function (User $user, Payout $payout) {
            return $user->id === $payout->trader_id || $user->id === $payout->owner_id || $user->hasRole('Super Admin');
        });
        Gate::define('access-to-payout-offer', function (User $user, PayoutOffer $payoutOffer) {
            return $user->id === $payoutOffer->owner_id || $user->hasRole('Super Admin');
        });
        Gate::define('access-to-payout-gateway', function (User $user, PayoutGateway $payoutGateway) {
            return $user->id === $payoutGateway->owner_id || $user->hasRole('Super Admin');
        });
        //api
        Gate::define('api-access-to-merchant', function (User $user, Merchant $merchant) {
            return $user->id === $merchant->user_id;
        });

        //Socialite
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('telegram', \SocialiteProviders\Telegram\Provider::class);
        });

        Route::bind('order', function($id, \Illuminate\Routing\Route $route) {
            if ($route->bindingFieldFor('order') === 'uuid') {
                return Order::withoutGlobalScopes()->where('uuid', $id)->firstOrFail();
            }

            return Order::findOrFail($id);
        });
    }
}
