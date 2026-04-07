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
        Schema::table('loyalty_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_accounts', 'tier_id')) {
                $table->foreignId('tier_id')->nullable()->after('points_balance')->constrained('loyalty_tiers')->nullOnDelete();
                $table->index('tier_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_accounts', 'tier_id')) {
                $table->dropForeign(['tier_id']);
                $table->dropIndex(['tier_id']);
                $table->dropColumn('tier_id');
            }
        });
    }
};
