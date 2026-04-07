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
                if (!Schema::hasColumn('cash_register_transactions', 'payment_method')) {
                    $table->string('payment_method')->nullable()->after('payment_id');
                    $table->index('payment_method');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_register_transactions')) {
            Schema::table('cash_register_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('cash_register_transactions', 'payment_method')) {
                    $table->dropIndex(['payment_method']);
                    $table->dropColumn('payment_method');
                }
            });
        }
    }
};
