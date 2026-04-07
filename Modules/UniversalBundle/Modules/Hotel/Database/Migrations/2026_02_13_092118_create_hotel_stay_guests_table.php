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
        Schema::create('hotel_stay_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stay_id')->constrained('hotel_stays')->onDelete('cascade');
            $table->foreignId('guest_id')->constrained('hotel_guests')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['stay_id', 'guest_id']);
            $table->index('stay_id');
            $table->index('guest_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_stay_guests');
    }
};
