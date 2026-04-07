<?php

namespace Modules\Sms\Channels;

use Illuminate\Notifications\Notification;

class Msg91Channel
{
    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toMsg91')) {
            return;
        }
        $message = $notification->toMsg91($notifiable);
    }
}