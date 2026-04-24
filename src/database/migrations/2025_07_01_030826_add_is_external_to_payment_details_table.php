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
            $table->boolean('is_external')->default(false)->after('is_active')->comment('Флаг внешних (партнерских) реквизитов');
            $table->index('is_external');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_details', function (Blueprint $table) {
            $table->dropIndex(['is_external']);
            $table->dropColumn('is_external');
        });
    }
};
