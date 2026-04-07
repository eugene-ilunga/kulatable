<?php

namespace Modules\Sms\Livewire\SuperadminSetting;

use Livewire\Component;
use Modules\Sms\Entities\SmsGlobalSetting;
use Modules\Sms\Entities\SmsTemplate;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Country;
use Modules\Sms\Notifications\TestSms;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Entities\SmsUsageLog;
use Modules\Sms\Services\AndroidSmsGatewayClient;
use Illuminate\Validation\Rule;

class SmsSetting extends Component
{
    use WithFileUploads, LivewireAlert;

    // Vonage fields
    public $vonage_api_key = '';
    public $vonage_api_secret = '';
    public $vonage_from_number = '';
    public $vonage_status = false;

    // MSG91 fields
    public $msg91_auth_key = '';
    public $msg91_from = '';
    public $msg91_status = false;

    // Android SMS Gateway (https://github.com/capcom6/android-sms-gateway)
    public $android_sms_gateway_base_url = '';
    public $android_sms_gateway_username = '';
    public $android_sms_gateway_password = '';
    public $android_sms_gateway_status = false;

    /** superadmin = your SIM/device; restaurant = each outlet uses its own gateway app credentials */
    public $android_sms_gateway_owner = 'superadmin';

    // Phone verification status
    public $phone_verification_status = false;

    // SMS Template Flow IDs
    public $reservation_confirmed_flow_id = '';
    public $order_bill_sent_flow_id = '';
    public $send_otp_flow_id = '';
    public $send_verify_otp_flow_id = '';

    // License fields
    public $license_type = '';
    public $purchase_code = '';
    public $purchased_on = '';
    public $supported_until = '';
    public $notify_update = true;

    // Test message modal fields
    public $showTestMessageModal = false;
    public $phone = '';
    public $phoneCode = '';
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;
    public $vonageTotalCount = 0;
    public $msg91TotalCount = 0;
    public $androidSmsGatewayTotalCount = 0;

    protected $rules = [
        'vonage_api_key' => 'required_if:vonage_status,true',
        'vonage_api_secret' => 'required_if:vonage_status,true',
        'vonage_from_number' => 'required_if:vonage_status,true',
        'msg91_auth_key' => 'required_if:msg91_status,true',
        'msg91_from' => 'required_if:msg91_status,true',
        'reservation_confirmed_flow_id' => 'required_if:msg91_status,true',
        'order_bill_sent_flow_id' => 'required_if:msg91_status,true',
        'send_otp_flow_id' => 'required_if:msg91_status,true',
        'send_verify_otp_flow_id' => 'required_if:msg91_status,true',
        'android_sms_gateway_owner' => 'nullable|in:superadmin,restaurant',
        'android_sms_gateway_base_url' => 'nullable|url|max:512',
        'android_sms_gateway_username' => 'nullable|string|max:191',
        'android_sms_gateway_password' => 'nullable|string|max:191',
        'phone' => 'required|string|max:15',
        'phoneCode' => 'required|string',
    ];

    protected $messages = [
        'vonage_api_key.required_if' => 'API Key is required when Vonage is enabled.',
        'vonage_api_secret.required_if' => 'Auth Token is required when Vonage is enabled.',
        'vonage_from_number.required_if' => 'SMS From is required when Vonage is enabled.',
        'msg91_auth_key.required_if' => 'Auth Key is required when MSG91 is enabled.',
        'msg91_from.required_if' => 'Sender ID is required when MSG91 is enabled.',
        'phone.required' => 'Phone number is required.',
        'phoneCode.required' => 'Phone code is required.',
    ];

    public function mount()
    {
        $this->loadSettings();
        $this->initializePhoneCodes();
        $this->getGatewayTotalCounts();
    }
    
