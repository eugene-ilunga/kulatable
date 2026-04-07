<?php

namespace Modules\Whatsapp\Entities;

use Illuminate\Database\Eloquent\Model;

class WhatsAppNotificationPreference extends Model
{
    /** @var array<int, self|null> */
    private static array $enabledKitchenStaffByRestaurantId = [];

    protected $table = 'whatsapp_notification_preferences';

    protected $guarded = ['id'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            unset(self::$enabledKitchenStaffByRestaurantId[$model->restaurant_id]);
        });
    }

    public static function firstEnabledKitchenStaff(int $restaurantId): ?self
    {
        if (! array_key_exists($restaurantId, self::$enabledKitchenStaffByRestaurantId)) {
            self::$enabledKitchenStaffByRestaurantId[$restaurantId] = static::query()
                ->where('restaurant_id', $restaurantId)
                ->where('notification_type', 'kitchen_notification')
                ->where('recipient_type', 'staff')
                ->where('is_enabled', true)
                ->first();
        }

        return self::$enabledKitchenStaffByRestaurantId[$restaurantId];
    }

    /**
     * Get restaurant.
     */
    public function restaurant()
    {
        return $this->belongsTo(\App\Models\Restaurant::class);
    }
}

