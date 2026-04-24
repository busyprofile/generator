<?php

namespace App\Contracts;

use App\Services\TelegramBot\Notifications\Notification;

interface TelegramBotServiceContract
{
    public function setWebhook(): bool;

    public function sendNotification(Notification $notification): bool;

    public function handleWebhook(): void;
}
