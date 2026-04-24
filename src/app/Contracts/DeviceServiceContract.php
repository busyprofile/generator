<?php

namespace App\Contracts;

use App\Models\UserDevice;

interface DeviceServiceContract
{
    public function get(string $token): ?UserDevice;

    public function update(UserDevice $device, string $android_id, string $device_model, string $android_version, string $manufacturer, string $brand): UserDevice;
}
