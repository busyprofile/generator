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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->enum('integration', ['GAREX', 'ALPHAPAY', 'METHODPAY', 'X023']);
            $table->foreignId('trader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_consider_balance')->default(false);
            $table->decimal('maximum_balance', 18, 2)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('integration');
            $table->index('is_active');
        });

        Schema::create('provider_terminals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('min_sum', 18, 2)->nullable();
            $table->decimal('max_sum', 18, 2)->nullable();
            $table->integer('time_for_order')->nullable();
            $table->float('rate')->nullable();
            $table->integer('max_response_time_ms')->nullable();
            $table->integer('number_of_retries')->nullable();
            $table->json('additional_settings')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('enabled_detail_types')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'is_active']);
        });

        Schema::create('provider_terminal_merchant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_terminal_id')->constrained('provider_terminals')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['provider_terminal_id', 'merchant_id'], 'ptm_provider_merchant_unique');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('payment_detail_id')->constrained('providers')->nullOnDelete();
            $table->foreignId('provider_terminal_id')->nullable()->after('provider_id')->constrained('provider_terminals')->nullOnDelete();
        });

        Schema::table('requisite_provider_logs', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('provider_name')->constrained('providers')->nullOnDelete();
            $table->foreignId('provider_terminal_id')->nullable()->after('provider_id')->constrained('provider_terminals')->nullOnDelete();

            $table->index(['provider_id', 'created_at']);
            $table->index(['provider_terminal_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisite_provider_logs', function (Blueprint $table) {
            $table->dropIndex(['provider_id', 'created_at']);
            $table->dropIndex(['provider_terminal_id', 'created_at']);
            $table->dropConstrainedForeignId('provider_terminal_id');
            $table->dropConstrainedForeignId('provider_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_id');
            $table->dropConstrainedForeignId('provider_terminal_id');
        });

        Schema::dropIfExists('provider_terminal_merchant');
        Schema::dropIfExists('provider_terminals');
        Schema::dropIfExists('providers');
    }
};
