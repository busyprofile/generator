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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('trader_commission_rate', 5, 2)->nullable()->after('referral_commission_percentage')
                ->comment('Индивидуальная комиссия трейдера (в процентах). Если null, используется комиссия из платежного шлюза.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trader_commission_rate');
        });
    }
}; 