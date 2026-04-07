<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cash_register_transactions')) {
            Schema::table('cash_register_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('cash_register_transactions', 'payment_id')) {
                    $table->unsignedBigInteger('payment_id')->nullable()->after('order_id');
                    $table->index('payment_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_register_transactions')) {
            Schema::table('cash_register_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('cash_register_transactions', 'payment_id')) {
                    $table->dropIndex(['payment_id']);
                    $table->dropColumn('payment_id');
                }
            });
        }
    }
};
