<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sms_global_settings') && ! Schema::hasColumn('sms_global_settings', 'android_sms_gateway_owner')) {
            Schema::table('sms_global_settings', function (Blueprint $table) {
                $table->string('android_sms_gateway_owner', 32)->default('superadmin')->after('android_sms_gateway_password');
            });
        }

        if (! Schema::hasTable('restaurant_android_sms_settings')) {
            Schema::create('restaurant_android_sms_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id');
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('base_url', 512)->nullable();
                $table->string('username', 191)->nullable();
                $table->string('password', 191)->nullable();
                $table->timestamps();

                $table->unique('restaurant_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_android_sms_settings');

        if (Schema::hasTable('sms_global_settings') && Schema::hasColumn('sms_global_settings', 'android_sms_gateway_owner')) {
            Schema::table('sms_global_settings', function (Blueprint $table) {
                $table->dropColumn('android_sms_gateway_owner');
            });
        }
    }
};
