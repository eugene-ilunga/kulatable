<?php

namespace App\Support;

class FreshpayNetworkDetector
{
    /**
     * Normalize phone number to local DRC format (0XXXXXXXXX).
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        // Support international dialing prefix: 00XXXXXXXX
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Country code to local format: 243XXXXXXXXX -> 0XXXXXXXXX
        if (str_starts_with($digits, '243')) {
            $digits = substr($digits, 3);
        }

        // Keep only the local significant part and force leading zero.
        // Ex: 972148867 / 0972148867 / 243972148867 / 00243972148867 => 0972148867
        $local = ltrim($digits, '0');

        if (strlen($local) < 9) {
            return '';
        }

        return '0' . substr($local, 0, 9);
    }

    /**
     * Detect FreshPay method from phone prefix.
     */
    public static function detectMethod(string $phone): ?string
    {
        $normalized = self::normalize($phone);

        if (strlen($normalized) < 3) {
            return null;
        }

        $prefix = substr($normalized, 0, 3);

        $mapping = config('services.freshpay.network_prefixes', [
            // DRC defaults - adapt as needed in config/services.php
            'airtel' => ['097', '098', '099', '090'],
            'orange' => ['089'],
            'mtn' => ['083'],
            'mpesa' => ['081', '082', '084', '085'],
            'afrimoney' => ['080'],
        ]);

        foreach ($mapping as $method => $prefixes) {
            if (in_array($prefix, $prefixes, true)) {
                return $method;
            }
        }

        return null;
    }
}