     /**
     * Get total SMS counts for each gateway across all records
     */
    public function getGatewayTotalCounts()
    {
        // Get total count for Vonage
        $this->vonageTotalCount = SmsUsageLog::where('gateway', 'vonage')->sum('count');
        $this->vonageTotalCount += global_setting()->total_vonage_count;
        
        // Get total count for MSG91
        $this->msg91TotalCount = SmsUsageLog::where('gateway', 'msg91')->sum('count');
        $this->msg91TotalCount += global_setting()->total_msg91_count;

        $this->androidSmsGatewayTotalCount = SmsUsageLog::where('gateway', 'android_sms_gateway')->sum('count');
        $legacyAndroid = (int) (global_setting()->total_android_sms_gateway_count ?? 0);
        $this->androidSmsGatewayTotalCount += $legacyAndroid;
    }

    public function initializePhoneCodes()
    {
        $this->allPhoneCodes = collect(Country::pluck('phonecode')->unique()->filter()->values());
        $this->filteredPhoneCodes = $this->allPhoneCodes;
        $this->phoneCode = $this->allPhoneCodes->first() ?? '1';
    }

    public function updatedPhoneCodeIsOpen($value)
    {
        if (!$value) {
            $this->reset(['phoneCodeSearch']);
            $this->updatedPhoneCodeSearch();
        }
    }

    public function updatedPhoneCodeSearch()
    {
        $this->filteredPhoneCodes = $this->allPhoneCodes->filter(function ($phonecode) {
            return str_contains($phonecode, $this->phoneCodeSearch);
        })->values();
    }

    public function selectPhoneCode($phonecode)
    {
        $this->phoneCode = $phonecode;
        $this->phoneCodeIsOpen = false;
        $this->phoneCodeSearch = '';
        $this->updatedPhoneCodeSearch();
    }

    public function loadSettings()
    {
        $settings = SmsGlobalSetting::first();
        
        if ($settings) {
            $this->vonage_api_key = $settings->vonage_api_key ?? '';
            $this->vonage_api_secret = $settings->vonage_api_secret ?? '';
            $this->vonage_from_number = $settings->vonage_from_number ?? '';
            $this->vonage_status = $settings->vonage_status ?? false;
            
            $this->msg91_auth_key = $settings->msg91_auth_key ?? '';
            $this->msg91_from = $settings->msg91_from ?? '';
            $this->msg91_status = $settings->msg91_status ?? false;

            $this->android_sms_gateway_base_url = $settings->android_sms_gateway_base_url ?? '';
            $this->android_sms_gateway_username = $settings->android_sms_gateway_username ?? '';
            $this->android_sms_gateway_password = $settings->android_sms_gateway_password ?? '';
            $this->android_sms_gateway_status = $settings->android_sms_gateway_status ?? false;
            $this->android_sms_gateway_owner = $settings->android_sms_gateway_owner ?? 'superadmin';
            
            $this->phone_verification_status = $settings->phone_verification_status ?? false;
        }

        // Load SMS template flow IDs
        $this->loadSmsTemplateFlowIds();
    }

    public function loadSmsTemplateFlowIds()
    {
        $templates = SmsTemplate::whereIn('type', ['reservation_confirmed', 'order_bill_sent', 'send_otp', 'send_verify_otp'])->get();
        
        foreach ($templates as $template) {
            switch ($template->type) {
                case 'reservation_confirmed':
                    $this->reservation_confirmed_flow_id = $template->flow_id ?? '';
                    break;
                case 'order_bill_sent':
                    $this->order_bill_sent_flow_id = $template->flow_id ?? '';
                    break;
                case 'send_otp':
                    $this->send_otp_flow_id = $template->flow_id ?? '';
                    break;
                case 'send_verify_otp':
                    $this->send_verify_otp_flow_id = $template->flow_id ?? '';
                    break;
            }
        }
    }

    public function updatedVonageStatus($value)
    {
        if ($value) {
            $this->msg91_status = false;
            $this->android_sms_gateway_status = false;
        }
    }

