<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_team_leader_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_leader_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('commission_percentage', 5, 2);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Уникальный индекс для предотвращения дублирования связей
            $table->unique(['merchant_id', 'team_leader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_team_leader_relations');
    }
}; 