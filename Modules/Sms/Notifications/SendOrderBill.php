<?php

namespace Modules\Sms\Notifications;

use App\Models\NotificationSetting;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Sms\Entities\SmsNotificationSetting;
use Modules\Sms\Entities\SmsUsageLog;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\Sms\Entities\SmsTemplate;
use Modules\Sms\Entities\SmsGlobalSetting;
use Modules\Sms\Channels\AndroidSmsGatewayChannel;
use Modules\Sms\Services\AndroidSmsGatewayClient;

class SendOrderBill extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $settings;
    protected $smsSetting;
    protected $message;
    protected $restaurant;

    /**
     * Create a new notification instance.
     *
     * @param $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->settings = $order->branch->restaurant;
        $this->smsSetting = SmsNotificationSetting::where('type', 'order_bill_sent')->where('restaurant_id', $order->branch->restaurant_id)->first();
        $this->restaurant = $order->branch->restaurant;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
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

        $this->message = __('app.hello') . ' ' . $notifiable->name . ', ' .  __('sms::modules.email.thankYouDining', ['restaurant_name' => $this->settings->name]) . "\n" .
            __('sms::modules.email.orderNumber', ['order_number' => $this->order->show_formatted_order_number]) . "\n" .
            __('sms::modules.email.totalAmount', ['total' => currency_format($this->order->total)]);

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
                    $this->order->branch_id,
                    'vonage',
                    'order_bill_sent',
                    $this->restaurant->package_id
                );
            } catch (\Exception $e) {
                Log::error('Vonage SMS send failed: ' . $e->getMessage());
                throw $e;
            }

            return $message;
        }
    }

    //phpcs:ignore
    public function toMsg91($notifiable)
    {
        if (sms_setting()->msg91_status) {
            try {
                // Get the flow ID for order bill sent
                $smsTemplate = SmsTemplate::where('type', 'order_bill_sent')->first();
                $flowId = $smsTemplate ? $smsTemplate->flow_id : null;

                // Format phone number
                $phoneNumber = $notifiable->routeNotificationForMsg91($this);

                // Send SMS using the same logic as in SmsSetting
                $success =  $this->sendSmsViaMsg91($phoneNumber, $notifiable, $flowId);
                if ($success) {
                    $this->incrementSmsCount();

                    // Log SMS usage
                    SmsUsageLog::logSmsUsage(
                        $this->restaurant->id,
                        $this->order->branch_id,
                        'msg91',
                        'order_bill_sent',
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
                $this->order->branch_id,
                'android_sms_gateway',
                'order_bill_sent',
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
        if ($flowId) {
            $payload = [
                'flow_id' => $flowId,
                'recipients' => [
                    [
                        'mobiles' => $number,
                        'order_number' => $this->order->show_formatted_order_number,
                        'customer_name' => $notifiable->name,
                        'restaurant_name' => $this->settings->name,
                        'order_total' => $this->order->total ?? '0'
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'authkey' => $msg91Key,
                'Content-Type' => 'application/json'
            ])->post('https://api.msg91.com/api/v5/flow/', $payload);
        } else {
            // Fallback message for transactional API
            $fallbackMessage = "Hello " . $notifiable->name . ",\n\nYour order " . $this->order->show_formatted_order_number . " bill is ready at " . $this->settings->name . "\n\nTotal: " . ($this->order->total ?? '0') . "\n\nThanks";

            $payload = [
                'sender' => $senderId,
                'route' => '4', // transactional
                'country' => $country,
                'sms' => [
                    [
                        'message' => $fallbackMessage,
                        'to' => [$number]
                    ]
                ]
            ];

            Log::info('Using MSG91 Transactional API (fallback)', ['payload' => $payload]);

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
