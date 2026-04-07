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
        Schema::create('delivery_partner_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_executive_id')->nullable()->constrained('delivery_executives')->nullOnDelete();
            $table->string('delivery_executive_code')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('notification_type', 100);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['delivery_executive_id', 'order_id'], 'dpn_exec_order_idx');
            $table->index('notification_type', 'dpn_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('delivery_partner_notifications');
    }

};

