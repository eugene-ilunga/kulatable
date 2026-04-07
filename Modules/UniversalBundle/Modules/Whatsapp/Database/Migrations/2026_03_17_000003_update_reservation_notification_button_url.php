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

        $baseBookingUrl = rtrim(config('app.url'), '/') . '/restaurant/my-bookings/';
        $exampleBookingUrl = $baseBookingUrl . 'demo-restaurant';

        $definition = DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'reservation_notification')
            ->first();

        if (!$definition || empty($definition->template_json)) {
            return;
        }

        $templateJson = json_decode($definition->template_json, true);
        if (!is_array($templateJson) || empty($templateJson['components'])) {
            return;
        }

        foreach ($templateJson['components'] as &$component) {
            if (($component['type'] ?? null) !== 'BUTTONS' || empty($component['buttons'])) {
                continue;
            }

            foreach ($component['buttons'] as &$button) {
                if (($button['type'] ?? null) === 'URL' && ($button['text'] ?? null) === 'View Booking') {
                    $button['url'] = $baseBookingUrl;
                    $button['example'] = [$exampleBookingUrl];
                }
            }
        }

        DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'reservation_notification')
            ->update([
                'template_json' => json_encode($templateJson, JSON_PRETTY_PRINT),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep the updated button URL on rollback.
    }
};
