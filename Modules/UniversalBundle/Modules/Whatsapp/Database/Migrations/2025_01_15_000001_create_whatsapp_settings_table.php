<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->nullable()->comment('Null for superadmin global settings');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->string('waba_id')->nullable()->comment('WhatsApp Business Account ID');
            $table->text('access_token')->nullable()->comment('Encrypted API access token');
            $table->string('phone_number_id')->nullable()->comment('Phone number ID from WhatsApp');
            $table->string('verify_token')->nullable()->comment('WhatsApp webhook verify token');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique('restaurant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};

