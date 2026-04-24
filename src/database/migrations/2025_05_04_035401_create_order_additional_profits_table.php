<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_additional_profits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('team_leader_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('commission_rate', 5, 2); // Пример: 5 цифр всего, 2 после запятой (например, 12.34%)
            $table->unsignedBigInteger('profit_amount'); // Сумма прибыли в минимальных единицах (центах)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_additional_profits');
    }
}; 