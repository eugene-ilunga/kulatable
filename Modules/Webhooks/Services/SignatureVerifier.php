<?php

namespace Modules\Webhooks\Services;

class SignatureVerifier
{
    public static function verify(string $secret, int $timestamp, string $payload, string $signature, int $tolerance = 300): bool
    {
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            return false;
        }

        // Basic replay protection window
        $now = now()->timestamp;
        return abs($now - $timestamp) <= $tolerance;
    }
}
