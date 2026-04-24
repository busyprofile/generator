<?php

namespace App\Services\Device;

use App\Contracts\DeviceServiceContract;
use App\Models\UserDevice;

class DeviceService implements DeviceServiceContract
{
    public function get(string $token): ?UserDevice
    {
        return cache()->remember(
            'device_by_token_' . $token,
            now()->addMinutes(10),
            function () use ($token) {
                return UserDevice::where('token', $token)->first();
            }
        );
    }

    public function update(UserDevice $device, string $android_id, string $device_model, string $android_version, string $manufacturer, string $brand): UserDevice
    {
        $device->update([
            'android_id' => $android_id,
            'device_model' => $device_model,
            'android_version' => $android_version,
            'manufacturer' => $manufacturer,
            'brand' => $brand,
            'connected_at' => now(),
        ]);

        cache()->forget('device_by_token_' . $device->token);

        return $device;
    }
}
