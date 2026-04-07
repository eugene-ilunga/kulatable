<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('restaurants') || !DB::getSchemaBuilder()->hasTable('whatsapp_notification_preferences')) {
            return;
        }

        $restaurantIds = DB::table('restaurants')->pluck('id');
        if ($restaurantIds->isEmpty()) {
            return;
        }

        $now = now();

        // Consolidated templates + legacy notification keys used by listeners/commands.
        $defaults = [
            ['notification_type' => 'order_notifications', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_notifications', 'recipient_type' => 'admin'],
            ['notification_type' => 'payment_notification', 'recipient_type' => 'customer'],
            ['notification_type' => 'reservation_notification', 'recipient_type' => 'customer'],
            ['notification_type' => 'new_order_alert', 'recipient_type' => 'admin'],
            ['notification_type' => 'new_order_alert', 'recipient_type' => 'delivery'],
            ['notification_type' => 'kitchen_notification', 'recipient_type' => 'staff'],
            ['notification_type' => 'staff_notification', 'recipient_type' => 'staff'],
            ['notification_type' => 'delivery_notification', 'recipient_type' => 'delivery'],
            ['notification_type' => 'delivery_notification', 'recipient_type' => 'customer'],
            ['notification_type' => 'sales_report', 'recipient_type' => 'staff'],
            ['notification_type' => 'operations_summary', 'recipient_type' => 'staff'],
            ['notification_type' => 'inventory_alert', 'recipient_type' => 'staff'],

            ['notification_type' => 'order_confirmation', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_status_update', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_cancelled', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_bill_invoice', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_cancellation_alert', 'recipient_type' => 'admin'],
            ['notification_type' => 'payment_confirmation', 'recipient_type' => 'customer'],
            ['notification_type' => 'payment_reminder', 'recipient_type' => 'customer'],
            ['notification_type' => 'reservation_confirmation', 'recipient_type' => 'customer'],
            ['notification_type' => 'reservation_status_update', 'recipient_type' => 'customer'],
            ['notification_type' => 'reservation_reminder', 'recipient_type' => 'customer'],
            ['notification_type' => 'reservation_followup', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_ready_to_serve', 'recipient_type' => 'staff'],
            ['notification_type' => 'order_ready_for_pickup', 'recipient_type' => 'customer'],
            ['notification_type' => 'order_ready_for_pickup', 'recipient_type' => 'delivery'],
            ['notification_type' => 'delivery_assignment', 'recipient_type' => 'delivery'],
            ['notification_type' => 'delivery_completion_confirmation', 'recipient_type' => 'delivery'],
            ['notification_type' => 'delivery_completion_confirmation', 'recipient_type' => 'customer'],
            ['notification_type' => 'waiter_request', 'recipient_type' => 'staff'],
            ['notification_type' => 'waiter_request_acknowledgment', 'recipient_type' => 'staff'],
            ['notification_type' => 'notify_waiter', 'recipient_type' => 'staff'],
        ];

        foreach ($restaurantIds as $restaurantId) {
            foreach ($defaults as $default) {
                DB::table('whatsapp_notification_preferences')->updateOrInsert(
                    [
                        'restaurant_id' => $restaurantId,
                        'notification_type' => $default['notification_type'],
                        'recipient_type' => $default['recipient_type'],
                    ],
                    [
                        'is_enabled' => false,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep preferences on rollback to avoid deleting user settings.
    }
};
