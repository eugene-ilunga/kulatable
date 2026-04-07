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
        Schema::table('loyalty_settings', function (Blueprint $table) {
            $table->enum('loyalty_type', ['points', 'stamps', 'both'])->default('points')->after('enabled');
            $table->boolean('enable_points')->default(true)->after('loyalty_type');
            $table->boolean('enable_stamps')->default(false)->after('enable_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_settings', function (Blueprint $table) {
            $table->dropColumn(['loyalty_type', 'enable_points', 'enable_stamps']);
        });
    }
};
