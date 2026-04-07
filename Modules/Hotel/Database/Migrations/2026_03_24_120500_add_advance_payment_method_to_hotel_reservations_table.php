<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_reservations', 'advance_payment_method')) {
                $table->string('advance_payment_method')->nullable()->after('advance_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotel_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_reservations', 'advance_payment_method')) {
                $table->dropColumn('advance_payment_method');
            }
        });
    }
};
