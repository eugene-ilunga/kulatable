<div>
    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
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
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2 xl:grid-cols-3">
        <div class="relative overflow-hidden p-5 rounded-2xl border border-indigo-100 dark:border-indigo-800 bg-gradient-to-br from-indigo-50 via-white to-white dark:from-indigo-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-indigo-200/40 dark:bg-indigo-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-indigo-700 dark:text-indigo-200">@lang('loyalty::app.totalLoyaltyMembers')</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['total_members']) }}</p>
        </div>

        <div class="relative overflow-hidden p-5 rounded-2xl border border-indigo-100 dark:border-indigo-800 bg-gradient-to-br from-indigo-50 via-white to-white dark:from-indigo-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-indigo-200/40 dark:bg-indigo-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-indigo-700 dark:text-indigo-200">@lang('loyalty::app.activeMembers')</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['active_members']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">@lang('loyalty::app.activeMembersLast30Days')</p>
        </div>

        <div class="relative overflow-hidden p-5 rounded-2xl border border-emerald-100 dark:border-emerald-800 bg-gradient-to-br from-emerald-50 via-white to-white dark:from-emerald-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-emerald-200/40 dark:bg-emerald-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-200">@lang('loyalty::app.revenueFromLoyaltyMembers')</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ currency_format($stats['loyalty_revenue'], restaurant()->currency_id) }}</p>
        </div>

        <div class="relative overflow-hidden p-5 rounded-2xl border border-amber-100 dark:border-amber-800 bg-gradient-to-br from-amber-50 via-white to-white dark:from-amber-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-amber-200/40 dark:bg-amber-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-200">@lang('loyalty::app.pointsIssued')</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['points_issued']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">@lang('loyalty::app.pointsRedeemed'): {{ number_format($stats['points_redeemed']) }}</p>
        </div>

        <div class="relative overflow-hidden p-5 rounded-2xl border border-rose-100 dark:border-rose-800 bg-gradient-to-br from-rose-50 via-white to-white dark:from-rose-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-rose-200/40 dark:bg-rose-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-200">@lang('loyalty::app.stampsIssued')</p>
            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($stats['stamps_issued']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">@lang('loyalty::app.rewardsRedeemed'): {{ number_format($stats['rewards_redeemed']) }}</p>
        </div>

        <div class="relative overflow-hidden p-5 rounded-2xl border border-sky-100 dark:border-sky-800 bg-gradient-to-br from-sky-50 via-white to-white dark:from-sky-900/30 dark:via-gray-800 dark:to-gray-800 shadow-sm">
            <div class="absolute -top-8 -right-8 h-20 w-20 rounded-full bg-sky-200/40 dark:bg-sky-600/20"></div>
            <p class="text-xs uppercase tracking-wide text-sky-800 dark:text-white">@lang('loyalty::app.repeatOrderRate')</p>
            <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 space-y-1">
                <div class="flex justify-between">
                    <span>@lang('loyalty::app.members')</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['repeat_rate_members'], 2) }}%</span>
                </div>
                <div class="flex justify-between">
                    <span>@lang('loyalty::app.nonMembers')</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($stats['repeat_rate_non_members'], 2) }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>
