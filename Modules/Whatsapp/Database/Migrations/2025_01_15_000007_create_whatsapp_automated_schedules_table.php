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
        Schema::create('whatsapp_automated_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->string('notification_type')->comment('e.g., low_stock_alert, birthday_wish, etc.');
            $table->string('schedule_type')->comment('cron, daily, weekly, monthly');
            $table->string('cron_expression')->nullable()->comment('For cron type: e.g., 0 9 * * *');
            $table->time('scheduled_time')->nullable()->comment('For daily/weekly/monthly: e.g., 09:00');
            $table->string('scheduled_day')->nullable()->comment('For weekly: monday, tuesday, etc. For monthly: 1-31');
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'notification_type'], 'wa_auto_sched_rest_notif_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_automated_schedules');
    }
};

