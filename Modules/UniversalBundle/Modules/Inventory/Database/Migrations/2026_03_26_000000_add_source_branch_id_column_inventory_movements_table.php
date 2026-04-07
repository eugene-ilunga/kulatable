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
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_movements', 'source_branch_id')) {
                $table->unsignedBigInteger('source_branch_id')->nullable()->after('transfer_branch_id');
                $table->foreign('source_branch_id')->references('id')->on('branches')->onDelete('cascade')->onUpdate('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_movements', 'source_branch_id')) {
                $table->dropForeign(['source_branch_id']);
                $table->dropColumn('source_branch_id');
            }
        });
    }
};

