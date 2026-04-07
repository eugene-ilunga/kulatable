<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restapi_global_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('restapi_global_settings', 'firebase_enabled')) {
                $table->boolean('firebase_enabled')->default(false)->after('notify_update');
            }

            if (!Schema::hasColumn('restapi_global_settings', 'firebase_server_key')) {
                $table->text('firebase_server_key')->nullable()->after('firebase_enabled');
            }

            if (!Schema::hasColumn('restapi_global_settings', 'firebase_project_id')) {
                $table->string('firebase_project_id')->nullable()->after('firebase_server_key');
            }

            if (!Schema::hasColumn('restapi_global_settings', 'firebase_priority')) {
                $table->string('firebase_priority', 10)->default('high')->after('firebase_project_id');
            }

            if (!Schema::hasColumn('restapi_global_settings', 'firebase_sound')) {
                $table->string('firebase_sound', 50)->default('default')->after('firebase_priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restapi_global_settings', function (Blueprint $table) {
            if (Schema::hasColumn('restapi_global_settings', 'firebase_sound')) {
                $table->dropColumn('firebase_sound');
            }

            if (Schema::hasColumn('restapi_global_settings', 'firebase_priority')) {
                $table->dropColumn('firebase_priority');
            }

            if (Schema::hasColumn('restapi_global_settings', 'firebase_project_id')) {
                $table->dropColumn('firebase_project_id');
            }

            if (Schema::hasColumn('restapi_global_settings', 'firebase_server_key')) {
                $table->dropColumn('firebase_server_key');
            }

            if (Schema::hasColumn('restapi_global_settings', 'firebase_enabled')) {
                $table->dropColumn('firebase_enabled');
            }
        });
    }
};

