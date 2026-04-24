<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserDeviceResource;
use App\Models\UserDevice;
use Inertia\Inertia;

class DeviceController extends Controller
{
    public function index()
    {
        $filters = $this->getTableFilters();

        $devices = UserDevice::query()
            ->with(['user'])
            ->when($filters->search, function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . $filters->search . '%')
                        ->orWhere('android_id', 'like', '%' . $filters->search . '%')
                        ->orWhere('device_model', 'like', '%' . $filters->search . '%')
                        ->orWhere('manufacturer', 'like', '%' . $filters->search . '%')
                        ->orWhere('brand', 'like', '%' . $filters->search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($filters) {
                            $userQuery->where('name', 'like', '%' . $filters->search . '%')
                                ->orWhere('email', 'like', '%' . $filters->search . '%');
                        });
                });
            })
            ->when($filters->user, function ($query) use ($filters) {
                $query->whereHas('user', function ($userQuery) use ($filters) {
                    $userQuery->where('name', 'like', '%' . $filters->user . '%')
                        ->orWhere('email', 'like', '%' . $filters->user . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(request()->per_page ?? 10);

        $devices = UserDeviceResource::collection($devices);

        return Inertia::render('Support/Device/Index', compact('devices', 'filters'));
    }
} 