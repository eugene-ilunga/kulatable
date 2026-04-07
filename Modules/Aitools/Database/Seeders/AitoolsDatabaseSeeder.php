<?php

namespace Modules\Aitools\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;

class AitoolsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('demo')) {
            Restaurant::where('id', 1)->update([
                'ai_enabled' => true,
                'ai_daily_request_limit' => 50,
                'ai_allowed_roles' => json_encode(['admin', 'staff','owner']),
                'ai_monthly_tokens_used' => 0,
                'ai_monthly_reset_at' => now()->addMonth()->startOfMonth()
            ]);
        }
    }
}
