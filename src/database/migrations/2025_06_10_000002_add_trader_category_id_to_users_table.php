<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('trader_category_id')->nullable()->after('merchant_id')->constrained('trader_categories')->onDelete('set null');
            
            $table->index('trader_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['trader_category_id']);
            $table->dropIndex(['trader_category_id']);
            $table->dropColumn('trader_category_id');
        });
    }
}; 