<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('font_control_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('font_control_settings', 'font_local_path')) {
                $table->string('font_local_path')->nullable()->after('font_url');
            }
            if (! Schema::hasColumn('font_control_settings', 'restaurant_id')) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('language_code')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('font_control_settings', function (Blueprint $table) {
            if (Schema::hasColumn('font_control_settings', 'font_local_path')) {
                $table->dropColumn('font_local_path');
            }
            if (Schema::hasColumn('font_control_settings', 'restaurant_id')) {
                $table->dropColumn('restaurant_id');
            }
        });
    }
};
