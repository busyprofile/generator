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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('provider_order_id')->nullable()->after('provider_terminal_id')
                ->comment('ID сделки в системе провайдера (MethodPay, Garex и т.д.)');
            
            $table->index('provider_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['provider_order_id']);
            $table->dropColumn('provider_order_id');
        });
    }
};

