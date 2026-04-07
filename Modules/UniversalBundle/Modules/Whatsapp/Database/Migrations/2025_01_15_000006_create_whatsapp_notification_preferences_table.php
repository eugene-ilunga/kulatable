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
        Schema::create('whatsapp_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->string('notification_type')->comment('e.g., order_confirmation, order_status_update, etc.');
            $table->string('recipient_type')->comment('customer, admin, staff, delivery');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['restaurant_id', 'notification_type', 'recipient_type'], 'unique_restaurant_notification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notification_preferences');
    }
};

