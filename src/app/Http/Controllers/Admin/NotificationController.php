<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notification\StoreRequest;
use App\Http\Resources\NotificationResource;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\Notification;
use App\Models\Telegram;
use App\Services\TelegramBot\Notifications\AdminGlobal;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $tgBot = [
            'username' => config('services.telegram.bot'),
            'redirectUrl' => config('services.telegram.redirect'),
            'openTelegramBot' => 'https://t.me/'.config('services.telegram.bot'),
            'user_telegram_id' => auth()->user()->telegram?->id,
        ];

        $notifications = Notification::query()
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);

        $notifications = NotificationResource::collection($notifications);

        return Inertia::render('Notifications/Index', compact('tgBot', 'notifications'));
    }

    public function store(StoreRequest $request)
    {
        $telegrams = Telegram::where('member_status', 'member')->get();

        $notification = Notification::create([
            'message' => $request->message,
            'recipients_count' => $telegrams->count(),
            'delivered_count' => 0,
        ]);

        foreach ($telegrams as $telegram) {
            SendTelegramNotificationJob::dispatch(
                new AdminGlobal(
                    telegram: $telegram,
                    notification: $notification
                )
            );
        }
    }
}
