<?php

namespace Modules\Sms\Livewire\Setting;

use App\Models\Country;
use App\Models\Restaurant;
use App\Models\GlobalSubscription;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Modules\Sms\Entities\RestaurantAndroidSmsSetting;
use Modules\Sms\Entities\SmsGlobalSetting;
use Modules\Sms\Entities\SmsNotificationSetting;
use Modules\Sms\Entities\SmsUsageLog;
use Modules\Sms\Notifications\TestSms;
use Modules\Sms\Services\AndroidSmsGatewayClient;

class SmsSetting extends Component
{
    use LivewireAlert;
    
    public $notificationSettings;
    public $sendEmail;
    public $activeGateway;
    public $activeGatewayName;
    public $smsCounts = [];

    public $packageSmsCount;
    public $usedSmsCount;
    public $remainingSmsCount;
    public $isSmsLimitReached;
    
    // Modal properties
    public $showViewModal = false;
    public $selectedNotificationType = '';
    public $notificationDetails = [];

    public $restaurant_android_sms_gateway_base_url = '';

    public $restaurant_android_sms_gateway_username = '';

    public $restaurant_android_sms_gateway_password = '';

    public $showTestMessageModal = false;

    public $phone = '';

    public $phoneCode = '';

    public $phoneCodeSearch = '';

    public $phoneCodeIsOpen = false;

    public $allPhoneCodes;

    public $filteredPhoneCodes;

    public function mount()
    {
        // Get SMS notification settings
        $this->notificationSettings = SmsNotificationSetting::get();
        $this->sendEmail = $this->notificationSettings->map(function($item) {
            return $item->send_sms === 'yes';
        })->toArray();
        
        // Get active SMS gateway
        $this->getActiveGateway();
        $this->getSmsCountInfo();
        $this->getSmsCountsForNotifications();
        $this->loadRestaurantAndroidGateway();
        $this->initializePhoneCodes();
    }

    public function initializePhoneCodes(): void
    {
        $this->allPhoneCodes = collect(Country::pluck('phonecode')->unique()->filter()->values());
        $this->filteredPhoneCodes = $this->allPhoneCodes;
        $this->phoneCode = $this->allPhoneCodes->first() ?? '1';
    }

    public function updatedPhoneCodeIsOpen($value): void
    {
        if (! $value) {
            $this->reset(['phoneCodeSearch']);
            $this->updatedPhoneCodeSearch();
        }
    }

    public function updatedPhoneCodeSearch(): void
    {
        $this->filteredPhoneCodes = $this->allPhoneCodes->filter(function ($phonecode) {
            return str_contains((string) $phonecode, (string) $this->phoneCodeSearch);
        })->values();
    }

    public function selectPhoneCode($phonecode): void
    {
        $this->phoneCode = $phonecode;
        $this->phoneCodeIsOpen = false;
        $this->phoneCodeSearch = '';
        $this->updatedPhoneCodeSearch();
    }

    /**
     * Whether the restaurant can open the test SMS dialog (gateway configured).
     */
    public function canTestRestaurantSms(): bool
    {
        $g = SmsGlobalSetting::first();
        if (! $g) {
            return false;
        }
        if ($g->vonage_status || $g->msg91_status) {
            return true;
        }
        if (! $g->android_sms_gateway_status) {
            return false;
        }
        if (($g->android_sms_gateway_owner ?? 'superadmin') === 'superadmin') {
            return trim((string) ($g->android_sms_gateway_base_url ?? '')) !== '';
        }

        $url = trim((string) $this->restaurant_android_sms_gateway_base_url);
        if ($url !== '') {
            return true;
        }
        $restaurantId = auth()->user()?->restaurant_id;
        if (! $restaurantId) {
            return false;
        }
        $row = RestaurantAndroidSmsSetting::where('restaurant_id', $restaurantId)->first();

        return $row && trim((string) ($row->base_url ?? '')) !== '';
    }

