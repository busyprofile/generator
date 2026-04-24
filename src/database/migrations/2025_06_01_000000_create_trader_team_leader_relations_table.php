<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trader_team_leader_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trader_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_leader_id')->constrained('users')->onDelete('cascade');
            $table->decimal('commission_percentage', 5, 2)->default(0.00);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            // Уникальная связь между трейдером и тимлидером
            $table->unique(['trader_id', 'team_leader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trader_team_leader_relations');
    }
}; 