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
        if (!Schema::hasTable('hotel_taxes')) {
            Schema::create('hotel_taxes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
                $table->string('name');
                $table->decimal('rate', 8, 2)->default(0); // percentage
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['restaurant_id', 'branch_id']);
            });
        }

        // hotel_extra_services table (meals, parking, etc.)
        if (!Schema::hasTable('hotel_extra_services')) {
            Schema::create('hotel_extra_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
                $table->string('name');
                $table->decimal('price', 10, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['restaurant_id', 'branch_id']);
            });
        }

        // Add columns to hotel_reservations
        Schema::table('hotel_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_reservations', 'reason_for_trip')) {
                $table->text('reason_for_trip')->nullable()->after('special_requests');
            }
            if (!Schema::hasColumn('hotel_reservations', 'means_of_transport')) {
                $table->string('means_of_transport')->nullable()->after('reason_for_trip');
            }
            if (!Schema::hasColumn('hotel_reservations', 'place_of_origin')) {
                $table->string('place_of_origin')->nullable()->after('means_of_transport');
            }
            if (!Schema::hasColumn('hotel_reservations', 'vehicle_registration_number')) {
                $table->string('vehicle_registration_number')->nullable()->after('place_of_origin');
            }
            if (!Schema::hasColumn('hotel_reservations', 'final_destination')) {
                $table->string('final_destination')->nullable()->after('vehicle_registration_number');
            }
            if (!Schema::hasColumn('hotel_reservations', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('final_destination'); // percentage, fixed
            }
            if (!Schema::hasColumn('hotel_reservations', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->default(0)->after('discount_type');
            }
            if (!Schema::hasColumn('hotel_reservations', 'subtotal_before_tax')) {
                $table->decimal('subtotal_before_tax', 10, 2)->nullable()->after('discount_value');
            }
            if (!Schema::hasColumn('hotel_reservations', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal_before_tax');
            }
            if (!Schema::hasColumn('hotel_reservations', 'extras_amount')) {
                $table->decimal('extras_amount', 10, 2)->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('hotel_reservations', 'tax_id')) {
                $table->foreignId('tax_id')->nullable()->after('extras_amount')->constrained('hotel_taxes')->onDelete('set null');
            }
        });

        // hotel_reservation_guests pivot for multiple guests per reservation
        if (!Schema::hasTable('hotel_reservation_guests')) {
            Schema::create('hotel_reservation_guests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained('hotel_reservations')->onDelete('cascade');
                $table->foreignId('guest_id')->constrained('hotel_guests')->onDelete('cascade');
                $table->boolean('is_primary')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['reservation_id', 'guest_id']);
                $table->index('reservation_id');
                $table->index('guest_id');
            });
        }

        // hotel_reservation_extras for extras on reservation
        if (!Schema::hasTable('hotel_reservation_extras')) {
            Schema::create('hotel_reservation_extras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained('hotel_reservations')->onDelete('cascade');
                $table->foreignId('extra_service_id')->constrained('hotel_extra_services')->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('total_amount', 10, 2);
                $table->timestamps();
                $table->index('reservation_id');
                $table->index('extra_service_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            $columns = [
                'reason_for_trip', 'means_of_transport', 'place_of_origin',
                'vehicle_registration_number', 'final_destination', 'discount_type',
                'discount_value', 'subtotal_before_tax', 'tax_amount', 'extras_amount', 'tax_id'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('hotel_reservations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('hotel_reservation_extras');
        Schema::dropIfExists('hotel_reservation_guests');
        Schema::dropIfExists('hotel_extra_services');
        Schema::dropIfExists('hotel_taxes');
    }
};
