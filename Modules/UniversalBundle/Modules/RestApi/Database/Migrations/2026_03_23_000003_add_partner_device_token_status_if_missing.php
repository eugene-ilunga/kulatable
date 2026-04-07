<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (! Schema::hasTable('partner_device_token')) {
            return;
        }

        $columns = DB::select('SHOW COLUMNS FROM partner_device_token');
        $columnNames = [];

        foreach ($columns as $col) {
            $columnNames[] = $col->Field ?? '';
        }

        if (! in_array('status', $columnNames, true)) {
            DB::statement("ALTER TABLE partner_device_token ADD status VARCHAR(20) NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        // Best-effort rollback.
        if (! Schema::hasTable('partner_device_token')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE partner_device_token DROP COLUMN status');
        } catch (\Throwable $e) {
            // Ignore rollback failures.
            $ignored = $e->getMessage();
        }

    }
};

