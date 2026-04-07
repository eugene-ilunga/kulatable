<?php

namespace Modules\RestApi\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\RestApi\Entities\RestApiGlobalSetting;

class FirebaseNotificationService
{
    protected ?array $credentials = null;

    protected ?string $projectId = null;

    public function __construct()
    {
        $settings = RestApiGlobalSetting::instance();

        $file = $settings->firebase_service_account_json ?: null;
        if ($file) {
            $path = public_path('user-uploads/firebase/' . $file);
            if (is_file($path)) {
                $json = file_get_contents($path);
                $this->credentials = json_decode($json, true);
            }
        }

        $this->projectId = $settings->firebase_project_id ?: ($this->credentials['project_id'] ?? null);
    }

    public function isConfigured(): bool
    {
        $settings = RestApiGlobalSetting::instance();

        return (bool)$settings->firebase_enabled
            && ! empty($this->projectId)
            && ! empty($this->credentials['client_email'])
            && ! empty($this->credentials['private_key']);
    }

    /**
     * Send push notification to FCM token(s).
     *
     * @param  array<string>        $tokens  One or more FCM device tokens
     * @param  string               $title   Notification title
     * @param  string               $body    Notification body
     * @param  array<string, mixed> $data    Optional data payload
     * @return bool                          True if at least one send succeeded
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured()) {
            Log::debug('RestApi FCM: Not configured, skipping notification.');

            return false;
        }

        $tokens = array_values(array_filter(array_unique($tokens)));
        if (empty($tokens)) {
            return false;
        }

        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            Log::warning('RestApi FCM: Failed to obtain access token.');

            return false;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';
        $success = false;
        $priority = 'high';

        foreach ($tokens as $token) {
            $dataPayload = array_map(fn ($v) => (string)$v, $data);
            unset($dataPayload['screen'], $dataPayload['order_uuid'], $dataPayload['orderId'], $dataPayload['id']);

            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $dataPayload,
                    'android' => [
                        'priority' => $priority,
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => "10",
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($url, $message);

            if ($response->successful()) {
                $success = true;
            } else {
                Log::channel('single')->warning('RestApi FCM send failed', [
                    'token_preview' => substr($token, 0, 20) . '...',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        }

        return $success;
    }

    protected function getAccessToken(): ?string
    {
        if (empty($this->credentials)) {
            return null;
        }

        $clientEmail = $this->credentials['client_email'] ?? null;
        $privateKey = $this->credentials['private_key'] ?? null;

        if (!$clientEmail || !$privateKey) {
            return null;
        }

        $privateKey = str_replace('\\n', "\n", $privateKey);
        $jwt = $this->createJwt($clientEmail, $privateKey);
        if (!$jwt) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            Log::warning('RestApi FCM: OAuth2 token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('access_token');
    }

    protected function createJwt(string $clientEmail, string $privateKey): ?string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now = time();
        $payload = [
            'iss' => $clientEmail,
            'sub' => $clientEmail,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $headerEnc = $this->base64UrlEncode(json_encode($header));
        $payloadEnc = $this->base64UrlEncode(json_encode($payload));
        $signatureInput = $headerEnc . '.' . $payloadEnc;

        $signature = '';
        $ok = openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$ok) {
            return null;
        }

        return $signatureInput . '.' . $this->base64UrlEncode($signature);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
