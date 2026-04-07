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
        Schema::create('whatsapp_template_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type', 100)->unique()->comment('e.g., order_confirmation');
            $table->string('template_name', 100)->comment('Standard template name');
            $table->string('category', 50)->comment('customer/admin/staff/delivery/automated');
            $table->text('description')->nullable();
            $table->text('template_json')->comment('JSON structure for WhatsApp Portal');
            $table->json('sample_variables')->nullable()->comment('Sample variables for testing');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('notification_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_template_definitions');
    }
};

