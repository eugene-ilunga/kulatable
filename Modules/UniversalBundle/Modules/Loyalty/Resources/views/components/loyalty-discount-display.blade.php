@if ($loyaltyPointsRedeemed > 0 && $loyaltyDiscountAmount > 0)
    <div wire:key="loyaltyDiscount" class="flex justify-between {{ $textSize ?? 'text-xs' }} text-blue-600 dark:text-blue-400">
        <div class="inline-flex items-center gap-x-1">
            @lang('loyalty::app.loyaltyDiscount') ({{ number_format($loyaltyPointsRedeemed) }} @lang('loyalty::app.points'))
            @if(isset($showEditIcon) && $showEditIcon && isset($customer) && $customer)
                <span class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 cursor-pointer ml-1"
                    wire:click="editLoyaltyRedemption"
                    title="{{ __('Edit loyalty points') }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </span>
            @endif
        </div>
        <div class="text-blue-600 dark:text-blue-400 font-medium">
            -{{ currency_format($loyaltyDiscountAmount, $currencyId) }}
        </div>
    </div>
@endif

