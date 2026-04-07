<div>
    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
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
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.startDate')</label>
                <x-datepicker wire:model.live="startDate" class="mt-1 w-full" />
            </div>
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.endDate')</label>
                <x-datepicker wire:model.live="endDate" class="mt-1 w-full" />
            </div>
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.locationOutlet')</label>
                <select wire:model.live="branchId" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">@lang('loyalty::app.allLocations')</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.transactionType')</label>
                <select wire:model.live="transactionType" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">@lang('loyalty::app.all')</option>
                    <option value="EARN">@lang('loyalty::app.earn')</option>
                    <option value="REDEEM">@lang('loyalty::app.redeem')</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.customer')</label>
                <select wire:model.live="customerId" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">@lang('loyalty::app.all')</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
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
                        @lang('loyalty::app.transactionDate')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.customer')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.orderId')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.transactionType')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.pointsIn')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.pointsOut')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.balanceAfter')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.pointsValue')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.source')
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.employee')
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($ledger as $entry)
                    @php
                        $pointsIn = $entry->points > 0 ? $entry->points : 0;
                        $pointsOut = $entry->points < 0 ? abs($entry->points) : 0;
                        $source = $entry->order_id ? __('loyalty::app.sourceOrder') : ($entry->type === 'EXPIRE' ? __('loyalty::app.sourceSystem') : __('loyalty::app.sourceAdmin'));
                        $employeeName = $entry->order?->addedBy?->name ?? $entry->order?->waiter?->name ?? __('loyalty::app.system');
                        $dateFormat = restaurant()->date_format ?? 'd-m-Y';
                        $timeFormat = restaurant()->time_format ?? timeFormat();
                        $displayDate = optional($entry->created_at)->timezone(timezone())->translatedFormat($dateFormat . ' ' . $timeFormat);
                        $pointsValue = round(((int) $entry->points) * ($valuePerPoint ?? 1), 2);
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $displayDate }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $entry->customer?->name ?? __('loyalty::app.unknownCustomer') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $entry->order?->order_number ? '#' . $entry->order->order_number : __('loyalty::app.notApplicable') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            @lang('loyalty::app.' . strtolower($entry->type))
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($pointsIn) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($pointsOut) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format((int) ($entry->balance_after ?? 0)) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ currency_format($pointsValue, restaurant()->currency_id) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $source }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $employeeName }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            @lang('loyalty::app.noLedgerEntries')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $ledger->links() }}
    </div>
</div>
