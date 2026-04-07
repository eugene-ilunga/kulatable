<?php

namespace Modules\Sms\Http\Traits;

use Illuminate\Support\Facades\Config;

trait SmsSettingTrait
{

    public function setConfig()
    {
        $smsSettings = \Modules\Sms\Entities\SmsGlobalSetting::first();

        if ($smsSettings) {

            Config::set('vonage.api_key', $smsSettings->vonage_api_key);
            Config::set('vonage.api_secret', $smsSettings->vonage_api_secret);
            Config::set('vonage.sms_from', $smsSettings->vonage_from_number);

            Config::set('laravel-msg91.auth_key', $smsSettings->msg91_auth_key);
            Config::set('laravel-msg91.sender_id', $smsSettings->msg91_from);
        }
        (new \Illuminate\Notifications\VonageChannelServiceProvider(app()))->register();
    }
}
