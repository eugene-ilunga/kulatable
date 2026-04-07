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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('context_type', ['RESTAURANT_TABLE', 'TAKEAWAY', 'DELIVERY', 'HOTEL_ROOM', 'BANQUET_EVENT'])->nullable()->after('order_type_id');
            $table->unsignedBigInteger('context_id')->nullable()->after('context_type');
            $table->enum('bill_to', ['PAY_NOW', 'POST_TO_ROOM'])->default('PAY_NOW')->after('context_id');
            $table->timestamp('posted_to_folio_at')->nullable()->after('bill_to');

            $table->index(['context_type', 'context_id']);
            $table->index('bill_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['context_type', 'context_id']);
            $table->dropIndex(['bill_to']);
            $table->dropColumn(['context_type', 'context_id', 'bill_to', 'posted_to_folio_at']);
        });
    }
};
