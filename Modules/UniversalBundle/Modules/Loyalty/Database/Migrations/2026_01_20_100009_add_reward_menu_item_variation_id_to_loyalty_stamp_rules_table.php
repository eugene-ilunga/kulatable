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
        Schema::table('loyalty_stamp_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('loyalty_stamp_rules', 'reward_menu_item_variation_id')) {
                $table->foreignId('reward_menu_item_variation_id')->nullable()->after('reward_menu_item_id')->constrained('menu_item_variations')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loyalty_stamp_rules', function (Blueprint $table) {
            if (Schema::hasColumn('loyalty_stamp_rules', 'reward_menu_item_variation_id')) {
                $table->dropForeign(['reward_menu_item_variation_id']);
                $table->dropColumn('reward_menu_item_variation_id');
            }
        });
    }
};
