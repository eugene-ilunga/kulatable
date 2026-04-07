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
        Schema::create('sms_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            $table->datetime('date'); // Changed from date to datetime
            $table->enum('gateway', ['vonage', 'msg91']);
            $table->enum('type', ['reservation_confirmed', 'order_bill_sent', 'send_otp']);
            $table->integer('count')->default(1);
            $table->unsignedBigInteger('package_id')->nullable();
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            
            $table->unique(['restaurant_id', 'branch_id', 'type', 'gateway', 'date'], 'sms_usage_unique');
            
            // Indexes for better performance
            $table->index(['restaurant_id', 'date']);
            $table->index(['gateway', 'type']);
            $table->index(['date', 'gateway']);
            $table->index(['package_id', 'date']); // Keep package_id index for filtering
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_usage_logs');
    }
};
