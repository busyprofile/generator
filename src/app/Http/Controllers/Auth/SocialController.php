<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Telegram;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function callback(Request $request)
    {
        $user = \Laravel\Socialite\Facades\Socialite::driver('telegram')->user();

        if ($request->user()->telegram) {
            $request->user()->telegram->update(['telegram_id' => $user->getId()]);
        } else {
            Telegram::create([
                'telegram_id' => $user->getId(),
                'member_status' => 'member',
                'user_id' => $request->user()->id,
            ]);
        }


        return redirect()->route('notifications.index');
    }
}