    public function sendTestMessage(): void
    {
        $this->validate([
            'phone' => 'required|string|max:15',
            'phoneCode' => 'required|string',
        ]);

        $global = SmsGlobalSetting::first();
        if (! $global || (! $global->vonage_status && ! $global->msg91_status && ! $global->android_sms_gateway_status)) {
            $this->alert('error', __('sms::modules.messages.enableGatewayBeforeTest'));

            return;
        }

        try {
            if ($global->vonage_status) {
                Config::set('vonage.api_key', $global->vonage_api_key);
                Config::set('vonage.api_secret', $global->vonage_api_secret);
                Config::set('vonage.sms_from', $global->vonage_from_number);
                $vonageNumber = str_replace('+', '', $this->phoneCode) . $this->phone;
                (new \Illuminate\Notifications\VonageChannelServiceProvider(app()))->register();
                Notification::route('vonage', $vonageNumber)->notify(new TestSms());
            } elseif ($global->msg91_status) {
                $msg91Number = '+' . ltrim((string) $this->phoneCode, '+') . $this->phone;
                $msg91Raw = null;
                $this->sendSmsViaMsg91($msg91Number, __('sms::modules.messages.testSmsMessage'), $msg91Raw, null, $global);
            } elseif ($global->android_sms_gateway_status) {
                $e164 = '+' . ltrim((string) $this->phoneCode, '+') . $this->phone;
                if (($global->android_sms_gateway_owner ?? 'superadmin') === 'superadmin') {
                    AndroidSmsGatewayClient::send($e164, __('sms::modules.messages.testSmsMessage'), null, null);
                } else {
                    $androidCredentials = $this->buildRestaurantAndroidTestCredentials();
                    if (! $androidCredentials) {
                        $this->alert('error', __('sms::modules.messages.testSmsRestaurantAndroidConfigure'));

                        return;
                    }
                    AndroidSmsGatewayClient::send($e164, __('sms::modules.messages.testSmsMessage'), $androidCredentials);
                }
            }

            $this->showTestMessageModal = false;
            $this->reset(['phone', 'phoneCode']);
            $this->initializePhoneCodes();

            $this->alert('success', __('sms::modules.messages.testSmsSent'));
        } catch (\Exception $e) {
            Log::error('Restaurant test SMS failed: ' . $e->getMessage());
            $this->alert('error', __('sms::modules.messages.testSmsFailed'));
        }
    }

    protected function buildRestaurantAndroidTestCredentials(): ?SmsGlobalSetting
    {
        $restaurantId = auth()->user()?->restaurant_id;
        if (! $restaurantId) {
            return null;
        }

        $row = RestaurantAndroidSmsSetting::where('restaurant_id', $restaurantId)->first();
        $url = trim((string) $this->restaurant_android_sms_gateway_base_url);
        if ($url === '' && $row) {
            $url = trim((string) ($row->base_url ?? ''));
        }
        if ($url === '') {
            return null;
        }

        $username = $this->restaurant_android_sms_gateway_username;
        if ($username === null || $username === '') {
            $username = $row->username ?? '';
        }

        $password = $this->restaurant_android_sms_gateway_password;
        if (! filled($password) && $row) {
            $password = $row->password ?? '';
        }

        return new SmsGlobalSetting([
            'android_sms_gateway_status' => true,
            'android_sms_gateway_base_url' => $url,
            'android_sms_gateway_username' => $username,
            'android_sms_gateway_password' => $password,
        ]);
    }

