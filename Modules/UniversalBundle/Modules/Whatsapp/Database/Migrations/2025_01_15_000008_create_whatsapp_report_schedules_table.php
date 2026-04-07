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
        Schema::create('whatsapp_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->string('report_type')->comment('e.g., daily_sales, weekly_sales, monthly_sales, etc.');
            $table->string('frequency')->comment('daily, weekly, monthly');
            $table->time('scheduled_time')->comment('Time to send report, e.g., 09:00');
            $table->string('scheduled_day')->nullable()->comment('For weekly: monday, tuesday, etc. For monthly: 1-31');
            $table->json('recipients')->nullable()->comment('Array of phone numbers to send reports to');
            $table->boolean('is_enabled')->default(false);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'report_type'], 'wa_report_sched_rest_report_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_report_schedules');
    }
};

