<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TelegramBotWebhookController extends Controller
{
    public function store(Request $request, string $token)
    {
        if ($token !== config('telegram.bots.mybot.webhook_token')) {
            return 'ok';
        }

        services()->telegramBot()->handleWebhook();

        return 'ok';
    }
}
