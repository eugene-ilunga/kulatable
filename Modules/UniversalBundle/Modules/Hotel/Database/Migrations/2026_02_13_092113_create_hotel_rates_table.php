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
        Schema::create('hotel_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('hotel_room_types')->onDelete('cascade');
            $table->foreignId('rate_plan_id')->constrained('hotel_rate_plans')->onDelete('cascade');
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('rate', 10, 2);
            $table->decimal('single_occupancy_rate', 10, 2)->nullable();
            $table->decimal('double_occupancy_rate', 10, 2)->nullable();
            $table->decimal('extra_person_rate', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['restaurant_id', 'branch_id']);
            $table->index(['room_type_id', 'rate_plan_id']);
            $table->index(['date_from', 'date_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_rates');
    }
};
