<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Sms\Entities\SmsGlobalSetting;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('sms_global_settings')) {
            Schema::create('sms_global_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                
                // License information
                $table->string('license_type', 20)->nullable();
                $table->string('purchase_code')->nullable();
                $table->timestamp('purchased_on')->nullable();
                $table->timestamp('supported_until')->nullable();
                $table->boolean('notify_update')->default(1);
                
                // Vonage/Nexmo Credentials (Super Admin Level)
                $table->string('vonage_api_key')->nullable();
                $table->string('vonage_api_secret')->nullable();
                $table->string('vonage_from_number')->nullable();
                
                // MSG91 Credentials (Super Admin Level)
                $table->string('msg91_auth_key')->nullable();
                $table->string('msg91_from')->nullable();
                  // Only status fields - credentials are stored in global settings
                $table->boolean('vonage_status')->default(false);
                $table->boolean('msg91_status')->default(false);
                $table->boolean('phone_verification_status')->default(false);
                $table->timestamps();
            });
        }

        SmsGlobalSetting::create();

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_global_settings');
    }
};
