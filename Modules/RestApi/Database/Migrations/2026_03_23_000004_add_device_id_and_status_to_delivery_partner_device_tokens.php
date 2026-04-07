<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (! Schema::hasTable('delivery_partner_device_tokens')) {
            return;
        }

        Schema::table('delivery_partner_device_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('delivery_partner_device_tokens', 'device_id')) {
                $table->string('device_id', 255)->nullable()->after('fcm_token');
            }

            if (! Schema::hasColumn('delivery_partner_device_tokens', 'status')) {
                $table->string('status', 20)->default('active')->after('device_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_partner_device_tokens')) {
            return;
        }

        Schema::table('delivery_partner_device_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_partner_device_tokens', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('delivery_partner_device_tokens', 'device_id')) {
                $table->dropColumn('device_id');
            }
        });
    }

};

