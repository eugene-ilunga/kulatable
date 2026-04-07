<?php

namespace App\Services\Freshpay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FreshpayCallbackService
{
    public function validateIp(Request $request): bool
    {
        $allowedIps = config('services.freshpay.allowed_ips', []);

        if (is_string($allowedIps)) {
            $allowedIps = array_filter(array_map('trim', explode(',', $allowedIps)));
        }

        if (empty($allowedIps)) {
            return true;
        }

        return in_array($request->ip(), $allowedIps, true);
    }

    public function resolvePayload(Request $request, object $paymentGateway, array $logContext = []): array
    {
        $encryptedData = (string) $request->input('data', '');
        $signature = (string) $request->header('X-Signature', '');
        $rawPayload = $request->all();

        if ($encryptedData !== '' || $signature !== '') {
            if ($encryptedData === '' || $signature === '') {
                Log::warning('FreshPay callback rejected: invalid payload', array_merge($logContext, [
                    'has_data' => $encryptedData !== '',
                    'has_signature' => $signature !== '',
                ]));

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid callback payload'], 400)];
            }

            $hmacKey = $paymentGateway->freshpay_callback_hmac_key ?: $paymentGateway->freshpay_merchant_secret;
            $expectedSignature = hash_hmac('sha256', $encryptedData, (string) $hmacKey);

            if (!hash_equals(strtolower($expectedSignature), strtolower($signature))) {
                Log::warning('FreshPay callback rejected: invalid signature', array_merge($logContext, [
                    'ip' => $request->ip(),
                    'received_signature_prefix' => $this->maskSignature($signature),
                    'expected_signature_prefix' => $this->maskSignature($expectedSignature),
                ]));

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid signature'], 401)];
            }

            Log::info('FreshPay callback signature validated', array_merge($logContext, [
                'ip' => $request->ip(),
                'data_length' => strlen($encryptedData),
            ]));

            $decryptedPayload = $this->decryptPayload(
                $encryptedData,
                (string) ($paymentGateway->freshpay_callback_secret_key ?: $paymentGateway->freshpay_merchant_secret)
            );

            if ($decryptedPayload === null || !is_array($decryptedPayload)) {
                Log::warning('FreshPay callback rejected: invalid encryption', array_merge($logContext, [
                    'ip' => $request->ip(),
                ]));

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid encryption'], 400)];
            }

            Log::info('FreshPay callback decrypted payload', array_merge($logContext, [
                'payload' => $decryptedPayload,
            ]));

            return ['payload' => $decryptedPayload, 'response' => null];
        }

        if (empty($rawPayload)) {
            Log::warning('FreshPay callback rejected: invalid payload', array_merge($logContext, [
                'has_data' => false,
                'has_signature' => false,
            ]));

            return ['payload' => null, 'response' => response()->json(['error' => 'Invalid callback payload'], 400)];
        }

        Log::info('FreshPay callback accepted in plain JSON mode', array_merge($logContext, [
            'payload' => $rawPayload,
        ]));

        return ['payload' => $rawPayload, 'response' => null];
    }

    public function buildLogContext(Request $request, ?string $hash = null): array
    {
        return [
            'hash' => $hash,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->userAgent(),
            'has_signature' => $request->hasHeader('X-Signature'),
            'signature_prefix' => $this->maskSignature((string) $request->header('X-Signature', '')),
            'body' => $request->all(),
            'raw_body' => $request->getContent(),
        ];
    }

    private function maskSignature(string $signature): ?string
    {
        if ($signature === '') {
            return null;
        }

        return substr($signature, 0, 12) . '...';
    }

    private function decryptPayload(string $encryptedData, string $key): ?array
    {
        $decoded = base64_decode($encryptedData, true);

        if ($decoded === false) {
            return null;
        }

        [$cipher, $normalizedKey] = $this->normalizeCipherKey($key);
        $iv = substr($normalizedKey, 0, 16);

        $decrypted = openssl_decrypt($decoded, $cipher, $normalizedKey, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            return null;
        }

        $json = json_decode($decrypted, true);

        return is_array($json) ? $json : null;
    }

    private function normalizeCipherKey(string $rawKey): array
    {
        $keyLength = strlen($rawKey);

        if ($keyLength === 16) {
            return ['AES-128-CBC', $rawKey];
        }

        if ($keyLength === 24) {
            return ['AES-192-CBC', $rawKey];
        }

        if ($keyLength === 32) {
            return ['AES-256-CBC', $rawKey];
        }

        return ['AES-128-CBC', substr(hash('sha256', $rawKey, true), 0, 16)];
    }
}
