<?php

namespace Modules\Webhooks\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Webhooks\Entities\SystemWebhook;
use Modules\Webhooks\Entities\SystemWebhookDelivery;

/**
 * Job to dispatch system-level webhooks.
 */
class DispatchSystemWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $deliveryId;

    public function __construct(SystemWebhookDelivery $delivery)
    {
        $this->deliveryId = $delivery->id;
    }

    public function handle(): void
    {
        $delivery = SystemWebhookDelivery::find($this->deliveryId);
        if (! $delivery || $delivery->status === 'succeeded') {
            return;
        }

        $webhook = SystemWebhook::find($delivery->system_webhook_id);

        if (! $webhook || ! $webhook->is_active) {
            $delivery->update(['status' => 'disabled']);
            return;
        }

        $payload = $delivery->payload ?? [];
        $timestamp = now()->timestamp;
        $signature = $this->signPayload($webhook->secret, $timestamp, $payload);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Idempotency-Key' => $delivery->idempotency_key ?? Str::uuid()->toString(),
            'X-Webhook-Event' => $delivery->event,
            'X-Webhook-Type' => 'system', // Indicates this is a system-level webhook
            'X-Webhook-Restaurant' => $delivery->restaurant_id,
            'X-Webhooks-Version' => config('webhooks.version', config('modules.webhooks.version', '1.0.0')),
            'X-Webhook-Schema' => $payload['schema_version'] ?? 1,
        ];

        // Merge custom headers if defined
        if (! empty($webhook->custom_headers) && is_array($webhook->custom_headers)) {
            $headers = array_merge($headers, $webhook->custom_headers);
        }

        $response = null;
        $startedAt = microtime(true);

        try {
            $response = Http::timeout(10)
                ->retry(0, 0)
                ->withHeaders($headers)
                ->post($webhook->target_url, $payload);

            $duration = (int) ((microtime(true) - $startedAt) * 1000);

            $delivery->update([
                'status' => $response->successful() ? 'succeeded' : 'failed',
                'attempts' => $delivery->attempts + 1,
                'response_code' => $response->status(),
                'response_body' => substr((string) $response->body(), 0, 5000),
                'duration_ms' => $duration,
                'error_message' => $response->successful() ? null : 'Non-2xx response',
                'next_retry_at' => $response->successful()
                    ? null
                    : now()->addSeconds($webhook->backoff_seconds * max(1, $delivery->attempts)),
            ]);

            if (! $response->successful()) {
                $this->queueRetryIfNeeded($webhook, $delivery);
            }
        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $startedAt) * 1000);

            $delivery->update([
                'status' => 'failed',
                'attempts' => $delivery->attempts + 1,
                'response_code' => null,
                'response_body' => null,
                'duration_ms' => $duration,
                'error_message' => $e->getMessage(),
                'next_retry_at' => now()->addSeconds($webhook->backoff_seconds * max(1, $delivery->attempts)),
            ]);

            $this->queueRetryIfNeeded($webhook, $delivery);
        }
    }

    private function queueRetryIfNeeded(SystemWebhook $webhook, SystemWebhookDelivery $delivery): void
    {
        if ($delivery->attempts >= $webhook->max_attempts) {
            return;
        }

        $delay = $webhook->backoff_seconds * $delivery->attempts;
        self::dispatch($delivery)->delay(now()->addSeconds($delay));
    }

    private function signPayload(string $secret, int $timestamp, array $payload): string
    {
        $body = json_encode($payload);
        $signed = $timestamp . '.' . $body;

        return hash_hmac('sha256', $signed, $secret);
    }
}
