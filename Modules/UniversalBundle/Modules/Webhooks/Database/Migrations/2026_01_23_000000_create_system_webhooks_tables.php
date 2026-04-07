<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System webhooks - platform-wide, not tied to any restaurant
        if (! Schema::hasTable('system_webhooks')) {
            Schema::create('system_webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('target_url');
                $table->string('secret');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('max_attempts')->default(3);
                $table->unsignedSmallInteger('backoff_seconds')->default(60);
                $table->json('subscribed_events')->nullable(); // null = all events
                $table->json('custom_headers')->nullable();
                $table->timestamps();
            });
        }

        // System webhook deliveries - logs for system webhooks
        if (! Schema::hasTable('system_webhook_deliveries')) {
            Schema::create('system_webhook_deliveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('system_webhook_id')->index();
                $table->unsignedBigInteger('restaurant_id')->nullable()->index(); // Source restaurant if applicable
                $table->string('event');
                $table->string('status')->default('pending'); // pending|succeeded|failed|disabled
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->unsignedSmallInteger('response_code')->nullable();
                $table->integer('duration_ms')->nullable();
                $table->text('response_body')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('next_retry_at')->nullable();
                $table->string('idempotency_key')->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->foreign('system_webhook_id')
                    ->references('id')
                    ->on('system_webhooks')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_webhook_deliveries');
        Schema::dropIfExists('system_webhooks');
    }
};
