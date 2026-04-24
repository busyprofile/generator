<?php

namespace App\Console\Commands;

use App\Enums\DetailType;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Money\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->confirm('Вы уверены что хотите запустить установку приложения?')) {
            return;
        }

        if (! $this->confirm('Вы точно уверены? Установка перезапишет все имеющиеся данные.')) {
            return;
        }

        if (! $this->confirm('Я предупреждал!')) {
            return;
        }

        services()->telegramBot()->setWebhook();

        Artisan::call('migrate:fresh');

        $user = User::create([
            'id' => 1,
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'apk_access_token' => strtolower(Str::random(32)),
            'api_access_token' => strtolower(Str::random(32)),
            'remember_token' => Str::random(10),
        ]);

        services()->wallet()->create($user);

        //create roles and permissions
        $role_admin = Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Trader']);
        Role::create(['name' => 'Merchant']);

        $permission = Permission::create(['name' => 'access admin panel']);
        $role_admin->givePermissionTo($permission);

        //assign roles
        $user->assignRole($role_admin);

        $payment_gateways = [
            ['id' => 1, 'name' => 'Сбербанк', 'code' => 'sberbank_rub', 'currency' => Currency::RUB(), 'detail_types' => [
                DetailType::CARD, DetailType::ACCOUNT_NUMBER,
            ], 'sms_senders' => ['900']],
            ['id' => 2, 'name' => 'Альфа-Банк', 'code' => 'alfabank_rub', 'currency' => Currency::RUB(), 'detail_types' => [
                DetailType::CARD, DetailType::ACCOUNT_NUMBER,
            ]],
            ['id' => 3, 'name' => 'Райффайзенбанк', 'code' => 'raiffeisen_rub', 'currency' => Currency::RUB(), 'detail_types' => [DetailType::CARD]],
            ['id' => 4, 'name' => 'СБП', 'code' => 'sbp_rub', 'currency' => Currency::RUB(), 'detail_types' => [DetailType::PHONE], 'sub_payment_gateways' => [1, 2, 3]],
            ['id' => 5, 'name' => 'HUMO', 'code' => 'humo_uzs', 'currency' => Currency::UZS(), 'detail_types' => [DetailType::CARD]],
            ['id' => 6, 'name' => 'UZCARD', 'code' => 'uzcard_uzs', 'currency' => Currency::UZS(), 'detail_types' => [DetailType::CARD]],
            ['id' => 7, 'name' => 'Halyk', 'code' => 'halyk_kzt', 'currency' => Currency::KZT(), 'detail_types' => [DetailType::CARD]],
            ['id' => 8, 'name' => 'Jusan', 'code' => 'jusan_kzt', 'currency' => Currency::KZT(), 'detail_types' => [DetailType::CARD]],
            ['id' => 9, 'name' => 'Eurasian', 'code' => 'eurasian_kzt', 'currency' => Currency::KZT(), 'detail_types' => [DetailType::CARD]],
            ['id' => 10, 'name' => 'ОТП', 'code' => 'otp_rub', 'currency' => Currency::RUB(), 'detail_types' => [DetailType::CARD]],
            ['id' => 11, 'name' => 'ПСБ', 'code' => 'psb_rub', 'currency' => Currency::RUB(), 'detail_types' => [DetailType::CARD]],
            ['id' => 12, 'name' => 'МТС Банк', 'code' => 'mts_rub', 'currency' => Currency::RUB(), 'detail_types' => [DetailType::CARD]],
            ['id' => 13, 'name' => 'ДОМ.РФ', 'code' => 'domrf_rub', 'currency' => Currency::RUB(), 'detail_types' => [DetailType::CARD]],
            ['id' => 14, 'name' => 'Росбанк', 'code' => 'rosbank_rub', 'currency' => Currency::RUB(), 'detail_types' => [DetailType::CARD]],
        ];

        foreach ($payment_gateways as $payment_gateway) {
            PaymentGateway::create([
                'name' => $payment_gateway['name'],
                'code' => $payment_gateway['code'],
                'currency' => $payment_gateway['currency'],
                'min_limit' => 1000,
                'max_limit' => 100000,
                'sms_senders' => $payment_gateway['sms_senders'] ?? [],
                'trader_commission_rate_for_orders' => 2.5,
                'detail_types' => $payment_gateway['detail_types'],
                'sub_payment_gateways' => ! empty($payment_gateway['sub_payment_gateways']) ? $payment_gateway['sub_payment_gateways'] : [],
            ]);
        }

        services()->settings()->createAll();

        //commands
        Artisan::call('app:update-p2p-prices');
        Artisan::call('app:load-payment-methods-list');
    }
}
