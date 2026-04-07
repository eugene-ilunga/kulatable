<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('whatsapp_notification_preferences')) {
            return;
        }

        $restaurantIds = DB::table('whatsapp_notification_preferences')
            ->select('restaurant_id')
            ->distinct()
            ->pluck('restaurant_id');

        foreach ($restaurantIds as $restaurantId) {
            $exists = DB::table('whatsapp_notification_preferences')
                ->where('restaurant_id', $restaurantId)
                ->where('notification_type', 'new_order_alert')
                ->where('recipient_type', 'staff')
                ->exists();

            if ($exists) {
                continue;
            }

            $enabled = DB::table('whatsapp_notification_preferences')
                ->where('restaurant_id', $restaurantId)
                ->whereIn('notification_type', ['staff_notification', 'kitchen_notification'])
                ->where('recipient_type', 'staff')
                ->where('is_enabled', true)
                ->exists();

            DB::table('whatsapp_notification_preferences')->insert([
                'restaurant_id' => $restaurantId,
                'notification_type' => 'new_order_alert',
                'recipient_type' => 'staff',
                'is_enabled' => $enabled,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('whatsapp_notification_preferences')
            ->where('notification_type', 'new_order_alert')
            ->where('recipient_type', 'staff')
            ->delete();
    }
};
