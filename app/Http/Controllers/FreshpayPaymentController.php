<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\FreshpayPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\SendNewOrderReceived;
use App\Events\SendOrderBillEvent;

class FreshpayPaymentController extends Controller
{
    public function webhook(Request $request, string $hash)
    {
        Log::info('FreshPay callback received', $this->buildCallbackLogContext($request, $hash));

        $restaurant = Restaurant::where('hash', $hash)->first();

        if (!$restaurant) {
            Log::warning('FreshPay callback rejected: restaurant not found', [
                'hash' => $hash,
            ]);
            return response()->json(['error' => 'Restaurant not found'], 404);
        }

        $paymentGateway = $restaurant->paymentGateways;

        if (!$paymentGateway || !($paymentGateway->freshpay_status ?? false)) {
            Log::warning('FreshPay callback rejected: gateway disabled', [
                'restaurant_id' => $restaurant->id,
            ]);
            return response()->json(['error' => 'FreshPay is not enabled'], 400);
        }

        if (!$this->validateIp($request)) {
            Log::warning('FreshPay callback rejected: unauthorized IP', [
                'restaurant_id' => $restaurant->id,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized IP'], 403);
        }

        $payload = $this->resolveCallbackPayload($request, $paymentGateway, $restaurant->id);

        if ($payload['response'] !== null) {
            return $payload['response'];
        }

        $decryptedPayload = $payload['payload'];
        $reference = (string) ($decryptedPayload['Reference'] ?? $decryptedPayload['reference'] ?? '');
        $transactionId = (string) ($decryptedPayload['PayDRC_Reference'] ?? $decryptedPayload['paydrc_reference'] ?? $decryptedPayload['Transaction_id'] ?? $decryptedPayload['transaction_id'] ?? '');

        $freshpayPayment = FreshpayPayment::query()
            ->when($reference !== '' || $transactionId !== '', function ($query) use ($reference, $transactionId) {
                $query->where(function ($innerQuery) use ($reference, $transactionId) {
                    if ($reference !== '') {
                        $innerQuery->where('freshpay_reference', $reference);
                    }

                    if ($transactionId !== '') {
                        $method = $reference !== '' ? 'orWhere' : 'where';
                        $innerQuery->{$method}('freshpay_payment_id', $transactionId);
                    }
                });
            })
            ->first();

        if (!$freshpayPayment) {
            Log::warning('FreshPay callback: payment not found', [
                'restaurant_id' => $restaurant->id,
                'reference' => $reference,
                'transaction_id' => $transactionId,
            ]);

            return response()->json(['error' => 'Payment not found'], 404);
        }

        $order = Order::with('branch')->find($freshpayPayment->order_id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if (($order->branch->restaurant_id ?? null) !== $restaurant->id) {
            Log::warning('FreshPay callback rejected: restaurant mismatch', [
                'restaurant_id' => $restaurant->id,
                'order_id' => $order->id,
                'order_restaurant_id' => $order->branch->restaurant_id ?? null,
            ]);
            return response()->json(['error' => 'Restaurant mismatch'], 403);
        }

        $transStatus = strtolower((string) ($decryptedPayload['Trans_Status'] ?? $decryptedPayload['trans_status'] ?? ''));
        $isSuccess = in_array($transStatus, ['successful', 'success', 'completed', 'paid'], true);
        $isFailed = in_array($transStatus, ['failed', 'failure', 'error', 'cancelled', 'canceled'], true);

        $freshpayPayment->fill([
            'freshpay_payment_id' => $transactionId !== '' ? $transactionId : $freshpayPayment->freshpay_payment_id,
            'freshpay_action' => $decryptedPayload['Action'] ?? $decryptedPayload['action'] ?? $freshpayPayment->freshpay_action,
            'freshpay_method' => $decryptedPayload['Method'] ?? $decryptedPayload['method'] ?? $freshpayPayment->freshpay_method,
            'customer_number' => $decryptedPayload['Customer_Details'] ?? $decryptedPayload['customer_number'] ?? $freshpayPayment->customer_number,
            'financial_institution_id' => $decryptedPayload['Financial_Institution_id'] ?? $decryptedPayload['financial_institution_id'] ?? $freshpayPayment->financial_institution_id,
            'trans_status' => $decryptedPayload['Trans_Status'] ?? $decryptedPayload['trans_status'] ?? $freshpayPayment->trans_status,
            'trans_status_description' => $decryptedPayload['Trans_Status_Description'] ?? $decryptedPayload['trans_status_description'] ?? $decryptedPayload['Status_Description'] ?? $decryptedPayload['status_description'] ?? $freshpayPayment->trans_status_description,
            'payment_error_response' => $decryptedPayload,
            'callback_payload' => $decryptedPayload,
        ]);

        if ($isSuccess && $freshpayPayment->payment_status !== 'completed') {
            $freshpayPayment->payment_status = 'completed';
            $freshpayPayment->payment_date = now();
            $freshpayPayment->save();

            if ($order->status !== 'paid') {
                $order->amount_paid = (float) ($order->amount_paid ?? 0) + (float) $freshpayPayment->amount;
                $order->status = 'paid';
                $order->save();

                Payment::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'payment_method' => 'freshpay',
                    ],
                    [
                        'branch_id' => $order->branch_id,
                        'amount' => $freshpayPayment->amount,
                        'transaction_id' => $freshpayPayment->freshpay_payment_id ?: $freshpayPayment->freshpay_reference,
                    ]
                );

                Payment::where('order_id', $order->id)
                    ->where('payment_method', 'due')
                    ->delete();

                SendNewOrderReceived::dispatch($order);

                if ($order->customer_id) {
                    SendOrderBillEvent::dispatch($order);
                }
            }
        } elseif ($isFailed && $freshpayPayment->payment_status !== 'completed') {
            $freshpayPayment->payment_status = 'failed';
            $freshpayPayment->save();
        } else {
            $freshpayPayment->save();
        }

        Log::info('FreshPay callback processed', [
            'restaurant_id' => $restaurant->id,
            'order_id' => $order->id,
            'freshpay_payment_id' => $freshpayPayment->id,
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'payment_status' => $freshpayPayment->payment_status,
            'trans_status' => $freshpayPayment->trans_status,
            'order_status' => $order->fresh()->status,
        ]);

        return response()->json([
            'status' => 'Callback received successfully',
            'data' => [
                'reference' => $reference,
                'trans_status' => $freshpayPayment->trans_status,
            ],
        ]);
    }

    private function validateIp(Request $request): bool
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

    private function resolveCallbackPayload(Request $request, $paymentGateway, int $restaurantId): array
    {
        $encryptedData = (string) $request->input('data', '');
        $signature = (string) $request->header('X-Signature', '');
        $rawPayload = $request->all();

        if ($encryptedData !== '' || $signature !== '') {
            if ($encryptedData === '' || $signature === '') {
                Log::warning('FreshPay callback rejected: invalid payload', [
                    'restaurant_id' => $restaurantId,
                    'has_data' => $encryptedData !== '',
                    'has_signature' => $signature !== '',
                ]);

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid callback payload'], 400)];
            }

            $hmacKey = $paymentGateway->freshpay_callback_hmac_key ?: $paymentGateway->freshpay_merchant_secret;
            $expectedSignature = hash_hmac('sha256', $encryptedData, (string) $hmacKey);

            if (!hash_equals(strtolower($expectedSignature), strtolower($signature))) {
                Log::warning('FreshPay callback rejected: invalid signature', [
                    'restaurant_id' => $restaurantId,
                    'ip' => $request->ip(),
                    'received_signature_prefix' => $this->maskSignature($signature),
                    'expected_signature_prefix' => $this->maskSignature($expectedSignature),
                ]);

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid signature'], 401)];
            }

            Log::info('FreshPay callback signature validated', [
                'restaurant_id' => $restaurantId,
                'ip' => $request->ip(),
                'data_length' => strlen($encryptedData),
            ]);

            $decryptedPayload = $this->decryptPayload(
                $encryptedData,
                (string) ($paymentGateway->freshpay_callback_secret_key ?: $paymentGateway->freshpay_merchant_secret)
            );

            if ($decryptedPayload === null || !is_array($decryptedPayload)) {
                Log::warning('FreshPay callback rejected: invalid encryption', [
                    'restaurant_id' => $restaurantId,
                    'ip' => $request->ip(),
                ]);

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid encryption'], 400)];
            }

            Log::info('FreshPay callback decrypted payload', [
                'restaurant_id' => $restaurantId,
                'payload' => $decryptedPayload,
            ]);

            return ['payload' => $decryptedPayload, 'response' => null];
        }

        if (empty($rawPayload)) {
            Log::warning('FreshPay callback rejected: invalid payload', [
                'restaurant_id' => $restaurantId,
                'has_data' => false,
                'has_signature' => false,
            ]);

            return ['payload' => null, 'response' => response()->json(['error' => 'Invalid callback payload'], 400)];
        }

        Log::info('FreshPay callback accepted in plain JSON mode', [
            'restaurant_id' => $restaurantId,
            'payload' => $rawPayload,
        ]);

        return ['payload' => $rawPayload, 'response' => null];
    }

    private function buildCallbackLogContext(Request $request, ?string $hash = null): array
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

        // FreshPay examples use 16-byte keys in code samples.
        return ['AES-128-CBC', substr(hash('sha256', $rawKey, true), 0, 16)];
    }
}
