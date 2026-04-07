@extends('layouts.app')

@section('content')


<div class="p-6">
    <div class="mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ __('multipos::messages.reports.title') }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ __('multipos::messages.dashboard.branch_label') }} {{ $currentBranch->name }}</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 mt-4">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('multipos::messages.reports.cards.total_machines') }}</dt>
                                <dd class="text-lg font-medium text-gray-900" data-card="total_machines">{{ count($reportData) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('multipos::messages.reports.cards.total_orders') }}</dt>
                                <dd class="text-lg font-medium text-gray-900" data-card="total_orders">{{ $reportData->sum('total_orders') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('multipos::messages.reports.cards.net_sales') }}</dt>
                                <dd class="text-lg font-medium text-gray-900" data-card="net_sales">${{ number_format($reportData->sum('net_sales'), 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('multipos::messages.reports.cards.avg_order_value') }}</dt>
                                <dd class="text-lg font-medium text-gray-900" data-card="avg_order_value">${{ number_format($reportData->avg('avg_order_value'), 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="flex flex-wrap justify-between items-center gap-4 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="lg:flex items-center mb-4 sm:mb-0">
                <div class="lg:flex gap-2 items-center">
                    <select id="dateRangeType" class="block w-full sm:w-fit mb-2 lg:mb-0 border border-gray-300 rounded-lg" onchange="updateDateRange(); loadReportData();">
                        <option value="today" {{ ($selectedRange ?? 'currentWeek')=='today' ? 'selected' : '' }}>@lang('app.today')</option>
                        <option value="currentWeek" {{ ($selectedRange ?? 'currentWeek')=='currentWeek' ? 'selected' : '' }}>@lang('app.currentWeek')</option>
                        <option value="lastWeek" {{ ($selectedRange ?? 'currentWeek')=='lastWeek' ? 'selected' : '' }}>@lang('app.lastWeek')</option>
                        <option value="last7Days" {{ ($selectedRange ?? 'currentWeek')=='last7Days' ? 'selected' : '' }}>@lang('app.last7Days')</option>
                        <option value="currentMonth" {{ ($selectedRange ?? 'currentWeek')=='currentMonth' ? 'selected' : '' }}>@lang('app.currentMonth')</option>
                        <option value="lastMonth" {{ ($selectedRange ?? 'currentWeek')=='lastMonth' ? 'selected' : '' }}>@lang('app.lastMonth')</option>
                        <option value="currentYear" {{ ($selectedRange ?? 'currentWeek')=='currentYear' ? 'selected' : '' }}>@lang('app.currentYear')</option>
                        <option value="lastYear" {{ ($selectedRange ?? 'currentWeek')=='lastYear' ? 'selected' : '' }}>@lang('app.lastYear')</option>
                        <option value="custom" {{ ($selectedRange ?? 'currentWeek')=='custom' ? 'selected' : '' }}>@lang('multipos::messages.reports.customDateRange')</option>
                    </select>

                    <div class="lg:flex items-center gap-2">
                        <div class="w-full">
                            <label for="start-date" class="sr-only">@lang('app.selectStartDate')</label>
                            <input id="start-date" type="date" value="{{ $startDate }}" onchange="document.getElementById('dateRangeType').value='custom'; loadReportData();" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>
                        <span class="mx-2 text-gray-500 dark:text-gray-100 w-10 text-center">@lang('app.to')</span>
                        <div class="w-full">
                            <label for="end-date" class="sr-only">@lang('app.selectEndDate')</label>
                            <input id="end-date" type="date" value="{{ $endDate }}" onchange="document.getElementById('dateRangeType').value='custom'; loadReportData();" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>
                    </div>

                    <div class="lg:flex items-center gap-2 ms-2">
                        <div class="w-full max-w-[7rem]">
                            <label for="start-time" class="sr-only">@lang('modules.reservation.timeStart'):</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 end-0 top-0 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" width="24" height="24" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                                </div>
                                <input id="start-time" type="time" value="{{ $startTime }}" onchange="document.getElementById('dateRangeType').value='custom'; debounceLoadReport();" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                            </div>
                        </div>
                        <span class="mx-2 text-gray-500 dark:text-gray-100 w-10 text-center">@lang('app.to')</span>
                        <div class="w-full max-w-[7rem]">
                            <label for="end-time" class="sr-only">@lang('modules.reservation.timeEnd'):</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 end-0 top-0 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" width="24" height="24" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 7.5a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0m7 0V3h1v4.293l2.854 2.853-.708.708-3-3A.5.5 0 0 1 7 7.5" fill="currentColor"/></svg>
                                </div>
                                <input id="end-time" type="time" value="{{ $endTime }}" onchange="document.getElementById('dateRangeType').value='custom'; debounceLoadReport();" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="inline-flex items-center gap-2 w-1/2 sm:w-auto ms-2">
                <a id="export-link" href="{{ route('multi-pos.reports.export-csv', ['branch_id' => $currentBranch->id, 'dateRangeType' => $selectedRange ?? 'currentWeek', 'start' => $startDate, 'end' => $endDate, 'start_time' => $startTime, 'end_time' => $endTime]) }}"
                class="inline-flex items-center  w-1/2 px-3 py-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 sm:w-auto dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                    <svg class="w-5 h-5 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6 2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.414A2 2 0 0 0 15.414 6L12 2.586A2 2 0 0 0 10.586 2zm5 6a1 1 0 1 0-2 0v3.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l3 3a1 1 0 0 0 1.414 0l3-3a1 1 0 0 0-1.414-1.414L11 11.586z" clip-rule="evenodd"/></svg>
                    @lang('app.export')
                </a>
            </div>
        </div>
    </div>

    <!-- Machine Sales Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('multipos::messages.reports.table.title') }}</h3>

            <div id="report-table-container">
                @if(count($reportData) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.pos_machine') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.orders') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.net_sales') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.avg_order') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.cash_sales') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.card_upi_sales') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="report-table-body">
                                @foreach($reportData as $row)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $row['machine_alias'] }}</div>
                                            <div class="text-sm text-gray-500">{{ $row['machine_public_id'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $row['total_orders'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($row['net_sales'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($row['avg_order_value'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($row['cash_sales'], 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ${{ number_format($row['card_upi_sales'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('multipos::messages.reports.empty.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('multipos::messages.reports.empty.hint') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
let debounceTimer;
const branchId = {{ $currentBranch->id }};

function debounceLoadReport() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadReportData, 500);
}

function updateDateRange() {
    const dateRangeType = document.getElementById('dateRangeType').value;
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const today = new Date();
    let startDate, endDate;

    switch(dateRangeType) {
        case 'today':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = endDate = yesterday.toISOString().split('T')[0];
            break;
        case 'currentWeek':
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            startDate = startOfWeek.toISOString().split('T')[0];
            endDate = endOfWeek.toISOString().split('T')[0];
            break;
        case 'lastWeek':
            const lastWeekStart = new Date(today);
            lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
            const lastWeekEnd = new Date(lastWeekStart);
            lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
            startDate = lastWeekStart.toISOString().split('T')[0];
            endDate = lastWeekEnd.toISOString().split('T')[0];
            break;
        case 'last7Days':
            const sevenDaysAgo = new Date(today);
            sevenDaysAgo.setDate(today.getDate() - 6);
            startDate = sevenDaysAgo.toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
        case 'currentMonth':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
        case 'lastMonth':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            startDate = lastMonth.toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
            break;
        case 'currentYear':
            startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
            break;
        case 'lastYear':
            const lastYear = today.getFullYear() - 1;
            startDate = new Date(lastYear, 0, 1).toISOString().split('T')[0];
            endDate = new Date(lastYear, 11, 31).toISOString().split('T')[0];
            break;
        default:
            return; // custom - don't update dates
    }

    if (startDate && endDate) {
        startDateInput.value = startDate;
        endDateInput.value = endDate;
    }
}

function loadReportData() {
    const dateRangeType = document.getElementById('dateRangeType').value;
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const startTime = document.getElementById('start-time').value || '00:00';
    const endTime = document.getElementById('end-time').value || '23:59';

    const params = new URLSearchParams({
        dateRangeType: dateRangeType,
        start: startDate,
        end: endDate,
        start_time: startTime,
        end_time: endTime
    });

    fetch('{{ route("multi-pos.reports.get-report-data") }}?' + params.toString())
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Update date inputs if server returned different dates
            if (data.startDate) document.getElementById('start-date').value = data.startDate;
            if (data.endDate) document.getElementById('end-date').value = data.endDate;
            if (data.startTime) document.getElementById('start-time').value = data.startTime;
            if (data.endTime) document.getElementById('end-time').value = data.endTime;

            // Update summary cards
            document.querySelector('[data-card="total_machines"]').textContent = data.summary.total_machines;
            document.querySelector('[data-card="total_orders"]').textContent = data.summary.total_orders;
            document.querySelector('[data-card="net_sales"]').textContent = '$' + parseFloat(data.summary.net_sales || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.querySelector('[data-card="avg_order_value"]').textContent = '$' + parseFloat(data.summary.avg_order_value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Update table
            const container = document.getElementById('report-table-container');

            if (data.reportData.length > 0) {
                container.innerHTML = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.pos_machine') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.orders') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.net_sales') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.avg_order') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.cash_sales') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.reports.table.card_upi_sales') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="report-table-body">
                                ${data.reportData.map(row => `
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">${(row.machine_alias && row.machine_alias.trim()) ? row.machine_alias : {!! json_encode(__('multipos::messages.js.unnamed')) !!}}</div>
                                            <div class="text-sm text-gray-500">${row.machine_public_id || ''}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${row.total_orders}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$${parseFloat(row.net_sales).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$${parseFloat(row.avg_order_value || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$${parseFloat(row.cash_sales || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$${parseFloat(row.card_upi_sales || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('multipos::messages.reports.empty.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('multipos::messages.reports.empty.hint') }}</p>
                    </div>
                `;
            }

            // Update export link
            const exportParams = new URLSearchParams({
                branch_id: branchId,
                dateRangeType: data.selectedRange,
                start: data.startDate,
                end: data.endDate,
                start_time: data.startTime,
                end_time: data.endTime
            });
            document.getElementById('export-link').href = '{{ route("multi-pos.reports.export-csv") }}?' + exportParams.toString();
        })
        .catch(error => {
            console.error('Error loading report data:', error);
            alert({!! json_encode(__('multipos::messages.js.error_loading_report')) !!});
        });
}
</script>
@endsection

