<?php

namespace Modules\Sms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Entities\RestaurantAndroidSmsSetting;
use Modules\Sms\Entities\SmsGlobalSetting;

class AndroidSmsGatewayClient
{
    /**
     * Whether Android gateway can send for the given context.
     * Pass null restaurant id for superadmin-only flows (e.g. verify OTP with superadmin SIM).
     */
    public static function canSendForRestaurant(?int $restaurantId = null): bool
    {
        return self::resolveCredentials($restaurantId) !== null;
    }

    /**
     * Resolve credentials: superadmin SIM uses global settings; restaurant SIM uses per-restaurant row.
     */
    public static function resolveCredentials(?int $restaurantId = null): ?SmsGlobalSetting
    {
        $global = SmsGlobalSetting::first();

        if (! $global || ! $global->android_sms_gateway_status) {
            return null;
        }

        $owner = $global->android_sms_gateway_owner ?? 'superadmin';

        if ($owner === 'superadmin') {
            $url = trim((string) $global->android_sms_gateway_base_url);

            return $url !== '' ? $global : null;
        }

        if ($restaurantId === null) {
            return null;
        }

        $row = RestaurantAndroidSmsSetting::where('restaurant_id', $restaurantId)->first();
        if (! $row || trim((string) $row->base_url) === '') {
            return null;
        }

        return self::syntheticFromRestaurantRow($row);
    }

    /**
     * @see https://github.com/capcom6/android-sms-gateway
     * @see https://docs.sms-gate.app
     *
     * @param  SmsGlobalSetting|null  $settings  When set, uses this payload only (e.g. unsaved superadmin form).
     * @param  int|null  $restaurantId  When $settings is null, resolves via owner (superadmin vs restaurant).
     */
    public static function send(string $e164PhoneWithPlus, string $text, ?SmsGlobalSetting $settings = null, ?int $restaurantId = null): void
    {
        if ($settings !== null) {
            self::sendWithResolved($e164PhoneWithPlus, $text, $settings);

            return;
        }

        $resolved = self::resolveCredentials($restaurantId);
        if (! $resolved) {
            throw new \RuntimeException('Android SMS Gateway is not configured for this context.');
        }

        self::sendWithResolved($e164PhoneWithPlus, $text, $resolved);
    }

    private static function syntheticFromRestaurantRow(RestaurantAndroidSmsSetting $row): SmsGlobalSetting
    {
        $s = new SmsGlobalSetting;
        $s->android_sms_gateway_status = true;
        $s->android_sms_gateway_base_url = $row->base_url;
        $s->android_sms_gateway_username = $row->username;
        $s->android_sms_gateway_password = $row->password;

        return $s;
    }

    private static function sendWithResolved(string $e164PhoneWithPlus, string $text, SmsGlobalSetting $settings): void
    {
        if (! $settings->android_sms_gateway_status) {
            throw new \RuntimeException('Android SMS Gateway is not enabled.');
        }

        $url = trim((string) $settings->android_sms_gateway_base_url);
        if ($url === '') {
            throw new \RuntimeException('Android SMS Gateway message URL is required.');
        }

        $response = Http::withBasicAuth(
            (string) ($settings->android_sms_gateway_username ?? ''),
            (string) ($settings->android_sms_gateway_password ?? '')
        )
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->post($url, [
                'textMessage' => ['text' => $text],
                'phoneNumbers' => [$e164PhoneWithPlus],
            ]);

        if (! $response->successful()) {
            Log::error('Android SMS Gateway API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Android SMS Gateway error: ' . $response->body());
        }
    }
}