    public function updatedMsg91Status($value)
    {
        if ($value) {
            $this->vonage_status = false;
            $this->android_sms_gateway_status = false;
        }
    }

    public function updatedAndroidSmsGatewayStatus($value)
    {
        if ($value) {
            $this->vonage_status = false;
            $this->msg91_status = false;
        }
    }

    public function submitForm()
    {
        $this->validate([
            'vonage_api_key' => 'required_if:vonage_status,true',
            'vonage_api_secret' => 'required_if:vonage_status,true',
            'vonage_from_number' => 'required_if:vonage_status,true',
            'msg91_auth_key' => 'required_if:msg91_status,true',
            'msg91_from' => 'required_if:msg91_status,true',
            'reservation_confirmed_flow_id' => 'required_if:msg91_status,true',
            'order_bill_sent_flow_id' => 'required_if:msg91_status,true',
            'send_otp_flow_id' => 'required_if:msg91_status,true',
            'send_verify_otp_flow_id' => 'required_if:msg91_status,true',
            'android_sms_gateway_owner' => 'required_if:android_sms_gateway_status,true|in:superadmin,restaurant',
            'android_sms_gateway_base_url' => [
                'nullable',
                'url',
                'max:512',
                Rule::requiredIf(fn () => $this->android_sms_gateway_status && $this->android_sms_gateway_owner === 'superadmin'),
            ],
            'android_sms_gateway_username' => 'nullable|string|max:191',
            'android_sms_gateway_password' => 'nullable|string|max:191',
        ]);

        $settings = SmsGlobalSetting::first();
        
        if (!$settings) {
            $settings = new SmsGlobalSetting();
        }

        $settings->vonage_api_key = $this->vonage_api_key;
        $settings->vonage_api_secret = $this->vonage_api_secret;
        $settings->vonage_from_number = $this->vonage_from_number;
        $settings->vonage_status = $this->vonage_status;
        
        $settings->msg91_auth_key = $this->msg91_auth_key;
        $settings->msg91_from = $this->msg91_from;
        $settings->msg91_status = $this->msg91_status;

        $settings->android_sms_gateway_base_url = $this->android_sms_gateway_base_url ?: null;
        $settings->android_sms_gateway_username = $this->android_sms_gateway_username ?: null;
        $settings->android_sms_gateway_password = $this->android_sms_gateway_password ?: null;
        $settings->android_sms_gateway_status = $this->android_sms_gateway_status;
        $settings->android_sms_gateway_owner = $this->android_sms_gateway_status
            ? ($this->android_sms_gateway_owner === 'restaurant' ? 'restaurant' : 'superadmin')
            : 'superadmin';
        
        $settings->phone_verification_status = $this->phone_verification_status;
        
        $settings->save();

        // Save SMS template flow IDs
        $this->saveSmsTemplateFlowIds();

        // Clear SMS settings cache
        session()->forget('sms_setting');

        $this->alert('success', __('sms::modules.form.settingsSaved'));
    }

    public function saveSmsTemplateFlowIds()
    {
        $templates = [
            'reservation_confirmed' => $this->reservation_confirmed_flow_id,
            'order_bill_sent' => $this->order_bill_sent_flow_id,
            'send_otp' => $this->send_otp_flow_id,
            'send_verify_otp' => $this->send_verify_otp_flow_id,
        ];

        foreach ($templates as $type => $flowId) {
            SmsTemplate::updateOrCreate(
                ['type' => $type],
                ['flow_id' => $flowId]
            );
        }
    }

