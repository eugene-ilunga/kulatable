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
            $table->boolean('enable_for_pos')->default(true)->after('enabled');
            $table->boolean('enable_for_customer_site')->default(true)->after('enable_for_pos');
            $table->boolean('enable_for_kiosk')->default(true)->after('enable_for_customer_site');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_settings', function (Blueprint $table) {
            $table->dropColumn(['enable_for_pos', 'enable_for_customer_site', 'enable_for_kiosk']);
        });
    }
};
