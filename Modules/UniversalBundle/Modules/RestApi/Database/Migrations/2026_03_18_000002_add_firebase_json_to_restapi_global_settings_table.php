<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restapi_global_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('restapi_global_settings', 'firebase_service_account_json')) {
                $table->string('firebase_service_account_json')->nullable()->after('firebase_project_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restapi_global_settings', function (Blueprint $table) {
            if (Schema::hasColumn('restapi_global_settings', 'firebase_service_account_json')) {
                $table->dropColumn('firebase_service_account_json');
            }
        });
    }
};