    public function sendTestMessage()
    {
        $this->validate([
            'phone' => 'required|string|max:15',
            'phoneCode' => 'required|string',
        ]);

        $canSendAndroidTest = $this->android_sms_gateway_status && $this->android_sms_gateway_owner === 'superadmin';

        if (! $this->vonage_status && ! $this->msg91_status && ! $canSendAndroidTest) {
            if ($this->android_sms_gateway_status && $this->android_sms_gateway_owner === 'restaurant') {
                $this->alert('error', __('sms::modules.messages.testSmsUseRestaurantAndroid'));
                return;
            }
            $this->alert('error', __('sms::modules.messages.enableGatewayBeforeTest'));
            return;
        }

        try {
            // Set configuration for Vonage
            if ($this->vonage_status) {
                Config::set('vonage.api_key', $this->vonage_api_key);
                Config::set('vonage.api_secret', $this->vonage_api_secret);
                Config::set('vonage.sms_from', $this->vonage_from_number);
            }

            // Format phone numbers
            $vonageNumber = str_replace('+', '', $this->phoneCode) . $this->phone;
            $msg91Number = '+' . ltrim((string) $this->phoneCode, '+') . $this->phone;

            // Send test SMS via Vonage
            if ($this->vonage_status) {
                (new \Illuminate\Notifications\VonageChannelServiceProvider(app()))->register();
                Notification::route('vonage', $vonageNumber)->notify(new TestSms());
            }

            // Send test SMS via MSG91 using direct API call (uses form credentials so you can test before saving)
            if ($this->msg91_status) {
                $msg91Credentials = new SmsGlobalSetting([
                    'msg91_auth_key' => $this->msg91_auth_key,
                    'msg91_from' => $this->msg91_from,
                ]);
                $msg91Raw = null;
                $this->sendSmsViaMsg91($msg91Number, __('sms::modules.messages.testSmsMessage'), $msg91Raw, null, $msg91Credentials);
            }

            if ($canSendAndroidTest) {
                $e164 = '+' . ltrim((string) $this->phoneCode, '+') . $this->phone;
                $androidCredentials = new SmsGlobalSetting([
                    'android_sms_gateway_status' => true,
                    'android_sms_gateway_base_url' => $this->android_sms_gateway_base_url,
                    'android_sms_gateway_username' => $this->android_sms_gateway_username,
                    'android_sms_gateway_password' => $this->android_sms_gateway_password,
                ]);
                AndroidSmsGatewayClient::send($e164, __('sms::modules.messages.testSmsMessage'), $androidCredentials);
            }

            $this->showTestMessageModal = false;
            $this->reset(['phone', 'phoneCode']);
            $this->initializePhoneCodes();

            $this->alert('success', __('sms::modules.messages.testSmsSent'));

        } catch (\Exception $e) {
            Log::error('Test SMS failed: ' . $e->getMessage());
            $this->alert('error', __('sms::modules.messages.testSmsFailed'));
        }
    }

    private function sendSmsViaMsg91($mobile, $message, &$rawResponse = null, $flowId = null, ?SmsGlobalSetting $credentials = null)
    {
        $setting = $credentials ?? SmsGlobalSetting::first();

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
                'sender' => $senderId,
                'mobiles' => $number,
                'VAR1' => $message // You can customize variables as needed
            ];
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'authkey' => $msg91Key,
                'Content-Type' => 'application/json'
            ])->post('https://api.msg91.com/api/v5/flow/', $payload);
            
        } else {
            $payload = [
                'sender' => $senderId,
                'route' => '4', // transactional
                'country' => $country,
                'sms' => [
                    [
                        'message' => $message,
                        'to' => [$number]
                    ]
                ]
            ];
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'authkey' => $msg91Key,
                'Content-Type' => 'application/json'
            ])->post('https://api.msg91.com/api/v2/sendsms', $payload);
        }

        $rawResponse = $response->body();

        if (! $response->successful()) {
            Log::error('MSG91 API Error (test SMS): ' . $rawResponse);
            throw new \Exception('MSG91 API error: ' . $rawResponse);
        }

        return true;
    }

    public function render()
    {
        return view('sms::livewire.superadmin-setting.sms-setting', [
            'phonecodes' => $this->filteredPhoneCodes ?? collect(),
        ]);
    }
}
