<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_global_settings')) {
            Schema::table('sms_global_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('sms_global_settings', 'android_sms_gateway_status')) {
                    $table->boolean('android_sms_gateway_status')->default(false)->after('msg91_status');
                }
                if (! Schema::hasColumn('sms_global_settings', 'android_sms_gateway_base_url')) {
                    $table->string('android_sms_gateway_base_url', 512)->nullable()->after('android_sms_gateway_status');
                }
                if (! Schema::hasColumn('sms_global_settings', 'android_sms_gateway_username')) {
                    $table->string('android_sms_gateway_username', 191)->nullable()->after('android_sms_gateway_base_url');
                }
                if (! Schema::hasColumn('sms_global_settings', 'android_sms_gateway_password')) {
                    $table->string('android_sms_gateway_password', 191)->nullable()->after('android_sms_gateway_username');
                }
            });
        }

        if (Schema::hasTable('global_settings') && ! Schema::hasColumn('global_settings', 'total_android_sms_gateway_count')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->integer('total_android_sms_gateway_count')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sms_global_settings')) {
            Schema::table('sms_global_settings', function (Blueprint $table) {
                $columns = [
                    'android_sms_gateway_status',
                    'android_sms_gateway_base_url',
                    'android_sms_gateway_username',
                    'android_sms_gateway_password',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('sms_global_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('global_settings') && Schema::hasColumn('global_settings', 'total_android_sms_gateway_count')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->dropColumn('total_android_sms_gateway_count');
            });
        }
    }
};
