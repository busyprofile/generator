<?php

namespace App\Services\TelegramBot\Commands;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Запускает бота';

    public function handle()
    {
        $this->replyWithMessage([
            'text' => 'Добро пожаловать! Здесь вы будете получать уведомления!'
        ]);
    }
}
