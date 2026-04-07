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
        Schema::create('whatsapp_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->string('notification_type', 100);
            $table->string('recipient_phone', 20);
            $table->string('template_name', 100);
            $table->json('variables')->nullable()->comment('Variables sent with template');
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->string('whatsapp_message_id')->nullable()->comment('Message ID from WhatsApp API');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('restaurant_id');
            $table->index('notification_type');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notification_logs');
    }
};