    private function sendSmsViaMsg91($mobile, $message, &$rawResponse = null, $flowId = null, ?SmsGlobalSetting $credentials = null): bool
    {
        $setting = $credentials ?? SmsGlobalSetting::first();

        $msg91Key = $setting->msg91_auth_key;
        $senderId = $setting->msg91_from;

        $country = '91';
        $number = $mobile;
        if (preg_match('/^\+(\d{1,3})(\d{6,15})$/', $mobile, $matches)) {
            $country = $matches[1];
            $number = $matches[2];
        }

        if ($flowId) {
            $payload = [
                'flow_id' => $flowId,
                'sender' => $senderId,
                'mobiles' => $number,
                'VAR1' => $message,
            ];
            $response = Http::withHeaders([
                'authkey' => $msg91Key,
                'Content-Type' => 'application/json',
            ])->post('https://api.msg91.com/api/v5/flow/', $payload);
        } else {
            $payload = [
                'sender' => $senderId,
                'route' => '4',
                'country' => $country,
                'sms' => [
                    [
                        'message' => $message,
                        'to' => [$number],
                    ],
                ],
            ];
            $response = Http::withHeaders([
                'authkey' => $msg91Key,
                'Content-Type' => 'application/json',
            ])->post('https://api.msg91.com/api/v2/sendsms', $payload);
        }

        $rawResponse = $response->body();

        if (! $response->successful()) {
            Log::error('MSG91 API Error (restaurant test SMS): ' . $rawResponse);
            throw new \Exception('MSG91 API error: ' . $rawResponse);
        }

        return true;
    }

    protected function loadRestaurantAndroidGateway(): void
    {
        $restaurantId = auth()->user()?->restaurant_id;
        if (! $restaurantId) {
            return;
        }

        $row = RestaurantAndroidSmsSetting::where('restaurant_id', $restaurantId)->first();
        if ($row) {
            $this->restaurant_android_sms_gateway_base_url = $row->base_url ?? '';
            $this->restaurant_android_sms_gateway_username = $row->username ?? '';
        }
    }

    protected function restaurantUsesOwnAndroidGateway(): bool
    {
        $g = SmsGlobalSetting::first();

        return $g
            && $g->android_sms_gateway_status
            && ($g->android_sms_gateway_owner ?? 'superadmin') === 'restaurant';
    }

    protected function saveRestaurantAndroidGateway(): void
    {
        $restaurantId = auth()->user()?->restaurant_id;
        if (! $restaurantId) {
            return;
        }

        $data = [
            'base_url' => $this->restaurant_android_sms_gateway_base_url ?: null,
            'username' => $this->restaurant_android_sms_gateway_username ?: null,
        ];
        if (filled($this->restaurant_android_sms_gateway_password)) {
            $data['password'] = $this->restaurant_android_sms_gateway_password;
        }

        RestaurantAndroidSmsSetting::updateOrCreate(
            ['restaurant_id' => $restaurantId],
            $data
        );

        $this->restaurant_android_sms_gateway_password = '';
    }

