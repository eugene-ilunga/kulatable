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
        if (!Schema::hasTable('whatsapp_template_mappings')) {
            Schema::create('whatsapp_template_mappings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('restaurant_id');
                $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
                $table->string('notification_type', 100)->comment('e.g., order_confirmation, reservation_confirmation');
                $table->string('template_name', 100)->comment('Actual template name in WhatsApp Business Portal');
                $table->string('template_id')->nullable()->comment('WhatsApp template ID for reference');
                $table->string('language_code', 10)->default('en');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['restaurant_id', 'notification_type', 'language_code'], 'unique_restaurant_template');
                $table->index('restaurant_id');
                $table->index('notification_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_template_mappings');
    }
};
