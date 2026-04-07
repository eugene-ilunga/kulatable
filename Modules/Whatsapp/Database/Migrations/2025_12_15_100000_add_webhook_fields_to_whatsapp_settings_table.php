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
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_settings', 'webhook_url')) {
                $table->string('webhook_url')->nullable()->after('verify_token')->comment('WhatsApp webhook callback URL');
            }
            if (!Schema::hasColumn('whatsapp_settings', 'webhook_verified_at')) {
                $table->timestamp('webhook_verified_at')->nullable()->after('webhook_url')->comment('When webhook was last verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_settings', 'webhook_url')) {
                $table->dropColumn('webhook_url');
            }
            if (Schema::hasColumn('whatsapp_settings', 'webhook_verified_at')) {
                $table->dropColumn('webhook_verified_at');
            }
        });
    }
};

