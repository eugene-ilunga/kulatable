<?php

namespace Modules\Whatsapp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\Entities\WhatsAppSetting;
use Modules\Whatsapp\Entities\WhatsAppNotificationLog;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify webhook (GET request from Meta)
     * This endpoint is called by Meta to verify the webhook URL
     * Also handles POST webhook events if Meta sends them to this endpoint
     */
    public function verify(Request $request)
    {
        // If POST request with webhook event data, handle it as a webhook event
        if ($request->isMethod('POST') && ($request->has('object') || $request->has('entry'))) {
            Log::info('WhatsApp Webhook Event received at verify endpoint, redirecting to handle', [
                'ip' => $request->ip(),
            ]);
            return $this->handle($request);
        }

        // Handle GET verification request
        // Read parameters - Meta sends them with dots (hub.mode) but Laravel converts to underscores
        $mode = $request->query('hub_mode') ?? $request->input('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->input('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->input('hub.challenge');

        Log::info('WhatsApp Webhook Verification Request', [
            'method' => $request->method(),
            'mode' => $mode,
            'token_received' => $token ? '***' . substr($token, -4) : null,
            'challenge' => $challenge ? '***' : null,
            'ip' => $request->ip(),
            'query_string' => $request->getQueryString(),
            'all_query_params' => $request->query(),
            'all_input_keys' => array_keys($request->all()),
        ]);

        $settings = WhatsAppSetting::first();

        if (!$settings) {
            Log::warning('WhatsApp Webhook verification failed: No settings found');
            return response('No WhatsApp settings found', 404);
        }

        $expectedToken = $settings->verify_token ?? null;

        if ($mode === 'subscribe' && $token === $expectedToken) {
            // Save webhook URL and verification timestamp
            $settings->webhook_url = $this->generateWebhookUrl();
            $settings->webhook_verified_at = now();
            $settings->save();

            Log::info('WhatsApp Webhook verification successful', [
                'setting_id' => $settings->id,
                'webhook_url' => $settings->webhook_url,
            ]);

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Webhook verification failed', [
            'mode' => $mode,
            'token_received' => $token ? '***' . substr($token, -4) : null,
            'token_expected' => $expectedToken ? '***' : null,
            'setting_id' => $settings->id ?? null,
        ]);

        return response('Invalid verify token', 403);
    }

    /**
     * Handle webhook events (POST request from Meta)
     * This endpoint receives message status updates and other webhook events
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('WhatsApp Webhook Event Received', [
            'method' => $request->method(),
            'object' => $payload['object'] ?? null,
            'entry_count' => isset($payload['entry']) ? count($payload['entry']) : 0,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
        ]);

        // Process webhook events
        if (isset($payload['object']) && $payload['object'] === 'whatsapp_business_account') {
            if (isset($payload['entry'])) {
                foreach ($payload['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            $this->processWebhookChange($change);
                        }
                    }
                }
            }
        } else {
            Log::warning('WhatsApp Webhook Event: Unknown object type', [
                'object' => $payload['object'] ?? null,
                'payload_keys' => array_keys($payload),
            ]);
        }

        // Always return 200 to acknowledge receipt
        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Process a webhook change event
     */
    protected function processWebhookChange(array $change): void
    {
        $value = $change['value'] ?? [];

        // Handle message status updates
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                $this->processMessageStatus($status);
            }
        }

        // Handle incoming messages (button responses, etc.)
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                if (isset($message['type']) && $message['type'] === 'button') {
                    $this->processButtonResponse($message, $value['metadata']['phone_number_id'] ?? null);
                }
            }
        }
    }

    /**
     * Process message status update
     */
    protected function processMessageStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $recipientId = $status['recipient_id'] ?? null;
        $statusType = $status['status'] ?? null; // sent, delivered, read, failed
        $timestamp = $status['timestamp'] ?? null;
        $error = $status['errors'] ?? null;

        Log::info('WhatsApp Message Status Update', [
            'message_id' => $messageId,
            'recipient_id' => $recipientId,
            'status' => $statusType,
            'timestamp' => $timestamp,
            'error' => $error,
        ]);

        // Update notification log if exists
        if ($messageId) {
            // Use correct column name: whatsapp_message_id
            $log = WhatsAppNotificationLog::where('whatsapp_message_id', $messageId)->first();
            if ($log) {
                // Map Meta statuses to our enum values
                // Meta sends: sent, delivered, read, failed
                // Our enum allows: sent, failed, pending
                $mappedStatus = 'sent'; // Default to sent for delivered/read
                
                if ($statusType === 'failed') {
                    $mappedStatus = 'failed';
                    // Update error message if available
                    if ($error && isset($error[0]['message'])) {
                        $log->error_message = $error[0]['message'];
                    }
                } elseif (in_array($statusType, ['sent', 'delivered', 'read'])) {
                    $mappedStatus = 'sent';
                    // Update sent_at timestamp if not already set
                    if (!$log->sent_at && $timestamp) {
                        $log->sent_at = date('Y-m-d H:i:s', $timestamp);
                    }
                }

                $log->status = $mappedStatus;
                $log->save();

                Log::info('WhatsApp Notification Log Updated', [
                    'log_id' => $log->id,
                    'whatsapp_message_id' => $messageId,
                    'meta_status' => $statusType,
                    'mapped_status' => $mappedStatus,
                ]);
            } else {
                Log::warning('WhatsApp Notification Log not found for message', [
                    'whatsapp_message_id' => $messageId,
                    'status' => $statusType,
                ]);
            }
        }
    }

    /**
     * Process button response from user
     */
    protected function processButtonResponse(array $message, ?string $phoneNumberId): void
    {
        // Handle button responses if needed
        Log::info('WhatsApp Button Response Received', [
            'from' => $message['from'] ?? null,
            'button_text' => $message['button']['text'] ?? null,
            'phone_number_id' => $phoneNumberId,
        ]);
    }

    /**
     * Generate the webhook callback URL
     */
    protected function generateWebhookUrl(): string
    {
        return route('whatsapp.webhook.handle');
    }
}

