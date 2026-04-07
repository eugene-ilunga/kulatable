<?php

namespace Modules\Sms\Listeners;

use App\Events\NewRestaurantCreatedEvent;
use Modules\Sms\Entities\SmsNotificationSetting;

class CreateSmsOnRestaurantCreatedListener
{
    public function handle(NewRestaurantCreatedEvent $event): void
    {
        $restaurant = $event->restaurant;

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
