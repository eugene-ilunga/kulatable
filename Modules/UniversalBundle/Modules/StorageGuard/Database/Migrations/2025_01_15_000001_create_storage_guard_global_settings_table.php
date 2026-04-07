<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Modules\StorageGuard\Entities\StorageGuardGlobalSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('storage_guard_global_settings')) {
            return;
        }

        Schema::create('storage_guard_global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('license_type', 20)->nullable();
            $table->string('purchase_code')->nullable();
            $table->timestamp('purchased_on')->nullable();
            $table->timestamp('supported_until')->nullable();
            $table->boolean('notify_update')->default(1);
            $table->timestamps();
        });
        StorageGuardGlobalSetting::firstOrCreate([]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_guard_global_settings');
    }
};
