<div class="freshpay_payment">
    <div class="grid gap-4" @if($freshpayPendingPaymentId) wire:poll.5s="refreshFreshpayPaymentStatus" @endif>
        @if($freshpayPendingPaymentId)
            <div class="p-4 border rounded-lg bg-amber-50 border-amber-200 text-amber-900">
                <div class="font-semibold">@lang('modules.billing.freshpayPendingTitle')</div>
                <div class="mt-1 text-sm">
                    @lang('modules.billing.freshpayPendingDescription')
                </div>
                @if($freshpayPendingReference)
                    <div class="mt-2 text-xs">
                        @lang('modules.billing.reference'): <span class="font-medium">{{ $freshpayPendingReference }}</span>
                    </div>
                @endif
            </div>
        @else
            <div>
                <x-label for="freshpayCustomerNumber" :value="__('modules.billing.customerNumber')" />
                <x-input id="freshpayCustomerNumber" class="block w-full mt-1" type="text" wire:model.defer="freshpayCustomerNumber" />
                <x-input-error for="freshpayCustomerNumber" class="mt-2" />
            </div>

            <div>
                <x-label for="freshpayMethod" :value="__('modules.billing.freshpayMethod')" />
                <x-select id="freshpayMethod" class="block w-full mt-1" wire:model.live="freshpayMethod">
                    <option value="">@lang('modules.billing.selectFreshpayMethod')</option>
                    <option value="airtel">Airtel Money</option>
                    <option value="orange">Orange Money</option>
                    <option value="mpesa">M-Pesa</option>
                </x-select>
                <x-input-error for="freshpayMethod" class="mt-2" />
            </div>

            <div class="text-sm text-gray-500">
                @lang('modules.billing.freshpayCustomerHelp')
            </div>
        @endif
    </div>
</div>
