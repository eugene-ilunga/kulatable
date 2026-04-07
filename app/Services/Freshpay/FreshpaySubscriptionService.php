<?php

namespace App\Services\Freshpay;

use App\Models\Package;
use App\Models\RestaurantPayment;
use App\Models\SuperadminPaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FreshpaySubscriptionService
{
    public const SUPPORTED_METHODS = ['airtel', 'orange', 'mpesa'];

    public function initiate(
        SuperadminPaymentGateway $gateway,
        RestaurantPayment $payment,
        Package $package,
        string $customerNumber,
        string $method
    ): RestaurantPayment {
        $method = strtolower(trim($method));

        if (!in_array($method, self::SUPPORTED_METHODS, true)) {
            throw new InvalidArgumentException(__('messages.invalidFreshpayMethod'));
        }

        $this->ensureGatewayIsConfigured($gateway);

        $payment->forceFill([
            'reference_id' => $payment->reference_id ?: $this->generateReference($package),
            'freshpay_customer_number' => $customerNumber,
            'freshpay_method' => $method,
        ])->save();

        $payload = $this->buildPayload($gateway, $payment, $package, $customerNumber, $method);
        $response = Http::acceptJson()
            ->timeout(20)
            ->post($gateway->freshpay_api_url, $payload);

        $responsePayload = $response->json();

        if (!is_array($responsePayload)) {
            $responsePayload = [
                'raw' => $response->body(),
            ];
        }

        $payment->forceFill([
            'freshpay_request_payload' => $payload,
            'freshpay_response_payload' => $responsePayload,
            'transaction_id' => $this->extractTransactionId($responsePayload) ?: $payment->transaction_id,
            'freshpay_status_description' => $this->extractStatusDescription($responsePayload),
        ]);

        if (!$response->successful() || $this->isFailedResponse($responsePayload)) {
            $payment->status = 'failed';
            $payment->payment_date_time = now();
            $payment->save();

            $message = $this->extractStatusDescription($responsePayload) ?: __('messages.freshpayPaymentRequestFailed');
            throw new InvalidArgumentException($message);
        }

        $payment->save();

        return $payment->fresh();
    }

    public function buildPayload(
        SuperadminPaymentGateway $gateway,
        RestaurantPayment $payment,
        Package $package,
        string $customerNumber,
        string $method
    ): array {
        return [
            'merchant_id' => $gateway->freshpay_merchant_id,
            'merchant_secrete' => $gateway->freshpay_merchant_secret,
            'amount' => $this->formatAmount((float) $payment->amount),
            'currency' => strtoupper((string) ($package->currency?->currency_code ?: 'USD')),
            'action' => 'debit',
            'customer_number' => $customerNumber,
            'firstname' => $gateway->freshpay_firstname,
            'lastname' => $gateway->freshpay_lastname,
            'email' => $gateway->freshpay_email,
            'reference' => $payment->reference_id,
            'method' => $method,
            'callback_url' => route('billing.save-freshpay-webhook', ['hash' => global_setting()?->hash]),
        ];
    }

    private function ensureGatewayIsConfigured(SuperadminPaymentGateway $gateway): void
    {
        if (!($gateway->freshpay_status ?? false)) {
            throw new InvalidArgumentException(__('messages.freshpayGatewayDisabled'));
        }

        $requiredFields = [
            'freshpay_api_url' => $gateway->freshpay_api_url,
            'freshpay_merchant_id' => $gateway->freshpay_merchant_id,
            'freshpay_merchant_secret' => $gateway->freshpay_merchant_secret,
            'freshpay_firstname' => $gateway->freshpay_firstname,
            'freshpay_lastname' => $gateway->freshpay_lastname,
            'freshpay_email' => $gateway->freshpay_email,
        ];

        foreach ($requiredFields as $field => $value) {
            if (blank($value)) {
                throw new InvalidArgumentException(__('messages.freshpayMissingConfiguration') . ' (' . $field . ')');
            }
        }
    }

    private function generateReference(Package $package): string
    {
        return 'fp_plan_' . $package->id . '_' . Str::upper(Str::random(10));
    }

    private function formatAmount(float $amount): string
    {
        $formatted = number_format($amount, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function extractTransactionId(array $responsePayload): ?string
    {
        foreach (['PayDRC_Reference', 'paydrc_reference', 'Transaction_id', 'transaction_id', 'id'] as $key) {
            if (!empty($responsePayload[$key])) {
                return (string) $responsePayload[$key];
            }
        }

        return null;
    }

    private function extractStatusDescription(array $responsePayload): ?string
    {
        foreach (['message', 'Message', 'Status_Description', 'status_description', 'Trans_Status_Description', 'trans_status_description', 'error'] as $key) {
            if (!empty($responsePayload[$key]) && is_scalar($responsePayload[$key])) {
                return (string) $responsePayload[$key];
            }
        }

        return null;
    }

    private function isFailedResponse(array $responsePayload): bool
    {
        $status = strtolower((string) ($responsePayload['status'] ?? $responsePayload['Status'] ?? $responsePayload['Trans_Status'] ?? $responsePayload['trans_status'] ?? ''));

        return in_array($status, ['failed', 'failure', 'error', 'cancelled', 'canceled', 'declined'], true);
    }
}
