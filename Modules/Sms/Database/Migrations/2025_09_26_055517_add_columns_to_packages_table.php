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
        if (!Schema::hasColumn('packages', 'sms_count') && !Schema::hasColumn('packages', 'carry_forward_sms')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->integer('sms_count')->default(-1)->after('branch_limit');
                $table->boolean('carry_forward_sms')->default(false);
            });
        }

        if (!Schema::hasColumn('restaurants', 'count_sms') && !Schema::hasColumn('restaurants', 'total_sms')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->integer('count_sms')->default(0)->after('license_expire_on');
                $table->integer('total_sms')->default(-1)->after('count_sms');
            });
        }

        if (!Schema::hasColumn('global_settings', 'total_vonage_count') && !Schema::hasColumn('global_settings', 'total_msg91_count')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->integer('total_vonage_count')->default(0);
                $table->integer('total_msg91_count')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('sms_count');
            $table->dropColumn('carry_forward_sms');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('count_sms');
            $table->dropColumn('total_sms');
        });

        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn('total_vonage_count');
            $table->dropColumn('total_msg91_count');
        });
    }
};
