<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('font_control_qr_settings')) {
            Schema::create('font_control_qr_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id')->nullable()->index();
                $table->string('label_format')->default('{table_code}');
                $table->string('font_family')->default('Noto Sans');
                $table->unsignedSmallInteger('font_size')->default(16);
                $table->string('font_url')->nullable();
                $table->string('font_local_path')->nullable();
                $table->unsignedSmallInteger('qr_size')->default(300);
                $table->unsignedSmallInteger('qr_margin')->default(10);
                $table->string('qr_foreground_color')->default('#000000');
                $table->string('qr_background_color')->default('#FFFFFF');
                $table->boolean('qr_round_block_size')->default(true);
                $table->string('label_color')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('font_control_qr_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('font_control_qr_settings', 'qr_foreground_color')) {
                    $table->string('qr_foreground_color')->default('#000000');
                }
                if (! Schema::hasColumn('font_control_qr_settings', 'qr_background_color')) {
                    $table->string('qr_background_color')->default('#FFFFFF');
                }
                if (! Schema::hasColumn('font_control_qr_settings', 'qr_round_block_size')) {
                    $table->boolean('qr_round_block_size')->default(true);
                }
                if (! Schema::hasColumn('font_control_qr_settings', 'label_color')) {
                    $table->string('label_color')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('font_control_qr_settings');
    }
};
