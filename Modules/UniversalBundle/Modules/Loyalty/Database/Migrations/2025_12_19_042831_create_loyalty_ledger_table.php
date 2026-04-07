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
        Schema::create('loyalty_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['EARN', 'REDEEM', 'ADJUST', 'EXPIRE']);
            $table->integer('points'); // + for earn/adjust, - for redeem/expire
            $table->string('reason', 200)->nullable();
            $table->timestamp('expires_at')->nullable(); // only for EARN entries if you want expiry later
            $table->timestamps();
            $table->index(['restaurant_id', 'customer_id']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_ledger');
    }
};
