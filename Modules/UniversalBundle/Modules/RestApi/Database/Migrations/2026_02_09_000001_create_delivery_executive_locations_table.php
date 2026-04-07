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
        Schema::create('delivery_executive_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_executive_id')->constrained('delivery_executives')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('branch_id');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();

            // Short names to stay under MySQL 64-char identifier limit
            $table->index(['order_id', 'created_at'], 'del_exec_loc_order_created');
            $table->index(['delivery_executive_id', 'created_at'], 'del_exec_loc_exec_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_executive_locations');
    }
    
};
