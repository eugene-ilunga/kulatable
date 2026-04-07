<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Hotel\Enums\FolioLineType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hotel_folio_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained('hotel_folios')->onDelete('cascade');
            $table->enum('type', array_column(FolioLineType::cases(), 'value'));
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2); // amount - discount + tax
            $table->string('reference_type')->nullable(); // order, service, etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // order_id, service_id, etc.
            $table->date('posting_date');
            $table->text('notes')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('folio_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('posting_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_folio_lines');
    }
};
