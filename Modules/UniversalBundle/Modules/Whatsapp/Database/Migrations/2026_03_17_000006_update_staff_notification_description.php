<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('whatsapp_template_definitions')) {
            return;
        }

        DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'staff_notification')
            ->update([
                'description' => 'Unified template for staff-related notifications (table assignment, waiter request, waiter acknowledgment)',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep the corrected description on rollback.
    }
};
