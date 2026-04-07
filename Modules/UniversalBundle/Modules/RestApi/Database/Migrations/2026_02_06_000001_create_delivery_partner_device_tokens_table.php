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
        Schema::create('delivery_partner_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_executive_id')->constrained('delivery_executives')->onDelete('cascade');
            $table->string('delivery_executive_code')->nullable();
            $table->string('fcm_token', 500)->nullable();
            $table->string('platform', 20)->nullable(); // android, ios
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_partner_device_tokens');
    }
};
