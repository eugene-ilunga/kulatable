<div>
    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[160px]">
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.dateRange')</label>
                <select wire:model.live="dateRangeType" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="today">@lang('loyalty::app.today')</option>
                    <option value="currentWeek">@lang('loyalty::app.currentWeek')</option>
                    <option value="lastWeek">@lang('loyalty::app.lastWeek')</option>
                    <option value="last7Days">@lang('loyalty::app.last7Days')</option>
                    <option value="currentMonth">@lang('loyalty::app.currentMonth')</option>
                    <option value="lastMonth">@lang('loyalty::app.lastMonth')</option>
                    <option value="currentYear">@lang('loyalty::app.currentYear')</option>
                    <option value="lastYear">@lang('loyalty::app.lastYear')</option>
                    <option value="custom">@lang('loyalty::app.custom')</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.startDate')</label>
                <x-datepicker wire:model.live="startDate" class="mt-1 w-full" />
            </div>
            <div class="min-w-[150px]">
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.endDate')</label>
                <x-datepicker wire:model.live="endDate" class="mt-1 w-full" />
            </div>
            <div class="min-w-[170px]">
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.locationOutlet')</label>
                <select wire:model.live="branchId" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">@lang('loyalty::app.allLocations')</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[170px]">
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.customer')</label>
                <select wire:model.live="customerId" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">@lang('loyalty::app.all')</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[170px]">
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.employee')</label>
                <select wire:model.live="employeeId" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">@lang('loyalty::app.all')</option>
                    <option value="system">@lang('loyalty::app.system')</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 flex justify-end">
                <x-button wire:click="exportReport" type="button">
                    @lang('loyalty::app.exportExcel')
                </x-button>
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.orderId')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.customer')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.pointsRedeemed')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.redemptionValue')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.billBeforeRedemption')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.billAfterRedemption')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.paymentMethod')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.employee')
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($redemptions as $order)
                    @php
                        $orderNumber = $order->order_number ? '#' . $order->order_number : __('loyalty::app.notApplicable');
                        $billAfter = (float) ($order->total ?? 0);
                        $billBefore = $billAfter + (float) ($order->loyalty_discount_amount ?? 0);
                        $pointsRedeemed = (int) (($order->loyalty_points_redeemed ?? 0) > 0
                            ? ($order->loyalty_points_redeemed ?? 0)
                            : ($order->ledger_points_redeemed ?? 0));
                        $employeeName = $order->addedBy?->name ?? $order->waiter?->name ?? __('loyalty::app.system');
                        $methodKey = $order->payment_method ?? 'cash';
                        $paymentLabel = __('modules.order.' . $methodKey);
                        if ($paymentLabel === 'modules.order.' . $methodKey) {
                            $paymentLabel = $methodKey;
                        }
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $orderNumber }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $order->customer?->name ?? __('loyalty::app.unknownCustomer') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($pointsRedeemed) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ currency_format($order->loyalty_discount_amount ?? 0, restaurant()->currency_id) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ currency_format($billBefore, restaurant()->currency_id) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ currency_format($billAfter, restaurant()->currency_id) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $paymentLabel }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $employeeName }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            @lang('loyalty::app.noRedemptionsFound')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $redemptions->links() }}
    </div>
</div>
