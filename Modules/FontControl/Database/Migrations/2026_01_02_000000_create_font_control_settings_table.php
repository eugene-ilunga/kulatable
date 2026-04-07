<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('font_control_settings', function (Blueprint $table) {
            $table->id();
            $table->string('language_code')->index();
            $table->string('font_family');
            $table->unsignedSmallInteger('font_size')->default(14);
            $table->string('font_url')->nullable();
            $table->timestamps();
        });

        // Seed sensible defaults for common locales
        DB::table('font_control_settings')->insert([
            [
                'language_code' => 'default',
                'font_family' => 'Figtree',
                'font_size' => 14,
                'font_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'ar',
                'font_family' => 'Cairo',
                'font_size' => 15,
                'font_url' => 'https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('font_control_settings');
    }
};
