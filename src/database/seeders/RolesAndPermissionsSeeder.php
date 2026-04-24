<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        // Пример: Permission::create(['name' => 'edit articles']);
        // Добавьте сюда необходимые вам разрешения, если они есть

        // create roles and assign existing permissions

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        // Пример назначения прав: $superAdminRole->givePermissionTo('edit articles');

        Role::firstOrCreate(['name' => 'Trader', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Merchant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Team Leader', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Support', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Merchant Support', 'guard_name' => 'web']);

        // Можно добавить больше ролей и прав по необходимости
    }
} 