<?php

namespace Modules\Sms\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\VonageMessage;
use RobinCSamuel\LaravelMsg91\Facades\LaravelMsg91;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\Sms\Entities\SmsTemplate;
use Modules\Sms\Entities\SmsGlobalSetting;
use Modules\Sms\Entities\SmsNotificationSetting;
use Modules\Sms\Entities\SmsUsageLog;
use Modules\Sms\Channels\AndroidSmsGatewayChannel;
use Modules\Sms\Services\AndroidSmsGatewayClient;

class SendCustomerVerifyOtp extends Notification implements ShouldQueue
{
    use Queueable;

    protected $otp;
    protected $message;
    protected $restaurant;
    protected $smsSetting;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $this->restaurant = $notifiable->restaurant;
        $this->smsSetting = SmsNotificationSetting::where('type', 'send_otp')->where('restaurant_id', $this->restaurant->id)->first();
        if (!in_array('Sms', restaurant_modules($this->restaurant))) {
            return [];
        }

        if ($this->smsSetting && $this->smsSetting->send_sms != 'yes') {
            return [];
        }

        // Check SMS limit if restaurant exists
        if ($this->restaurant) {
            if ($this->restaurant->total_sms == 0) {
                return [];
            }

            if ($this->restaurant->total_sms > 0 && $this->restaurant->count_sms >= $this->restaurant->total_sms) {
                return [];
            }
        }

        $this->message = $this->otp . ' ' . __('sms::modules.email.isTheOtpToAccessYourAccount') . ' ' . __('sms::modules.email.doNotShareItWithAnyone');

        $via = [];

        if (! is_null($notifiable->phone) && ! is_null($notifiable->phone_code)) {
            if (sms_setting()->vonage_status) {
                array_push($via, 'vonage');
            }

            if (sms_setting()->msg91_status) {
                $via[] = \Modules\Sms\Channels\Msg91Channel::class;
            }

            if (sms_setting()->android_sms_gateway_status && AndroidSmsGatewayClient::canSendForRestaurant($this->restaurant->id)) {
                $via[] = AndroidSmsGatewayChannel::class;
            }
        }

        return $via;
    }

    //phpcs:ignore
    public function toVonage($notifiable)
    {
        if (sms_setting()->vonage_status) {
            $message = (new VonageMessage)->content($this->message)->unicode();

            try {
                $this->incrementSmsCount();

                SmsUsageLog::logSmsUsage(
                    $this->restaurant->id,
                    $notifiable->branch_id ?? null,
                    'vonage',
                    'send_otp',
                    $this->restaurant->package_id
                );
            } catch (\Exception $e) {
                Log::error('Vonage SMS send failed: ' . $e->getMessage());
                throw $e;
            }

            return $message;
        }
    }

    public function toMsg91($notifiable)
    {
        if (sms_setting()->msg91_status) {
            try {
                // Get the flow ID for send OTP
                $smsTemplate = SmsTemplate::where('type', 'send_otp')->first();
                $flowId = $smsTemplate ? $smsTemplate->flow_id : null;

                // Format phone number
                $phoneNumber = $notifiable->routeNotificationForMsg91($this);

                // Send SMS using the same logic as in SmsSetting
                $success = $this->sendSmsViaMsg91($phoneNumber, $notifiable, $flowId);

                // Increment SMS count after successful send
                if ($success) {
                    $this->incrementSmsCount();

                    SmsUsageLog::logSmsUsage(
                        $this->restaurant->id,
                        $notifiable->branch_id ?? null,
                        'msg91',
                        'send_otp',
                        $this->restaurant->package_id
                    );
                }
            } catch (\Exception $e) {
                Log::error('MSG91 SMS send failed: ' . $e->getMessage());
                throw $e; // Re-throw to be caught by the calling code
            }
        }
    }

    public function toAndroidSmsGateway($notifiable): void
    {
        if (! sms_setting()->android_sms_gateway_status) {
            return;
        }

        try {
            $phone = $notifiable->routeNotificationForAndroidSmsGateway($this);
            if (! $phone) {
                return;
            }

            AndroidSmsGatewayClient::send($phone, $this->message, null, $this->restaurant->id);

            $this->incrementSmsCount();

            SmsUsageLog::logSmsUsage(
                $this->restaurant->id,
                $notifiable->branch_id ?? null,
                'android_sms_gateway',
                'send_otp',
                $this->restaurant->package_id
            );
        } catch (\Exception $e) {
            Log::error('Android SMS Gateway send failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send SMS via MSG91 using Flow API or Transactional API
     */
    private function sendSmsViaMsg91($mobile, $notifiable, $flowId = null)
    {
        $setting = SmsGlobalSetting::first();

        $msg91Key = $setting->msg91_auth_key;
        $senderId = $setting->msg91_from;

        // Extract country code and number
        $country = '91'; // default
        $number = $mobile;
        if (preg_match('/^\+(\d{1,3})(\d{6,15})$/', $mobile, $matches)) {
            $country = $matches[1];
            $number = $matches[2];
        }

        // If flow ID is provided, use Flow API, otherwise use transactional API
        if ($flowId && $number && $msg91Key && $senderId) {
            $response = Http::withHeaders([
                'authkey' => $msg91Key,
                'Content-Type' => 'application/json'
            ])->post('https://api.msg91.com/api/v5/flow/', [
                'flow_id' => $flowId,
                'recipients' => [
                    [
                        'mobiles' => $number,
                        'var' => $this->otp
                    ]
                ]
            ]);
        } else {

            $payload = [
                'sender' => $senderId,
                'route' => '4', // transactional
                'country' => $country,
                'sms' => [
                    [
                        'message' => $this->message,
                        'to' => [$number]
                    ]
                ]
            ];
            $response = Http::withHeaders([
                'authkey' => $msg91Key,
                'Content-Type' => 'application/json'
            ])->post('https://api.msg91.com/api/v2/sendsms', $payload);
        }
        $rawResponse = $response->body();

        if (!$response->successful()) {
            Log::error('MSG91 API Error: ' . $rawResponse);
            throw new \Exception('MSG91 API Error: ' . $rawResponse);
        }

        return $response->successful();
    }

    /**
     * Increment SMS count for the restaurant
     */
    private function incrementSmsCount()
    {
        if ($this->restaurant) {
            $this->restaurant->increment('count_sms');
        }
    }
}
