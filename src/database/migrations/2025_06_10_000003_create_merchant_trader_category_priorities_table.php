<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_trader_category_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('trader_category_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('priority')->default(0); // 0 = highest priority
            $table->timestamps();
            
            $table->unique(['merchant_id', 'trader_category_id'], 'merchant_trader_cat_unique');
            $table->index(['merchant_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_trader_category_priorities');
    }
}; 