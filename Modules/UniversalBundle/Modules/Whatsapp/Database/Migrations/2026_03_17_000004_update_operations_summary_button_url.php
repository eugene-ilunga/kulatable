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

        $definition = DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'operations_summary')
            ->first();

        if (!$definition || empty($definition->template_json)) {
            return;
        }

        $templateJson = json_decode($definition->template_json, true);
        if (!is_array($templateJson) || empty($templateJson['components'])) {
            return;
        }

        $reportUrl = rtrim(config('app.url'), '/') . '/reports/sales-report';

        foreach ($templateJson['components'] as &$component) {
            if (($component['type'] ?? null) !== 'BUTTONS' || empty($component['buttons'])) {
                continue;
            }

            foreach ($component['buttons'] as &$button) {
                if (($button['type'] ?? null) === 'URL' && ($button['text'] ?? null) === 'View Report') {
                    $button['url'] = $reportUrl;
                    $button['example'] = [$reportUrl];
                }
            }
        }

        DB::table('whatsapp_template_definitions')
            ->where('notification_type', 'operations_summary')
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
