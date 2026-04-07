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
        Schema::create('loyalty_stamp_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->integer('stamps_required')->default(1); // Number of stamps needed to redeem
            $table->enum('reward_type', ['free_item', 'discount_percent', 'discount_amount'])->default('free_item');
            $table->decimal('reward_value', 10, 2)->nullable(); // For discount_percent or discount_amount
            $table->foreignId('reward_menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete(); // For free_item reward
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['restaurant_id', 'is_active']);
            $table->index(['menu_item_id']);
            $table->unique(['restaurant_id', 'menu_item_id']); // One rule per menu item per restaurant
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_stamp_rules');
    }
};
