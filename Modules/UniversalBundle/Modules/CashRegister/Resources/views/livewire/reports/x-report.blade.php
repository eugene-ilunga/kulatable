<div class="mb-8">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="items-center justify-between block sm:flex ">
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <form class="ltr:sm:pr-3 rtl:sm:pl-3" action="#" method="GET">
                    <div class="lg:flex gap-2 items-center">
                        <x-select class="block w-fit" wire:model="dateRangeType" wire:change="setDateRange">
                            <option value="today">@lang('app.today')</option>
                            <option value="yesterday">@lang('app.yesterday')</option>
                            <option value="this_week">@lang('app.currentWeek')</option>
                            <option value="last_week">@lang('app.lastWeek')</option>
                            <option value="this_month">@lang('app.currentMonth')</option>
                            <option value="last_month">@lang('app.lastMonth')</option>
                            <option value="custom">@lang('cashregister::app.customRange')</option>
                        </x-select>

                        <div id="date-range-picker-x" date-rangepicker class="flex items-center w-full">
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-start-x" name="start" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.change='startDate' wire:change="setStartDate($event.target.value)" placeholder="@lang('app.selectStartDate')">
                            </div>
                            <span class="mx-4 text-gray-500">@lang('app.to')</span>
                            <div class="relative w-full">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                </div>
                                <input id="datepicker-range-end-x" name="end" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" wire:model.live='endDate' wire:change="setEndDate($event.target.value)" placeholder="@lang('app.selectEndDate')">
                            </div>
                        </div>
                    </div>
                </form>

                <div class="inline-flex gap-2 ml-0 lg:ml-3">
                    @if($sessions && count($sessions) > 0)
                        <x-select class="text-sm w-full" wire:model.live="selectedSessionId">
                            <option value="">@lang('cashregister::app.selectSession')</option>
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}">
                                    #{{ $s->id }} — {{ optional($s->opened_at)->timezone(timezone())->format('d M Y, h:i A') }} {{ $s->status === 'open' ? '(' . __('cashregister::app.openStatus') . ')' : '' }}
                                </option>
                            @endforeach
                        </x-select>
                    @endif
                    <x-select class="text-sm w-full" wire:model.live='branchId'>
                        <option value="">@lang('cashregister::app.allBranches')</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </x-select>
                    {{-- <x-select class="text-sm w-full" wire:model.live='registerId'>
                        <option value="">@lang('cashregister::app.allRegisters')</option>
                        @foreach($registers as $register)
                            <option value="{{ $register->id }}">{{ $register->name }}</option>
                        @endforeach
                    </x-select> --}}
                    @if(user_can('View Cash Register Reports'))
                        <x-select class="text-sm w-full" wire:model.live='cashierId'>
                            <option value="">@lang('cashregister::app.allCashiers')</option>
                            @foreach($cashiers as $cashier)
                                <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                            @endforeach
                        </x-select>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(user_can('View Cash Register Reports') && $sessions && count($sessions) > 0)
        @php
            $openSessions = collect($sessions)->where('status', 'open');
        @endphp
        @if($openSessions->count() > 0)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">@lang('cashregister::app.activeOpenRegisters')</h3>
            <div class="overflow-x-auto mb-2">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('cashregister::app.regsterName')</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('cashregister::app.branch')</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('cashregister::app.cashier')</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('cashregister::app.openedAtHeader')</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">@lang('cashregister::app.action')</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($openSessions as $s)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $s->register?->name ?? __('cashregister::app.unknown') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $s->branch?->name ?? __('cashregister::app.unknown') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $s->cashier?->name ?? __('cashregister::app.unknown') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ optional($s->opened_at)->timezone(timezone())->format('d M Y, h:i A') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                    <button wire:click="$set('selectedSessionId', {{ $s->id }})" onclick="window.scrollToXReport && window.scrollToXReport()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        @lang('cashregister::app.viewXReport')
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endif

    @if($reportData)
        <div id="x-report-section" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <!-- Report Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            @lang('cashregister::app.xReportHeader')
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            @lang('cashregister::app.generatedOn') {{ $reportData['generated_at']->timezone(timezone())->format('d M Y, h:i A') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.branch') {{ $reportData['session']->branch?->name ?? __('cashregister::app.unknown') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.register'): {{ $reportData['session']->register->name ?? __('cashregister::app.unknown') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @lang('cashregister::app.cashier'): {{ $reportData['session']->cashier->name ?? __('cashregister::app.unknown') }}
                        </p>
                        
                    </div>
                </div>
            </div>

            <!-- Report Data -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.metric')
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('cashregister::app.amount')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                @lang('cashregister::app.openingFloat')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                <span wire:loading.remove wire:target="selectedSessionId">
                                    {{ currency_format($reportData['opening_float'], restaurant()->currency_id) }}
                                </span>
                                <span wire:loading wire:target="selectedSessionId" class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                @lang('cashregister::app.cashSalesLabel')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                <span wire:loading.remove wire:target="selectedSessionId">
                                    {{ currency_format($reportData['cash_sales'], restaurant()->currency_id) }}
                                </span>
                                <span wire:loading wire:target="selectedSessionId" class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                </span>
                            </td>
                        </tr>
                        @if(!empty($reportData['payment_method_totals']))
                            @foreach($reportData['payment_method_totals'] as $method => $amount)
                                @continue($method === 'cash')
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ __('modules.order.' . $method) !== 'modules.order.' . $method ? __('modules.order.' . $method) : \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $method)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">
                                        <span wire:loading.remove wire:target="selectedSessionId">
                                            {{ currency_format((float) $amount, restaurant()->currency_id) }}
                                        </span>
                                        <span wire:loading wire:target="selectedSessionId" class="inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                @lang('cashregister::app.cashIn')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-right">
                                <span wire:loading.remove wire:target="selectedSessionId">
                                    {{ currency_format($reportData['cash_in'], restaurant()->currency_id) }}
                                </span>
                                <span wire:loading wire:target="selectedSessionId" class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                @lang('cashregister::app.cashOutLabel')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                <span wire:loading.remove wire:target="selectedSessionId">
                                    -{{ currency_format($reportData['cash_out'], restaurant()->currency_id) }}
                                </span>
                                <span wire:loading wire:target="selectedSessionId" class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                @lang('cashregister::app.safeDrops')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 text-right">
                                <span wire:loading.remove wire:target="selectedSessionId">
                                    -{{ currency_format($reportData['safe_drops'], restaurant()->currency_id) }}
                                </span>
                                <span wire:loading wire:target="selectedSessionId" class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                </span>
                            </td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                @lang('cashregister::app.expectedCash')
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">
                                <span wire:loading.remove wire:target="selectedSessionId">
                                    {{ currency_format($reportData['expected_cash'], restaurant()->currency_id) }}
                                </span>
                                <span wire:loading wire:target="selectedSessionId" class="inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-pulse"></span>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Session Status -->
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($reportData['session']->status === 'open')
                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            @else
                                <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                            @endif
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                @lang('cashregister::app.sessionStatus'): 
                                <span class="capitalize">@lang('app.' . $reportData['session']->status)</span>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                @lang('cashregister::app.shift'): {{ $reportData['session']->opened_at->timezone(timezone())->format('d M Y, h:i A') }} - 
                                {{ $reportData['session']->closed_at ? $reportData['session']->closed_at->timezone(timezone())->format('d M Y, h:i A') : __('cashregister::app.ongoing') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button wire:click="printReport" class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            @lang('cashregister::app.printReport')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">@lang('cashregister::app.noDataAvailable')</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @lang('cashregister::app.noCashRegisterSessions')
                </p>
            </div>
        </div>
    @endif
</div>

@script
<script>
    window.scrollToXReport = function () {
        setTimeout(() => {
            const section = document.getElementById('x-report-section');
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 200);
    };

    const datepickerEl1 = document.getElementById('datepicker-range-start-x');

    datepickerEl1.addEventListener('changeDate', (event) => {
        $wire.dispatch('setStartDate', { start: datepickerEl1.value });
    });

    const datepickerEl2 = document.getElementById('datepicker-range-end-x');

    datepickerEl2.addEventListener('changeDate', (event) => {
        $wire.dispatch('setEndDate', { end: datepickerEl2.value });
    });
    
    $wire.on('print_location', (url) => {
        if (!url) {
            return;
        }

        const isPWA = (window.matchMedia('(display-mode: standalone)').matches) ||
            (window.navigator.standalone === true) ||
            (document.referrer.includes('android-app://'));

        if (isPWA) {
            window.location.href = url;
        } else {
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.target = '_blank';
            anchor.click();
        }
    });
</script>
@endscript
