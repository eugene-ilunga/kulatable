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
            ->where('notification_type', 'kitchen_notification')
            ->update([
                'description' => 'Unified template for kitchen-related notifications (new KOT items to prepare).',
                'updated_at' => now(),
            ]);

        DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'staff_notification')
            ->update([
                'description' => 'Unified template for staff-related notifications (table assignment, waiter request)',
                'updated_at' => now(),
            ]);

        DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'reservation_notification')
            ->update([
                'description' => 'Unified template for reservation confirmation, status updates (Confirmed, Cancelled, Pending), and followup messages',
                'sample_variables' => json_encode([
                    'Header: Header text (Reservation Confirmed/Reservation Cancelled/Reservation Pending/Thank You)',
                    'Body 1: Customer name',
                    'Body 2: Message type (your reservation is confirmed/your reservation status has been confirmed/cancelled/set to pending/thank you for visiting)',
                    'Body 3: Number of guests',
                    'Body 4: Date',
                    'Body 5: Time',
                    'Body 6: Additional details (Table number/Status/Feedback link/Restaurant name)',
                    'Button URL: Restaurant hash/slug (for View Booking button)',
                ]),
                'updated_at' => now(),
            ]);

        DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'operations_summary')
            ->update([
                'description' => 'End-of-day operations summary for admin',
                'updated_at' => now(),
            ]);

        DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'kitchen_notification')
            ->update([
                'description' => 'Unified template for kitchen-related notifications (new KOT items to prepare).',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep the corrected descriptions on rollback.
    }
};
