<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Hotel\Enums\StayStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hotel_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('reservation_id')->nullable()->constrained('hotel_reservations')->onDelete('set null'); // Nullable for walk-ins
            $table->foreignId('room_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->string('stay_number')->unique();
            $table->timestamp('check_in_at');
            $table->timestamp('expected_checkout_at');
            $table->timestamp('actual_checkout_at')->nullable();
            $table->enum('status', array_column(StayStatus::cases(), 'value'))->default(StayStatus::CHECKED_IN->value);
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->decimal('credit_limit', 10, 2)->nullable(); // Credit limit for posting charges
            $table->text('check_in_notes')->nullable();
            $table->text('check_out_notes')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['restaurant_id', 'branch_id']);
            $table->index('reservation_id');
            $table->index('room_id');
            $table->index('status');
            $table->index(['check_in_at', 'expected_checkout_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_stays');
    }
};
