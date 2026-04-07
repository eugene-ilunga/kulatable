<?php

if (! function_exists('sms_setting')) {

    function sms_setting()
    {
        if (! session()->has('sms_setting') || session('sms_setting')) {
            session(['sms_setting' => \Modules\Sms\Entities\SmsGlobalSetting::first()]);
        }

        return session('sms_setting');
    }
}
