<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        $columns = DB::select('SHOW COLUMNS FROM delivery_partner_device_tokens');

        $columnMap = [];

        foreach ($columns as $col) {
            $columnMap[$col->Field] = $col;
        }

        $hasRegistrationId = array_key_exists('registration_id', $columnMap);
        $hasDeviceId = array_key_exists('device_id', $columnMap);

        // If registration_id doesn't exist, convert it from device_id (older/broken schema) or add it.
        if (! $hasRegistrationId) {
            if ($hasDeviceId) {
                $type = $columnMap['device_id']->Type ?? 'VARCHAR(255)';
                $null = ($columnMap['device_id']->Null ?? 'YES') === 'NO' ? 'NOT NULL' : 'NULL';

                $sql = 'ALTER TABLE delivery_partner_device_tokens CHANGE device_id registration_id ' . $type . ' ' . $null;
                DB::statement($sql);
            } else {
                DB::statement('ALTER TABLE delivery_partner_device_tokens ADD registration_id VARCHAR(500) NOT NULL DEFAULT ""');
            }
        }

        // Ensure we still have a device_id column because POS register/updateOrCreate expects it.
        $columnsAfter = DB::select('SHOW COLUMNS FROM delivery_partner_device_tokens');
        $columnMapAfter = [];

        foreach ($columnsAfter as $col) {
            $columnMapAfter[$col->Field] = $col;
        }

        if (! array_key_exists('device_id', $columnMapAfter)) {
            DB::statement('ALTER TABLE delivery_partner_device_tokens ADD device_id VARCHAR(255) NOT NULL DEFAULT ""');
        }
    }

    public function down(): void
    {
        // Best-effort rollback (schema is environment-dependent).
    }
};

