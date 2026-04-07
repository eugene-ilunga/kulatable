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
        Schema::create('hotel_reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('hotel_reservations')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('hotel_room_types')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('hotel_rooms')->onDelete('set null'); // Assigned at check-in
            $table->integer('quantity')->default(1);
            $table->decimal('rate', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();

            $table->index('reservation_id');
            $table->index('room_type_id');
            $table->index('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_reservation_rooms');
    }
};
