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
        Schema::create('hotel_folio_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('hotel_folios')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null'); // Link to existing payment if available
            $table->string('payment_method'); // cash, card, upi, bank_transfer, etc.
            $table->decimal('amount', 10, 2);
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('folio_id');
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_folio_payments');
    }
};
