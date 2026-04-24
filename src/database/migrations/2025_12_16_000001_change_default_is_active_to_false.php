<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Изменяем default для is_active в provider_terminals
        DB::statement('ALTER TABLE provider_terminals ALTER COLUMN is_active SET DEFAULT false');
        
        // Изменяем default для is_active в provider_terminal_merchant
        DB::statement('ALTER TABLE provider_terminal_merchant ALTER COLUMN is_active SET DEFAULT false');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем default обратно на true
        DB::statement('ALTER TABLE provider_terminals ALTER COLUMN is_active SET DEFAULT true');
        DB::statement('ALTER TABLE provider_terminal_merchant ALTER COLUMN is_active SET DEFAULT true');
    }
};

