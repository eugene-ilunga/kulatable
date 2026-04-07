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
            // Add separate platform fields for points
            // Place after enable_for_kiosk (from 2025_12_29_103153) if it exists, otherwise after enable_stamps
            if (!Schema::hasColumn('loyalty_settings', 'enable_points_for_pos')) {
                if (Schema::hasColumn('loyalty_settings', 'enable_for_kiosk')) {
                    $table->boolean('enable_points_for_pos')->default(true)->after('enable_for_kiosk');
                } elseif (Schema::hasColumn('loyalty_settings', 'enable_stamps')) {
                    $table->boolean('enable_points_for_pos')->default(true)->after('enable_stamps');
                } else {
                    $table->boolean('enable_points_for_pos')->default(true)->after('max_discount_percent');
                }
            }
            if (!Schema::hasColumn('loyalty_settings', 'enable_points_for_customer_site')) {
                $table->boolean('enable_points_for_customer_site')->default(true)->after('enable_points_for_pos');
            }
            if (!Schema::hasColumn('loyalty_settings', 'enable_points_for_kiosk')) {
                $table->boolean('enable_points_for_kiosk')->default(true)->after('enable_points_for_customer_site');
            }
            
            // Add separate platform fields for stamps
            if (!Schema::hasColumn('loyalty_settings', 'enable_stamps_for_pos')) {
                $table->boolean('enable_stamps_for_pos')->default(true)->after('enable_points_for_kiosk');
            }
            if (!Schema::hasColumn('loyalty_settings', 'enable_stamps_for_customer_site')) {
                $table->boolean('enable_stamps_for_customer_site')->default(true)->after('enable_stamps_for_pos');
            }
            if (!Schema::hasColumn('loyalty_settings', 'enable_stamps_for_kiosk')) {
                $table->boolean('enable_stamps_for_kiosk')->default(true)->after('enable_stamps_for_customer_site');
            }
        });

        // Migrate existing data: copy current platform settings to both points and stamps
        // Only if the source columns exist
        if (Schema::hasColumn('loyalty_settings', 'enable_for_pos') && 
            Schema::hasColumn('loyalty_settings', 'enable_points_for_pos')) {
            \Illuminate\Support\Facades\DB::table('loyalty_settings')->update([
                'enable_points_for_pos' => \Illuminate\Support\Facades\DB::raw('enable_for_pos'),
                'enable_points_for_customer_site' => \Illuminate\Support\Facades\DB::raw('enable_for_customer_site'),
                'enable_points_for_kiosk' => \Illuminate\Support\Facades\DB::raw('enable_for_kiosk'),
                'enable_stamps_for_pos' => \Illuminate\Support\Facades\DB::raw('enable_for_pos'),
                'enable_stamps_for_customer_site' => \Illuminate\Support\Facades\DB::raw('enable_for_customer_site'),
                'enable_stamps_for_kiosk' => \Illuminate\Support\Facades\DB::raw('enable_for_kiosk'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_settings', function (Blueprint $table) {
            $table->dropColumn([
                'enable_points_for_pos',
                'enable_points_for_customer_site',
                'enable_points_for_kiosk',
                'enable_stamps_for_pos',
                'enable_stamps_for_customer_site',
                'enable_stamps_for_kiosk',
            ]);
        });
    }
};
