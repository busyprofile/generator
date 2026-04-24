<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserDeviceResource;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserDeviceController extends Controller
{
    /**
     * Отображает список устройств пользователя
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $devices = Auth::user()
            ->devices()
            ->orderBy('created_at', 'desc')
            ->get();
        $devices = UserDeviceResource::collection($devices);

        return Inertia::render('UserDevice/Index', compact('devices'));
    }

    /**
     * Создает новое устройство
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $device = new UserDevice();
        $device->user_id = Auth::id();
        $device->name = $request->name;
        $device->token = UserDevice::generateToken();
        $device->save();

        return redirect()->route('trader.devices.index')->with('success', 'Токен для устройства успешно создан');
    }
}
