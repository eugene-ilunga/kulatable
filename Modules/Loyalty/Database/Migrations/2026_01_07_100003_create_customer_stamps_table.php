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
        Schema::create('customer_stamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stamp_rule_id')->constrained('loyalty_stamp_rules')->cascadeOnDelete();
            $table->integer('stamps_earned')->default(0); // Current stamps earned for this rule
            $table->integer('stamps_redeemed')->default(0); // Total stamps redeemed for this rule
            $table->timestamp('last_earned_at')->nullable();
            $table->timestamp('last_redeemed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['restaurant_id', 'customer_id', 'stamp_rule_id']);
            $table->index(['restaurant_id', 'customer_id']);
            $table->index(['stamp_rule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_stamps');
    }
};
