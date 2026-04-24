<?php

namespace App\Services\TelegramBot;

use App\Contracts\TelegramBotServiceContract;
use App\Services\TelegramBot\Features\EditPaymentDetail;
use App\Services\TelegramBot\Notifications\Notification;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramBotService implements TelegramBotServiceContract
{
    public function __construct(
        protected string $webhookToken
    )
    {}

    public function setWebhook(): bool
    {
        return Telegram::setWebhook([
            'url' => route('telegram-bot.webhook', $this->webhookToken)
        ]);
    }

    public function sendNotification(Notification $notification): bool
    {
        if ($notification->getMemberStatus() === 'kicked') {
            return false;
        }

        try {
            Telegram::sendMessage([
                'chat_id' => $notification->getChat(),
                'text' => $notification->getMessage(),
                'parse_mode' => 'HTML'
            ]);
        } catch (\Throwable $e) {
            //TODO логировать ошибки, и убрать ошибку Bad Request: chat not found
        }

        $notification->afterSend();

        return true;
    }

    public function handleWebhook(): void
    {
        $update = Telegram::commandsHandler(true);

        try {
            if ($update?->myChatMember?->newChatMember?->status === 'kicked') {
                \App\Models\Telegram::where('telegram_id', $update->myChatMember->from->id)->update([
                    'member_status' => $update?->myChatMember?->newChatMember?->status
                ]);
            } else if ($update?->myChatMember?->newChatMember?->status === 'member') {
                $telegram = \App\Models\Telegram::where('telegram_id', $update->myChatMember->from->id)
                    ->first();

                if ($telegram->member_status === 'kicked') {
                    \App\Models\Telegram::where('telegram_id', $update->myChatMember->from->id)->update([
                        'member_status' => $update?->myChatMember?->newChatMember?->status
                    ]);
                }
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
