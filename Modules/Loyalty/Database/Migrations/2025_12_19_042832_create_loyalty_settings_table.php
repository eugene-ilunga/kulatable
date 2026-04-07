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
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            // Earn rule: points per ₹ spent (example: 1 point per ₹100)
            $table->decimal('earn_rate_rupees', 10, 2)->default(100); // spend ₹100
            $table->integer('earn_rate_points')->default(1);          // earn 1 point
            // Redemption rule: value per point (example: 1 point = ₹1)
            $table->decimal('value_per_point', 10, 2)->default(1);
            // Safety caps
            $table->integer('min_redeem_points')->default(50);
            $table->decimal('max_discount_percent', 5, 2)->default(20); // max 20% of subtotal
            $table->timestamps();
            $table->unique('restaurant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_settings');
    }
};
