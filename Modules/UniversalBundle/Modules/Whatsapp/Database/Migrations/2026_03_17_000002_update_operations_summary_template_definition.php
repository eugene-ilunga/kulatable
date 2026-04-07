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
            ->where('notification_type', 'operations_summary')
            ->update([
                'template_json' => json_encode([
                    'name' => 'operations_summary',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'DOCUMENT',
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Here is the daily operations summary for branch {{1}} on the date of {{2}}. The total number of orders processed today is {{3}}, the total revenue generated for today is {{4}}, the total number of reservations handled today is {{5}}, and here are the combined staff on duty and peak hours information: {{6}}. The end of day summary has been completed successfully and is ready for review!',
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'End of day summary',
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Report',
                                    'url' => rtrim(config('app.url'), '/') . '/reports/sales-report',
                                    'example' => [rtrim(config('app.url'), '/') . '/reports/sales-report'],
                                ],
                            ],
                        ],
                    ],
                ], JSON_PRETTY_PRINT),
                'sample_variables' => json_encode([
                    'Branch name',
                    'Date',
                    'Total orders',
                    'Total revenue',
                    'Total reservations',
                    'Staff on duty and Peak hours (combined)',
                ]),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep latest template definition on rollback.
    }
};
