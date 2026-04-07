<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Loyalty\Entities\LoyaltyTier;
use Modules\Loyalty\Entities\LoyaltySetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all restaurants that have loyalty settings
        $restaurants = LoyaltySetting::with('restaurant')->get();
        
        foreach ($restaurants as $setting) {
            $restaurantId = $setting->restaurant_id;
            
            // Check if restaurant already has tiers
            $existingTiers = LoyaltyTier::where('restaurant_id', $restaurantId)->count();
            
            // Only add default tiers if none exist
            if ($existingTiers === 0) {
                // Bronze Tier
                LoyaltyTier::create([
                    'restaurant_id' => $restaurantId,
                    'name' => 'Bronze',
                    'color' => '#CD7F32',
                    'icon' => null,
                    'min_points' => 0,
                    'max_points' => 999,
                    'earning_multiplier' => 1.00,
                    'redemption_multiplier' => 1.00,
                    'order' => 0,
                    'is_active' => true,
                    'description' => 'Starting tier for all customers',
                ]);
                
                // Silver Tier
                LoyaltyTier::create([
                    'restaurant_id' => $restaurantId,
                    'name' => 'Silver',
                    'color' => '#C0C0C0',
                    'icon' => null,
                    'min_points' => 1000,
                    'max_points' => 4999,
                    'earning_multiplier' => 1.25,
                    'redemption_multiplier' => 1.10,
                    'order' => 1000,
                    'is_active' => true,
                    'description' => 'Earn 25% more points, get 10% more value on redemption',
                ]);
                
                // Gold Tier
                LoyaltyTier::create([
                    'restaurant_id' => $restaurantId,
                    'name' => 'Gold',
                    'color' => '#FFD700',
                    'icon' => null,
                    'min_points' => 5000,
                    'max_points' => null,
                    'earning_multiplier' => 1.50,
                    'redemption_multiplier' => 1.20,
                    'order' => 5000,
                    'is_active' => true,
                    'description' => 'Earn 50% more points, get 20% more value on redemption',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all restaurants
        $restaurants = LoyaltySetting::pluck('restaurant_id');
        
        foreach ($restaurants as $restaurantId) {
            // Delete default tiers (Bronze, Silver, Gold)
            LoyaltyTier::where('restaurant_id', $restaurantId)
                ->whereIn('name', ['Bronze', 'Silver', 'Gold'])
                ->delete();
        }
    }
};
