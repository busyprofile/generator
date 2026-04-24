<?php

namespace App\Http\Controllers\API\Bot;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Money\Money;
use App\Services\Money\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Регистрация нового пользователя-трейдера
     */
    public function register(Request $request)
    {
        $request->validate([
            'id' => 'required|string|max:255',
            'username' => 'required|string|max:255',
        ]);

        $telegramId = $request->input('id');
        $username = $request->input('username');
        
        // Формируем email и пароль
        $email = $telegramId . '@gmail.com';
        $password = $username . $telegramId;

        // Проверяем, не существует ли уже пользователь с таким email
        if (User::where('email', $email)->exists()) {
            return response()->failWithMessage('Пользователь с таким ID уже существует.');
        }

        try {
            DB::beginTransaction();

            // Генерируем дефолтную аватарку
            $avatarUrl = 'https://api.dicebear1.com/9.x/adventurer/svg?seed=' . urlencode($email);

            // Создаем пользователя
            $user = User::create([
                'name' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'avatar' => $avatarUrl,
                'apk_access_token' => Str::random(60),
                'api_access_token' => Str::random(60),
                'is_online' => false,
                'is_payout_online' => false,
                'is_vip' => false,
                'payouts_enabled' => true,
                'stop_traffic' => false,
                'trader_commission_rate' => 0.0,
                'referral_commission_percentage' => 0.0,
            ]);

            // Назначаем роль трейдера
            $user->assignRole('Trader');

            // Создаем кошелек с страховым депозитом $1000
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'merchant_balance' => Money::fromUnits('0', Currency::USD()),
                'trust_balance' => Money::fromUnits('0', Currency::USD()),
                'reserve_balance' => Money::fromUnits('100000', Currency::USD()), // $1000.00 в центах
                'commission_balance' => Money::fromUnits('0', Currency::USD()),
                'teamleader_balance' => Money::fromUnits('0', Currency::USD()),
            ]);

            DB::commit();

            return response()->success([
                'message' => 'Пользователь успешно зарегистрирован',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => 'Trader',
                    'reserve_balance' => '$1000.00',
                ],
                'credentials' => [
                    'email' => $email,
                    'password' => $password,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->failWithMessage('Ошибка при создании пользователя: ' . $e->getMessage());
        }
    }

    /**
     * Добавление баланса пользователю по email
     */
    public function addBalance(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0.01',
            'balance_type' => 'required|string|in:merchant,trust,reserve,commission,teamleader',
        ]);

        $email = $request->input('email');
        $amount = $request->input('amount');
        $balanceType = $request->input('balance_type');

        try {
            DB::beginTransaction();

            // Находим пользователя
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                return response()->failWithMessage('Пользователь не найден.');
            }

            // Получаем или создаем кошелек
            $wallet = $user->wallet;
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'merchant_balance' => Money::fromUnits('0', Currency::USD()),
                    'trust_balance' => Money::fromUnits('0', Currency::USD()),
                    'reserve_balance' => Money::fromUnits('0', Currency::USD()),
                    'commission_balance' => Money::fromUnits('0', Currency::USD()),
                    'teamleader_balance' => Money::fromUnits('0', Currency::USD()),
                ]);
            }

            // Конвертируем сумму в центы
            $amountInCents = (int) ($amount * 100);
            $moneyAmount = Money::fromUnits((string) $amountInCents, Currency::USD());

            // Определяем поле баланса для обновления
            $balanceField = $balanceType . '_balance';
            
            // Получаем текущий баланс и добавляем новую сумму
            $currentBalance = $wallet->{$balanceField};
            $newBalance = $currentBalance->add($moneyAmount);
            
            // Обновляем баланс
            $wallet->update([
                $balanceField => $newBalance
            ]);

            DB::commit();

            return response()->success([
                'message' => 'Баланс успешно добавлен',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'balance_update' => [
                    'type' => $balanceType,
                    'added_amount' => '$' . number_format($amount, 2),
                    'new_balance' => '$' . number_format($newBalance->toUnits() / 100, 2),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->failWithMessage('Ошибка при добавлении баланса: ' . $e->getMessage());
        }
    }
} 