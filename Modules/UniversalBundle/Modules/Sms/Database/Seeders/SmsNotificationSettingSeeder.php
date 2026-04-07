<?php

namespace Modules\Sms\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Restaurant;
use Modules\Sms\Entities\SmsNotificationSetting;

class SmsNotificationSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Schema::hasTable('restaurants') && Schema::hasTable('sms_notification_settings')) {
            $restaurants = Restaurant::all();
            
            $restaurants->each(function ($restaurant) {
                $this->seedSmsNotificationSettings($restaurant);
            });
        }
    }

    /**
     * Seed SMS notification settings for a specific restaurant
     */
    public function seedSmsNotificationSettings($restaurant): void
    {
        // Check if restaurant already has SMS notification settings
        $existingSettings = SmsNotificationSetting::where('restaurant_id', $restaurant->id)->exists();
        
        if ($existingSettings) {
            // Restaurant already has settings, skip seeding
            return;
        }

        $smsNotificationTypes = [
            [
                'type' => 'reservation_confirmed',
                'send_sms' => 'no',
                'restaurant_id' => $restaurant->id
            ],
            [
                'type' => 'order_bill_sent',
                'send_sms' => 'no',
                'restaurant_id' => $restaurant->id
            ],
            [
                'type' => 'send_otp',
                'send_sms' => 'no',
                'restaurant_id' => $restaurant->id
            ]
        ];

        SmsNotificationSetting::insert($smsNotificationTypes);
    }
} 