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
                <label class="text-sm text-gray-600 dark:text-gray-300">@lang('loyalty::app.asOfDate')</label>
                <x-datepicker wire:model.live="asOfDate" class="mt-1 w-full" />
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
            <div class="flex-1 flex justify-end">
                <x-button wire:click="exportReport" type="button">
                    @lang('loyalty::app.exportExcel')
                </x-button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2">
        <div class="relative overflow-hidden p-5 rounded-2xl border border-amber-100 dark:border-amber-800 bg-gradient-to-br from-amber-50 via-white to-white dark:from-amber-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-amber-200/40 dark:bg-amber-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-200">@lang('loyalty::app.totalOutstandingPoints')</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($totals['points']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">@lang('loyalty::app.asOfDate'): {{ $asOfDate }}</p>
        </div>
        <div class="relative overflow-hidden p-5 rounded-2xl border border-emerald-100 dark:border-emerald-800 bg-gradient-to-br from-emerald-50 via-white to-white dark:from-emerald-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-emerald-200/40 dark:bg-emerald-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-200">@lang('loyalty::app.totalOutstandingValue')</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ currency_format($totals['value'], restaurant()->currency_id) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">@lang('loyalty::app.asOfDate'): {{ $asOfDate }}</p>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.customer')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.outstandingPoints')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.outstandingValue')
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($rows as $account)
                    @php
                        $value = round(((int) $account->points_balance) * ($valuePerPoint ?? 1), 2);
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $account->customer?->name ?? __('loyalty::app.unknownCustomer') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($account->points_balance) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ currency_format($value, restaurant()->currency_id) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            @lang('loyalty::app.noLiabilityFound')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $rows->links() }}
    </div>
</div>
