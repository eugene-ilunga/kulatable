<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\FontControl\Entities\FontControlGlobalSetting;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \App\Models\Module::validateVersion(FontControlGlobalSetting::MODULE_NAME);

        if (!Schema::hasTable('font_control_global_settings')) {
            Schema::create('font_control_global_settings', function (Blueprint $table) {
                $table->id();
                $table->string('purchase_code')->nullable();
                $table->timestamp('supported_until')->nullable();
                $table->timestamp('purchased_on')->nullable();
                $table->string('license_type', 20)->nullable();
                $table->boolean('notify_update')->default(1);
                $table->timestamps();
            });

            FontControlGlobalSetting::create([]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_register_global_settings');
    }
};
