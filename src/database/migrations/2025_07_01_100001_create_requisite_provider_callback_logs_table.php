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
        Schema::create('requisite_provider_callback_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->index();
            $table->string('provider_name');
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->float('execution_time')->nullable();
            $table->integer('status_code')->nullable();
            $table->boolean('is_successful')->default(false);
            $table->string('error_message')->nullable();
            $table->string('exception_class')->nullable();
            $table->string('exception_message')->nullable();
            $table->timestamps();

            $table->index('provider_name');
            $table->index('merchant_id');
            $table->index('order_id');
            $table->index('is_successful');
            $table->index('created_at');

            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisite_provider_callback_logs');
    }
};
