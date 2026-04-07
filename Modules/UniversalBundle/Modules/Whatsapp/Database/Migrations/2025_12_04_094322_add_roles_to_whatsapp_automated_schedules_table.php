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
        Schema::table('whatsapp_automated_schedules', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('scheduled_day')->comment('Array of role IDs to send notifications to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_automated_schedules', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
