<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем роль Support (Саппорт)
        // Role::create(['name' => 'Support']); // Закомментировано
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем роль при откате миграции
        // $role = Role::where('name', 'Support')->first(); // Закомментировано
        // if ($role) {
        //     $role->delete();
        // }
    }
};
