<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to ensure all FontControl columns exist.
 * This replaces the old ensureSchemaIsReady() logic from ServiceProvider.
 */
return new class extends Migration {
    public function up(): void
    {
        // Ensure font_control_settings columns
        if (Schema::hasTable('font_control_settings')) {
            Schema::table('font_control_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('font_control_settings', 'font_local_path')) {
                    $table->string('font_local_path')->nullable()->after('font_url');
                }
                if (!Schema::hasColumn('font_control_settings', 'restaurant_id')) {
                    $table->unsignedBigInteger('restaurant_id')->nullable()->after('language_code')->index();
                }
            });

            // Backfill per-restaurant defaults (run once)
            $this->backfillRestaurantDefaults();
        }

        // Ensure font_control_qr_settings columns
        if (Schema::hasTable('font_control_qr_settings')) {
            Schema::table('font_control_qr_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('font_control_qr_settings', 'label_color')) {
                    $table->string('label_color')->nullable()->after('qr_round_block_size');
                }
                if (!Schema::hasColumn('font_control_qr_settings', 'advanced_qr_enabled')) {
                    $table->boolean('advanced_qr_enabled')->default(false)->after('label_color');
                }
                if (!Schema::hasColumn('font_control_qr_settings', 'qr_logo_path')) {
                    $table->string('qr_logo_path')->nullable()->after('advanced_qr_enabled');
                }
                if (!Schema::hasColumn('font_control_qr_settings', 'qr_logo_size')) {
                    $table->unsignedSmallInteger('qr_logo_size')->nullable()->after('qr_logo_path');
                }
                if (!Schema::hasColumn('font_control_qr_settings', 'qr_ecc_level')) {
                    $table->string('qr_ecc_level')->default('H')->after('qr_logo_size');
                }
            });

            // Seed global QR defaults if missing
            $this->seedQrDefaults();
        }
    }

    public function down(): void
    {
        // Don't remove columns in down() - safer to keep data
    }

    /**
     * Backfill per-restaurant font settings from global default.
     */
    private function backfillRestaurantDefaults(): void
    {
        if (!Schema::hasColumn('font_control_settings', 'restaurant_id')) {
            return;
        }

        if (!Schema::hasTable('restaurants')) {
            return;
        }

        // Use existing global default as template
        $globalDefault = DB::table('font_control_settings')
            ->where('language_code', 'default')
            ->whereNull('restaurant_id')
            ->first();

        if (!$globalDefault) {
            return;
        }

        $template = [
            'font_family' => $globalDefault->font_family ?? 'Figtree',
            'font_size' => $globalDefault->font_size ?? 14,
            'font_url' => $globalDefault->font_url ?? null,
            'font_local_path' => $globalDefault->font_local_path ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $restaurantIds = DB::table('restaurants')->pluck('id');

        foreach ($restaurantIds as $rid) {
            $exists = DB::table('font_control_settings')
                ->where('language_code', 'default')
                ->where('restaurant_id', $rid)
                ->exists();

            if (!$exists) {
                DB::table('font_control_settings')->insert(array_merge($template, [
                    'language_code' => 'default',
                    'restaurant_id' => $rid,
                ]));
            }
        }
    }

    /**
     * Seed global QR settings if missing.
     */
    private function seedQrDefaults(): void
    {
        $exists = DB::table('font_control_qr_settings')->whereNull('restaurant_id')->exists();

        if (!$exists) {
            DB::table('font_control_qr_settings')->insert([
                'restaurant_id' => null,
                'label_format' => '{table_code}',
                'font_family' => 'Noto Sans',
                'font_size' => 16,
                'qr_size' => 300,
                'qr_margin' => 10,
                'qr_foreground_color' => '#000000',
                'qr_background_color' => '#FFFFFF',
                'qr_round_block_size' => true,
                'label_color' => '#000000',
                'advanced_qr_enabled' => false,
                'qr_logo_path' => null,
                'qr_logo_size' => null,
                'qr_ecc_level' => 'H',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
