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
        Schema::table('inventory_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_settings', 'send_stock_summary_email')) {
                $table->boolean('send_stock_summary_email')
                    ->default(false)
                    ->after('allow_auto_purchase');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_settings', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_settings', 'send_stock_summary_email')) {
                $table->dropColumn('send_stock_summary_email');
            }
        });
    }
};

