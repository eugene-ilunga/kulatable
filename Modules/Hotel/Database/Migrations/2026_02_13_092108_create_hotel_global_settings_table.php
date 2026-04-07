<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Hotel\Entities\HotelGlobalSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('hotel_global_settings')) {
            Schema::create('hotel_global_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('license_type', 20)->nullable();
                $table->string('purchase_code')->nullable();
                $table->timestamp('purchased_on')->nullable();
                $table->timestamp('supported_until')->nullable();
                $table->boolean('notify_update')->default(1);
                $table->timestamps();
            });
        }
        HotelGlobalSetting::create();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_global_settings');
    }
};
