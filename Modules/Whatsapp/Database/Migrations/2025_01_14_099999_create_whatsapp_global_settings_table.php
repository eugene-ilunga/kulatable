<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Whatsapp\Entities\WhatsappGlobalSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_global_settings')) {
            Schema::create('whatsapp_global_settings', function (Blueprint $table) {
                $table->id();
                $table->string('purchase_code')->nullable();
                $table->timestamp('supported_until')->nullable();
                $table->timestamp('purchased_on')->nullable();
                $table->string('license_type', 20)->nullable();
                $table->boolean('notify_update')->default(1);
                $table->timestamps();
            });
        }

        WhatsappGlobalSetting::firstOrCreate([]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_global_settings');
    }
};
