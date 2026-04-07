<?php

namespace Modules\Aitools\Observers;

use App\Models\Restaurant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RestaurantObserver
{
    /**
     * Handle the Restaurant "saving" event.
     */
    public function saving(Restaurant $restaurant): void
    {
        // Only process if Aitools module is enabled
        if (!module_enabled('Aitools')) {
            return;
        }

        // Reset AI monthly token count when package changes
        if ($restaurant->isDirty('package_id')) {
            // Save current month's usage to history before resetting
            $currentMonth = now()->format('Y-m');
            $tokensUsed = $restaurant->ai_monthly_tokens_used ?? 0;
            $oldPackage = \App\Models\Package::find($restaurant->getOriginal('package_id'));
            $tokenLimit = $oldPackage->ai_monthly_token_limit ?? -1;
            
            if ($tokensUsed > 0 && Schema::hasTable('ai_token_usage_history')) {
                DB::table('ai_token_usage_history')->updateOrInsert(
                    [
                        'restaurant_id' => $restaurant->id,
                        'month' => $currentMonth,
                    ],
                    [
                        'tokens_used' => $tokensUsed,
                        'token_limit' => $tokenLimit,
                        'updated_at' => now(),
                    ]
                );
            }
            
            $restaurant->ai_monthly_tokens_used = 0;
            $restaurant->ai_monthly_reset_at = now()->addMonth()->startOfMonth();
        }

        // Reset AI monthly token count when subscription renews (license_expire_on is updated)
        if ($restaurant->isDirty('license_expire_on')) {
            $oldExpireOn = $restaurant->getOriginal('license_expire_on');
            $newExpireOn = $restaurant->license_expire_on;

            // If the expiration date is being extended (renewal), save history and reset monthly count
            if ($oldExpireOn && $newExpireOn && $newExpireOn->greaterThan($oldExpireOn)) {
                $currentMonth = now()->format('Y-m');
                $tokensUsed = $restaurant->ai_monthly_tokens_used ?? 0;
                $tokenLimit = $restaurant->package->ai_monthly_token_limit ?? -1;
                
                if ($tokensUsed > 0 && Schema::hasTable('ai_token_usage_history')) {
                    \DB::table('ai_token_usage_history')->updateOrInsert(
                        [
                            'restaurant_id' => $restaurant->id,
                            'month' => $currentMonth,
                        ],
                        [
                            'tokens_used' => $tokensUsed,
                            'token_limit' => $tokenLimit,
                            'updated_at' => now(),
                        ]
                    );
                }
                
                $restaurant->ai_monthly_tokens_used = 0;
                $restaurant->ai_monthly_reset_at = now()->addMonth()->startOfMonth();
            }
        }
    }
}
