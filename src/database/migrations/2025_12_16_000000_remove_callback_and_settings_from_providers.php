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
        Schema::table('providers', function (Blueprint $table) {
            if (Schema::hasColumn('providers', 'callback_url')) {
                $table->dropColumn('callback_url');
            }
            if (Schema::hasColumn('providers', 'additional_settings')) {
                $table->dropColumn('additional_settings');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('callback_url')->nullable()->after('is_active');
            $table->json('additional_settings')->nullable()->after('callback_url');
        });
    }
};

