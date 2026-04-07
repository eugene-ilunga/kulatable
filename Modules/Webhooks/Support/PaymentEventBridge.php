<?php

namespace Modules\Webhooks\Support;

use App\Events\PaymentFailed;
use App\Events\PaymentSuccess;

class PaymentEventBridge
{
    public static function dispatchSuccess(array $data): void
    {
        event(new PaymentSuccess(
            $data['restaurant_id'] ?? null,
            $data['branch_id'] ?? null,
            $data['gateway'] ?? null,
            $data['transaction_id'] ?? null,
            $data['amount'] ?? null,
            $data['currency'] ?? null,
            $data['order_id'] ?? null,
        ));
    }

    public static function dispatchFailure(array $data): void
    {
        event(new PaymentFailed(
            $data['restaurant_id'] ?? null,
            $data['branch_id'] ?? null,
            $data['gateway'] ?? null,
            $data['transaction_id'] ?? null,
            $data['amount'] ?? null,
            $data['currency'] ?? null,
            $data['order_id'] ?? null,
            $data['reason'] ?? null,
        ));
    }
}
