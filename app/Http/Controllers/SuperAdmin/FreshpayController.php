<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use App\Models\GlobalInvoice;
use App\Models\RestaurantPayment;
use App\Models\GlobalSubscription;
use App\Http\Controllers\Controller;
use App\Models\SuperadminPaymentGateway;
use App\Notifications\RestaurantUpdatedPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FreshpayController extends Controller
{
    public function webhook(Request $request, ?string $hash = null)
    {
        Log::info('FreshPay plan callback received', $this->buildCallbackLogContext($request, $hash));

        $paymentGateway = SuperadminPaymentGateway::first();

        if (!$paymentGateway || !($paymentGateway->freshpay_status ?? false)) {
            Log::warning('FreshPay plan callback rejected: gateway disabled');
            return response()->json(['error' => 'FreshPay is not enabled'], 400);
        }

        if ($hash && $hash !== global_setting()->hash) {
            Log::warning('FreshPay plan callback rejected: unauthorized hash', [
                'hash' => $hash,
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$this->validateIp($request)) {
            Log::warning('FreshPay plan callback rejected: unauthorized IP', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized IP'], 403);
        }

        $payload = $this->resolveCallbackPayload($request, $paymentGateway);

        if ($payload['response'] !== null) {
            return $payload['response'];
        }

        $decryptedPayload = $payload['payload'];
        $reference = (string) ($decryptedPayload['Reference'] ?? $decryptedPayload['reference'] ?? '');
        $transactionId = (string) ($decryptedPayload['PayDRC_Reference'] ?? $decryptedPayload['paydrc_reference'] ?? $decryptedPayload['Transaction_id'] ?? $decryptedPayload['transaction_id'] ?? '');

        $restaurantPayment = RestaurantPayment::query()
            ->when($reference !== '' || $transactionId !== '', function ($query) use ($reference, $transactionId) {
                $query->where(function ($innerQuery) use ($reference, $transactionId) {
                    if ($reference !== '') {
                        $innerQuery->where('reference_id', $reference);
                    }

                    if ($transactionId !== '') {
                        $method = $reference !== '' ? 'orWhere' : 'where';
                        $innerQuery->{$method}('transaction_id', $transactionId);
                    }
                });
            })
            ->latest()
            ->first();

        if (!$restaurantPayment) {
            Log::warning('FreshPay plan callback: payment not found', [
                'reference' => $reference,
                'transaction_id' => $transactionId,
            ]);

            return response()->json(['error' => 'Payment not found'], 404);
        }

        $transStatus = strtolower((string) ($decryptedPayload['Trans_Status'] ?? $decryptedPayload['trans_status'] ?? ''));
        $isSuccess = in_array($transStatus, ['successful', 'success', 'completed', 'paid'], true);
        $isFailed = in_array($transStatus, ['failed', 'failure', 'error', 'cancelled', 'canceled'], true);

        if ($transactionId !== '') {
            $restaurantPayment->transaction_id = $transactionId;
        }

        if ($isSuccess && $restaurantPayment->status !== 'paid') {
            $finalTransactionId = $transactionId ?: ($reference ?: (string) $restaurantPayment->reference_id);
            return $this->processSuccessfulPayment($restaurantPayment, $finalTransactionId);
        }

        if ($isFailed && $restaurantPayment->status !== 'paid') {
            $restaurantPayment->status = 'failed';
            $restaurantPayment->payment_date_time = now();
            $restaurantPayment->save();
        } else {
            $restaurantPayment->save();
        }

        Log::info('FreshPay plan callback processed', [
            'restaurant_payment_id' => $restaurantPayment->id,
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'status' => $restaurantPayment->status,
            'trans_status' => $transStatus,
        ]);

        return response()->json([
            'status' => 'Callback received',
            'data' => [
                'reference' => $reference,
                'transaction_id' => $transactionId,
                'trans_status' => $transStatus,
            ],
        ]);
    }

    private function processSuccessfulPayment(RestaurantPayment $restaurantPayment, string $transactionId)
    {
        DB::beginTransaction();

        try {
            $restaurant = Restaurant::find($restaurantPayment->restaurant_id);
            $package = Package::find($restaurantPayment->package_id);

            if (!$restaurant || !$package) {
                DB::rollBack();
                return response()->json(['error' => 'Invalid payment references'], 404);
            }

            $restaurantPayment->status = 'paid';
            $restaurantPayment->payment_date_time = now();
            $restaurantPayment->transaction_id = $transactionId;
            $restaurantPayment->save();

            $restaurant->package_id = $restaurantPayment->package_id;
            $restaurant->package_type = $restaurantPayment->package_type;
            $restaurant->trial_ends_at = null;
            $restaurant->is_active = true;
            $restaurant->status = 'active';
            $restaurant->license_expire_on = null;
            $restaurant->license_updated_at = now();
            $restaurant->subscription_updated_at = now();
            $restaurant->save();

            clearRestaurantModulesCache($restaurant->id);

            GlobalSubscription::where('restaurant_id', $restaurant->id)
                ->where('subscription_status', 'active')
                ->update(['subscription_status' => 'inactive']);

            $isRecurring = in_array($restaurantPayment->package_type, ['monthly', 'annual'], true);
            $nextDate = null;

            if ($restaurantPayment->package_type === 'annual') {
                $nextDate = now()->addYear();
            } elseif ($restaurantPayment->package_type === 'monthly') {
                $nextDate = now()->addMonth();
            }

            $subscription = GlobalSubscription::create([
                'restaurant_id' => $restaurant->id,
                'package_id' => $package->id,
                'currency_id' => $restaurantPayment->currency_id,
                'package_type' => $restaurantPayment->package_type,
                'transaction_id' => $transactionId,
                'gateway_name' => 'freshpay',
                'subscription_status' => 'active',
                'subscribed_on_date' => now(),
                'ends_at' => $isRecurring ? $nextDate : null,
                'quantity' => 1,
            ]);

            GlobalInvoice::create([
                'restaurant_id' => $restaurant->id,
                'package_id' => $package->id,
                'currency_id' => $restaurantPayment->currency_id,
                'package_type' => $restaurantPayment->package_type,
                'transaction_id' => $transactionId,
                'global_subscription_id' => $subscription->id,
                'gateway_name' => 'freshpay',
                'amount' => $restaurantPayment->amount,
                'total' => $restaurantPayment->amount,
                'status' => 'active',
                'pay_date' => now()->format('Y-m-d H:i:s'),
                'next_pay_date' => $isRecurring ? $nextDate : null,
                'reference_id' => $restaurantPayment->reference_id,
            ]);

            DB::commit();

            $superadmin = User::withoutGlobalScopes()->whereNull('branch_id')->whereNull('restaurant_id')->first();
            if ($superadmin) {
                Notification::send($superadmin, new RestaurantUpdatedPlan($restaurant, $package->id));
            }

            $restaurantAdmin = $restaurant->restaurantAdmin($restaurant);
            if ($restaurantAdmin) {
                Notification::send($restaurantAdmin, new RestaurantUpdatedPlan($restaurant, $package->id));
            }

            return response()->json(['status' => 'Payment processed successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('FreshPay plan webhook processing failed', [
                'restaurant_payment_id' => $restaurantPayment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
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

    private function resolveCallbackPayload(Request $request, $paymentGateway): array
    {
        $encryptedData = (string) $request->input('data', '');
        $signature = (string) $request->header('X-Signature', '');
        $rawPayload = $request->all();

        if ($encryptedData !== '' || $signature !== '') {
            if ($encryptedData === '' || $signature === '') {
                Log::warning('FreshPay plan callback rejected: invalid payload', [
                    'has_data' => $encryptedData !== '',
                    'has_signature' => $signature !== '',
                ]);

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid callback payload'], 400)];
            }

            $hmacKey = $paymentGateway->freshpay_callback_hmac_key ?: $paymentGateway->freshpay_merchant_secret;
            $expectedSignature = hash_hmac('sha256', $encryptedData, (string) $hmacKey);

            if (!hash_equals(strtolower($expectedSignature), strtolower($signature))) {
                Log::warning('FreshPay plan callback rejected: invalid signature', [
                    'ip' => $request->ip(),
                    'received_signature_prefix' => $this->maskSignature($signature),
                    'expected_signature_prefix' => $this->maskSignature($expectedSignature),
                ]);

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid signature'], 401)];
            }

            Log::info('FreshPay plan callback signature validated', [
                'ip' => $request->ip(),
                'data_length' => strlen($encryptedData),
            ]);

            $decryptedPayload = $this->decryptPayload(
                $encryptedData,
                (string) ($paymentGateway->freshpay_callback_secret_key ?: $paymentGateway->freshpay_merchant_secret)
            );

            if ($decryptedPayload === null || !is_array($decryptedPayload)) {
                Log::warning('FreshPay plan callback rejected: invalid encryption', [
                    'ip' => $request->ip(),
                ]);

                return ['payload' => null, 'response' => response()->json(['error' => 'Invalid encryption'], 400)];
            }

            Log::info('FreshPay plan callback decrypted payload', [
                'payload' => $decryptedPayload,
            ]);

            return ['payload' => $decryptedPayload, 'response' => null];
        }

        if (empty($rawPayload)) {
            Log::warning('FreshPay plan callback rejected: invalid payload', [
                'has_data' => false,
                'has_signature' => false,
            ]);

            return ['payload' => null, 'response' => response()->json(['error' => 'Invalid callback payload'], 400)];
        }

        Log::info('FreshPay plan callback accepted in plain JSON mode', [
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

        return ['AES-128-CBC', substr(hash('sha256', $rawKey, true), 0, 16)];
    }
}
