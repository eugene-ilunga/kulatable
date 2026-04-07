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
        Schema::table('pos_machines', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('token');
            $table->index(['device_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_machines', function (Blueprint $table) {
            $table->dropIndex(['device_id', 'branch_id']);
            $table->dropColumn('device_id');
        });
    }
};
