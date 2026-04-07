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
        // Add stamp redemption tracking to orders
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'stamp_discount_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('stamp_discount_amount', 10, 2)->default(0)->after('loyalty_discount_amount');
            });
        }

        // Add stamp redemption tracking to order_items
        if (Schema::hasTable('order_items')
            && (!Schema::hasColumn('order_items', 'is_free_item_from_stamp')
                || !Schema::hasColumn('order_items', 'stamp_rule_id'))
        ) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
                    $table->boolean('is_free_item_from_stamp')->default(false)->after('note');
                }
                if (!Schema::hasColumn('order_items', 'stamp_rule_id')) {
                    $table->foreignId('stamp_rule_id')->nullable()->after('is_free_item_from_stamp')
                        ->constrained('loyalty_stamp_rules')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('orders', function (Blueprint $table) {
        //     if (Schema::hasColumn('orders', 'stamp_discount_amount')) {
        //         $table->dropColumn('stamp_discount_amount');
        //     }
        // });

        // Schema::table('order_items', function (Blueprint $table) {
        //     if (Schema::hasColumn('order_items', 'stamp_rule_id')) {
        //         $table->dropForeign(['stamp_rule_id']);
        //         $table->dropColumn('stamp_rule_id');
        //     }
        //     if (Schema::hasColumn('order_items', 'is_free_item_from_stamp')) {
        //         $table->dropColumn('is_free_item_from_stamp');
        //     }
        // });
    }
};
