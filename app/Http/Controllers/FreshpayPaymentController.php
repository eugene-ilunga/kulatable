<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\FreshpayPayment;
use Illuminate\Http\Request;
use App\Services\Freshpay\FreshpayCallbackService;
use Illuminate\Support\Facades\Log;
use App\Events\SendNewOrderReceived;
use App\Events\SendOrderBillEvent;

class FreshpayPaymentController extends Controller
{
    public function webhook(Request $request, string $hash)
    {
        $callbackService = app(FreshpayCallbackService::class);

        Log::info('FreshPay callback received', $callbackService->buildLogContext($request, $hash));

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

        if (!$callbackService->validateIp($request)) {
            Log::warning('FreshPay callback rejected: unauthorized IP', [
                'restaurant_id' => $restaurant->id,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized IP'], 403);
        }

        $payload = $callbackService->resolvePayload($request, $paymentGateway, [
            'restaurant_id' => $restaurant->id,
        ]);

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

}
