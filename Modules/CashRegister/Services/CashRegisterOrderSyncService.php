<?php

namespace Modules\CashRegister\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;

class CashRegisterOrderSyncService
{
    /**
     * Sync cash sale transactions for each cash payment on an order.
     */
    public static function syncCashForOrder(Order $order): void
    {
        $order->loadMissing(['payments']);

        $payments = collect();
        if (method_exists($order, 'payments')) {
            $payments = $order->payments->filter(function ($payment) {
                return ($payment->payment_method ?? null) !== 'due' && (float) ($payment->amount ?? 0) > 0;
            });
        }

        $userId = Auth::id() ?? $order->created_by;
        if (!$userId) {
            return;
        }

        $session = CashRegisterSession::where('opened_by', $userId)
            ->where('status', 'open')
            ->where('branch_id', $order->branch_id ?? branch()->id ?? 0)
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return;
        }

        $paymentIds = [];
        foreach ($payments as $payment) {
            $paymentIds[] = $payment->id;
            $transaction = self::findTransactionForPayment($order, $payment);
            $paymentMethod = (string) ($payment->payment_method ?? '');
            $type = $paymentMethod === 'cash' ? 'cash_sale' : 'order_payment';

            $payload = [
                'cash_register_session_id' => $session->id,
                'restaurant_id' => $session->restaurant_id,
                'branch_id' => $session->branch_id,
                'happened_at' => $payment->created_at ?? now(),
                'type' => $type,
                'reference' => (string) ($order->uuid ?? $order->id),
                'reason' => 'Order payment' . ($paymentMethod !== '' ? ' (' . $paymentMethod . ')' : ''),
                'amount' => (float) ($payment->amount ?? 0),
                'created_by' => $userId,
            ];

            if (self::hasPaymentIdColumn()) {
                $payload['payment_id'] = $payment->id;
            }
            if (self::hasPaymentMethodColumn()) {
                $payload['payment_method'] = $paymentMethod;
            }

            if ($transaction) {
                $transaction->update($payload);
            } else {
                $payload['running_amount'] = 0;
                $payload['order_id'] = $order->id;
                CashRegisterTransaction::create($payload);
            }
        }

        if (self::hasPaymentIdColumn()) {
            CashRegisterTransaction::where('order_id', $order->id)
                ->whereIn('type', ['cash_sale', 'order_payment'])
                ->whereNotNull('payment_id')
                ->when(!empty($paymentIds), function ($query) use ($paymentIds) {
                    $query->whereNotIn('payment_id', $paymentIds);
                }, function ($query) {
                    $query->whereNotNull('payment_id');
                })
                ->delete();

            if (!empty($paymentIds)) {
                CashRegisterTransaction::where('order_id', $order->id)
                    ->whereIn('type', ['cash_sale', 'order_payment'])
                    ->whereNull('payment_id')
                    ->delete();
            }
        }
    }

    public static function syncPaidCashOrder(Order $order): void
    {
        self::syncCashForOrder($order);
    }

    public static function syncCashPayment(Payment $payment): void
    {
        if (($payment->payment_method ?? null) === 'due' || (float) ($payment->amount ?? 0) <= 0) {
            return;
        }

        $order = method_exists($payment, 'order') ? $payment->order : null;
        if (!$order) {
            return;
        }

        self::syncCashForOrder($order->fresh(['payments']));
    }

    private static function findTransactionForPayment(Order $order, Payment $payment): ?CashRegisterTransaction
    {
        if (self::hasPaymentIdColumn()) {
            return CashRegisterTransaction::where('payment_id', $payment->id)->first();
        }

        return CashRegisterTransaction::where('order_id', $order->id)
            ->whereIn('type', ['cash_sale', 'order_payment'])
            ->where('amount', (float) ($payment->amount ?? 0))
            ->first();
    }

    private static function hasPaymentIdColumn(): bool
    {
        static $hasPaymentId = null;

        if ($hasPaymentId === null) {
            $hasPaymentId = Schema::hasColumn('cash_register_transactions', 'payment_id');
        }

        return $hasPaymentId;
    }

    private static function hasPaymentMethodColumn(): bool
    {
        static $hasPaymentMethod = null;

        if ($hasPaymentMethod === null) {
            $hasPaymentMethod = Schema::hasColumn('cash_register_transactions', 'payment_method');
        }

        return $hasPaymentMethod;
    }
}
