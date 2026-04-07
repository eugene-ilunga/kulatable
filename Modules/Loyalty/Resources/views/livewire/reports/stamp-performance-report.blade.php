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
                        @lang('loyalty::app.stampCampaign')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.customersEnrolled')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.stampsIssued')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.cardsCompleted')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.rewardsIssued')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.completionRate')
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        @lang('loyalty::app.dropOffRate')
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($rows as $row)
                    @php
                        $stampsRequired = max(1, (int) ($row->stamps_required ?? 1));
                        $stampsRedeemed = (int) ($row->stamps_redeemed ?? 0);
                        $cardsCompleted = (int) floor($stampsRedeemed / $stampsRequired);
                        $rewardsIssued = $cardsCompleted;
                        $customersEnrolled = (int) ($row->customers_enrolled ?? 0);
                        $rawCompletion = $customersEnrolled > 0 ? (($cardsCompleted / $customersEnrolled) * 100) : 0;
                        $completionRate = round(min(100, max(0, $rawCompletion)), 2);
                        $dropOffRate = round(max(0, 100 - $completionRate), 2);
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $row->campaign_name ?? __('loyalty::app.notApplicable') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($customersEnrolled) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format((int) ($row->stamps_issued ?? 0)) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($cardsCompleted) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($rewardsIssued) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($completionRate, 2) }}%</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">{{ number_format($dropOffRate, 2) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            @lang('loyalty::app.noStampPerformanceFound')
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
