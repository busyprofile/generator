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
        Schema::table('payment_details', function (Blueprint $table) {
            $table->decimal('unique_amount_percentage', 5, 2)
                ->nullable()
                ->default(3.0) // значение по умолчанию 3% (соответствует 0.97-1.03)
                ->after('max_order_amount')
                ->comment('Процент отклонения для проверки уникальности суммы заказа');
                
            $table->integer('unique_amount_seconds')
                ->nullable()
                ->default(600) // значение по умолчанию 600 секунд (10 минут)
                ->after('unique_amount_percentage')
                ->comment('Интервал времени в секундах для проверки уникальности суммы заказа');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_details', function (Blueprint $table) {
            $table->dropColumn(['unique_amount_percentage', 'unique_amount_seconds']);
        });
    }
}; 