<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\MultiPOS\Entities\MultiPOSGlobalSetting;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Module::validateVersion(MultiPOSGlobalSetting::MODULE_NAME);

        if (!Schema::hasTable('multi_pos_global_settings')) {
            Schema::create('multi_pos_global_settings', function (Blueprint $table) {
                $table->id();
                $table->string('purchase_code')->nullable();
                $table->timestamp('supported_until')->nullable();
                $table->timestamp('purchased_on')->nullable();
                $table->string('license_type', 20)->nullable();
                $table->boolean('notify_update')->default(1);
                $table->timestamps();
            });

            MultiPOSGlobalSetting::create([]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('multi_pos_global_settings');
    }
};