    public function submitForm()
    {
        if ($this->restaurantUsesOwnAndroidGateway()) {
            $this->validate([
                'restaurant_android_sms_gateway_base_url' => 'nullable|url|max:512',
                'restaurant_android_sms_gateway_username' => 'nullable|string|max:191',
                'restaurant_android_sms_gateway_password' => 'nullable|string|max:191',
            ]);
        }

        foreach ($this->notificationSettings as $key => $notification) {
            $notification->update(['send_sms' => $this->sendEmail[$key] ? 'yes' : 'no']);
        }

        if ($this->restaurantUsesOwnAndroidGateway()) {
            $this->saveRestaurantAndroidGateway();
        }

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function openViewModal($notificationType)
    {
        $this->selectedNotificationType = $notificationType;
        $this->notificationDetails = $this->getNotificationDetails($notificationType);
        $this->showViewModal = true;
    }

    private function getNotificationDetails($notificationType)
    {
        $notification = $this->notificationSettings->where('type', $notificationType)->first();
        
        if (!$notification) {
            return [];
        }
        
        $details = [
            'type' => $notificationType,
            'title' => __('sms::modules.notifications.' . $notificationType),
            'description' => __('sms::modules.notifications.' . $notificationType . '_info'),
            'is_enabled' => $notification->send_sms === 'yes',
            'sms_message' => '',
            'gateway_info' => []
        ];

        // Get SMS message based on notification type and gateway
        if (sms_setting()->vonage_status) {
            $details['sms_message'] = $this->getVonageSmsMessage($notificationType);
        } elseif (sms_setting()->msg91_status) {
            $details['sms_message'] = $this->getMsg91SmsMessage($notificationType);
        } elseif (sms_setting()->android_sms_gateway_status) {
            $details['sms_message'] = $this->getAndroidSmsGatewayMessage($notificationType);
        }

        return $details;
    }

    private function getVonageSmsMessage($notificationType)
    {
        $vonageMessages = [
            'reservation_confirmed' => __('sms::modules.messages.reservation_confirmed'),
            'order_bill_sent' => __('sms::modules.messages.order_bill_sent'),
            'send_otp' => __('sms::modules.messages.send_otp')
        ];

        return $vonageMessages[$notificationType] ?? 'No message template available for this notification type.';
    }

    private function getMsg91SmsMessage($notificationType)
    {
        $msg91Messages = [
            'reservation_confirmed' => 'Hello ##customer_name##, your reservation is confirmed at ##restaurant_name##. Reservation Date & Time: ##reservation_date_time##. Thank you!',
            'order_bill_sent' => 'Hello ##customer_name##, Thank you for dining with us at ##restaurant_name##! It was our pleasure to serve you!. Order: ##order_number##. Total: ##order_total##. Thank you!',
            'send_otp' => '##var## is the OTP to access your account. Do not share it with anyone.'
        ];

        return $msg91Messages[$notificationType] ?? 'No message template available for this notification type.';
    }

    private function getAndroidSmsGatewayMessage($notificationType)
    {
        return $this->getVonageSmsMessage($notificationType);
    }

    public function getActiveGateway()
    {
        $globalSettings = \Modules\Sms\Entities\SmsGlobalSetting::first();
        
        if ($globalSettings) {
            if ($globalSettings->vonage_status) {
                $this->activeGateway = 'vonage';
                $this->activeGatewayName = 'Vonage';
            } elseif ($globalSettings->msg91_status) {
                $this->activeGateway = 'msg91';
                $this->activeGatewayName = 'MSG91';
            } elseif ($globalSettings->android_sms_gateway_status) {
                $this->activeGateway = 'android_sms_gateway';
                $owner = $globalSettings->android_sms_gateway_owner ?? 'superadmin';
                $this->activeGatewayName = $owner === 'restaurant'
                    ? __('sms::modules.form.androidSmsGatewayRestaurantSim')
                    : __('sms::modules.form.androidSmsGateway');
            } else {
                $this->activeGateway = null;
                $this->activeGatewayName = null;
            }
        }
    }

    public function getSmsCountInfo()
    {
        $restaurant = Restaurant::find(auth()->user()->restaurant_id);
        
        if ($restaurant) {
            // Use restaurant's total_sms instead of package sms_count
            $this->packageSmsCount = $restaurant->total_sms ?? 0;
            $this->usedSmsCount = $restaurant->count_sms ?? 0;
            
            // Check if SMS count is unlimited (-1)
            if ($this->packageSmsCount == -1) {
                $this->remainingSmsCount = -1; // -1 indicates unlimited
                $this->isSmsLimitReached = false;
            } else {
                $this->remainingSmsCount = max(0, $this->packageSmsCount - $this->usedSmsCount);
                $this->isSmsLimitReached = $this->usedSmsCount >= $this->packageSmsCount;
            }
        } else {
            $this->packageSmsCount = 0;
            $this->usedSmsCount = 0;
            $this->remainingSmsCount = 0;
            $this->isSmsLimitReached = false;
        }
    }

    /**
     * Get active subscription for the restaurant
     */
    private function getActiveSubscription($restaurantId)
    {
        return GlobalSubscription::where('restaurant_id', $restaurantId)
            ->where('subscription_status', 'active')
            ->orderBy('subscribed_on_date', 'desc')
            ->first();
    }

    /**
     * Get SMS counts for each notification type based on active subscription
     * Only count records after subscription start date and for current package
     */
    public function getSmsCountsForNotifications()
    {
        $restaurant = Restaurant::find(auth()->user()->restaurant_id);
        
        if (!$restaurant) {
            $this->smsCounts = [];
            return;
        }

        $restaurantId = $restaurant->id;
        $currentPackageId = $restaurant->package_id;
        
        // Get active subscription
        $activeSubscription = $this->getActiveSubscription($restaurantId);
        
        if (!$activeSubscription) {
            $this->smsCounts = [];
            return;
        }

        $subscriptionStartDate = $activeSubscription->subscribed_on_date;
        
        // Initialize counts for all notification types
        $this->smsCounts = [];
        
        foreach ($this->notificationSettings as $notification) {
            $type = $notification->type;
            $count = 0;
            
            // Get count based on active gateway, current package, and after subscription start date
            if ($this->activeGateway === 'vonage') {
                $count = SmsUsageLog::where('restaurant_id', $restaurantId)
                    ->where('gateway', 'vonage')
                    ->where('type', $type)
                    ->where('package_id', $currentPackageId)
                    ->where('date', '>=', $subscriptionStartDate)
                    ->sum('count');
            } elseif ($this->activeGateway === 'msg91') {
                $count = SmsUsageLog::where('restaurant_id', $restaurantId)
                    ->where('gateway', 'msg91')
                    ->where('type', $type)
                    ->where('package_id', $currentPackageId)
                    ->where('date', '>=', $subscriptionStartDate)
                    ->sum('count');
            } elseif ($this->activeGateway === 'android_sms_gateway') {
                $count = SmsUsageLog::where('restaurant_id', $restaurantId)
                    ->where('gateway', 'android_sms_gateway')
                    ->where('type', $type)
                    ->where('package_id', $currentPackageId)
                    ->where('date', '>=', $subscriptionStartDate)
                    ->sum('count');
            }
            
            $this->smsCounts[$type] = $count;
        }
    }

    /**
     * Get SMS count for a specific notification type
     */
    public function getSmsCountForType($type)
    {
        return $this->smsCounts[$type] ?? 0;
    }

    /**
     * Refresh SMS counts (useful for real-time updates)
     */
    public function refreshSmsCounts()
    {
        $this->getSmsCountsForNotifications();
    }

    /**
     * Get total SMS count for current subscription period
     */
    public function getTotalSmsCountForCurrentPackage()
    {
        $restaurant = Restaurant::find(auth()->user()->restaurant_id);
        
        if (!$restaurant) {
            return 0;
        }

        $restaurantId = $restaurant->id;
        $currentPackageId = $restaurant->package_id;
        
        // Get active subscription
        $activeSubscription = $this->getActiveSubscription($restaurantId);
        
        if (!$activeSubscription) {
            return 0;
        }

        $subscriptionStartDate = $activeSubscription->subscribed_on_date;
        
        return SmsUsageLog::where('restaurant_id', $restaurantId)
            ->where('package_id', $currentPackageId)
            ->where('date', '>=', $subscriptionStartDate)
            ->sum('count');
    }

    /**
     * Get SMS count by gateway for current subscription period
     */
    public function getSmsCountByGatewayForCurrentPackage($gateway)
    {
        $restaurant = Restaurant::find(auth()->user()->restaurant_id);
        
        if (!$restaurant) {
            return 0;
        }

        $restaurantId = $restaurant->id;
        $currentPackageId = $restaurant->package_id;
        
        // Get active subscription
        $activeSubscription = $this->getActiveSubscription($restaurantId);
        
        if (!$activeSubscription) {
            return 0;
        }

        $subscriptionStartDate = $activeSubscription->subscribed_on_date;
        
        return SmsUsageLog::where('restaurant_id', $restaurantId)
            ->where('gateway', $gateway)
            ->where('package_id', $currentPackageId)
            ->where('date', '>=', $subscriptionStartDate)
            ->sum('count');
    }

    public function render()
    {
        return view('sms::livewire.setting.sms-setting', [
            'restaurantAndroidGatewayEnabled' => $this->restaurantUsesOwnAndroidGateway(),
            'canTestRestaurantSms' => $this->canTestRestaurantSms(),
            'phonecodes' => $this->filteredPhoneCodes ?? collect(),
        ]);
    }
}
