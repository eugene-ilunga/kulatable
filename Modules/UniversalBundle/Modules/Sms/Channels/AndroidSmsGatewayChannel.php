<?php

namespace Modules\Sms\Channels;

use Illuminate\Notifications\Notification;

class AndroidSmsGatewayChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toAndroidSmsGateway')) {
            return;
        }

        $notification->toAndroidSmsGateway($notifiable);
    }
}
