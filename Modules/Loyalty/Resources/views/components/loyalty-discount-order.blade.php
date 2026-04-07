@if ($order->loyalty_points_redeemed > 0 && $order->loyalty_discount_amount > 0)
    <div wire:key="loyaltyDiscount" class="flex justify-between {{ $textSize ?? 'text-sm' }} text-blue-600 dark:text-blue-400">
        <div>
            @lang('loyalty::app.loyaltyDiscount') ({{ number_format($order->loyalty_points_redeemed) }} @lang('loyalty::app.points'))
        </div>
        <div>
            -{{ currency_format($order->loyalty_discount_amount, $currencyId ?? restaurant()->currency_id) }}
        </div>
    </div>
@endif

