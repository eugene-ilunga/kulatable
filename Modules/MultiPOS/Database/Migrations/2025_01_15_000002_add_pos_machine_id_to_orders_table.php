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
        if (!Schema::hasColumn('orders', 'pos_machine_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('pos_machine_id')->nullable()->after('branch_id');

                $table->foreign('pos_machine_id')
                    ->references('id')
                    ->on('pos_machines')
                    ->onDelete('set null');

                $table->index('pos_machine_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pos_machine_id']);
            $table->dropColumn('pos_machine_id');
        });
    }
};
