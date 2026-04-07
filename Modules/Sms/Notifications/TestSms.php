<?php

namespace Modules\Sms\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TestSms extends Notification
{
    use Queueable;

    protected $message;

    public function __construct()
    {
        $this->message = __('sms::modules.messages.testSmsMessage');
    }

    public function via(object $notifiable): array
    {
        $via = [];

        if (sms_setting()->vonage_status) {
            $via[] = 'vonage';
        }

        return $via;
    }

    public function toVonage($notifiable)
    {
        return (new VonageMessage)
                ->content($this->message)
                ->unicode();
    }

} 