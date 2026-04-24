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
        Schema::create('requisite_provider_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name', 100)->index();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('request_type', 50); // get_requisites, supports, etc.
            $table->json('request_params')->nullable();
            $table->json('response_data')->nullable();
            $table->boolean('success')->index();
            $table->text('error_message')->nullable();
            $table->integer('response_time_ms')->nullable(); // время ответа в миллисекундах
            $table->integer('retry_attempt')->default(1);
            $table->string('detail_id')->nullable(); // ID полученного реквизита
            $table->timestamps();

            // Индексы для оптимизации запросов
            $table->index(['provider_name', 'success', 'created_at']);
            $table->index(['merchant_id', 'created_at']);
            $table->index(['order_id']);
            $table->index(['created_at']);

            // Внешние ключи
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisite_provider_logs');
    }
};
