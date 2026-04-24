<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем роль Merchant Support
        // Role::create(['name' => 'Merchant Support']); // Закомментировано
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // $role = Role::where('name', 'Merchant Support')->first(); // Закомментировано
        // if ($role) {
        //     $role->delete();
        // }
    }
};
