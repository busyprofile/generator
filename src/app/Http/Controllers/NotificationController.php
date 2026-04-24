<?php

namespace App\Http\Controllers;

use App\Models\Telegram;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $tgBot = [
            'username' => config('services.telegram.bot'),
            'redirectUrl' => config('services.telegram.redirect'),
            'openTelegramBot' => 'https://t.me/'.config('services.telegram.bot'),
            'user_telegram_id' => auth()->user()->telegram?->telegram_id,
        ];

        return Inertia::render('Notifications/Index', compact('tgBot'));
    }

    public function unlinkTelegram(): RedirectResponse
    {
        $user = auth()->user();
        
        if ($user->telegram) {
            $user->telegram->delete();
        }
        
        return redirect()->route('notifications.index')->with('success', 'Привязка к Telegram успешно удалена');
    }
}
