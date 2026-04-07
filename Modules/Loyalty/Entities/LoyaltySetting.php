<?php

namespace Modules\Loyalty\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltySetting extends Model
{
    /** @var array<string, self> */
    private static array $getForRestaurantCache = [];

    protected $table = 'loyalty_settings';
    
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            unset(self::$getForRestaurantCache[(string) $model->restaurant_id]);
        });
    }

    protected $casts = [
        'enabled' => 'boolean',
        'loyalty_type' => 'string',
        'enable_points' => 'boolean',
        'enable_stamps' => 'boolean',
        'enable_for_pos' => 'boolean',
        'enable_for_customer_site' => 'boolean',
        'enable_for_kiosk' => 'boolean',
        'enable_points_for_pos' => 'boolean',
        'enable_points_for_customer_site' => 'boolean',
        'enable_points_for_kiosk' => 'boolean',
        'enable_stamps_for_pos' => 'boolean',
        'enable_stamps_for_customer_site' => 'boolean',
        'enable_stamps_for_kiosk' => 'boolean',
        'earn_rate_rupees' => 'decimal:2',
        'earn_rate_points' => 'integer',
        'value_per_point' => 'decimal:2',
        'min_redeem_points' => 'integer',
        'max_discount_percent' => 'decimal:2',
    ];

    /**
     * Get the restaurant that owns the loyalty settings.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }

    /**
     * Get or create settings for a restaurant.
     */
    public static function getForRestaurant($restaurantId): self
    {
        $key = (string) $restaurantId;
        if (isset(self::$getForRestaurantCache[$key])) {
            return self::$getForRestaurantCache[$key];
        }

        $settings = static::firstOrCreate(
            ['restaurant_id' => $restaurantId],
            [
                'enabled' => true,
                'loyalty_type' => 'points',
                'enable_points' => true,
                'enable_stamps' => false,
                'enable_for_pos' => true,
                'enable_for_customer_site' => true,
                'enable_for_kiosk' => true,
                'enable_points_for_pos' => true,
                'enable_points_for_customer_site' => true,
                'enable_points_for_kiosk' => true,
                'enable_stamps_for_pos' => true,
                'enable_stamps_for_customer_site' => true,
                'enable_stamps_for_kiosk' => true,
                'earn_rate_rupees' => 100,
                'earn_rate_points' => 1,
                'value_per_point' => 1,
                'min_redeem_points' => 50,
                'max_discount_percent' => 20,
            ]
        );

        static::createDefaultTiers($restaurantId);

        return self::$getForRestaurantCache[$key] = $settings;
    }
    
    /**
     * Create default tiers for a restaurant.
     */
    public static function createDefaultTiers($restaurantId): void
    {
        if (\Modules\Loyalty\Entities\LoyaltyTier::where('restaurant_id', $restaurantId)->exists()) {
            return;
        }

        // Bronze Tier
        \Modules\Loyalty\Entities\LoyaltyTier::create([
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
        \Modules\Loyalty\Entities\LoyaltyTier::create([
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
        \Modules\Loyalty\Entities\LoyaltyTier::create([
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

    /**
     * Check if loyalty is enabled for this restaurant.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Calculate points earned for a given order subtotal.
     */
    public function calculatePointsEarned($subtotal): int
    {
        if (!$this->isEnabled() || $this->earn_rate_rupees <= 0) {
            return 0;
        }

        return (int) floor($subtotal / $this->earn_rate_rupees) * $this->earn_rate_points;
    }

    /**
     * Calculate discount amount for given points.
     */
    public function calculateDiscountFromPoints($points, $subtotal): float
    {
        if (!$this->isEnabled() || $points < $this->min_redeem_points) {
            return 0;
        }

        $maxDiscount = $subtotal * ($this->max_discount_percent / 100);
        $discountFromPoints = $points * $this->value_per_point;
        
        return min($discountFromPoints, $maxDiscount);
    }

    /**
     * Calculate points required for a given discount amount.
     */
    public function calculatePointsForDiscount($discountAmount): int
    {
        if (!$this->isEnabled() || $this->value_per_point <= 0) {
            return 0;
        }

        return (int) ceil($discountAmount / $this->value_per_point);
    }
}

