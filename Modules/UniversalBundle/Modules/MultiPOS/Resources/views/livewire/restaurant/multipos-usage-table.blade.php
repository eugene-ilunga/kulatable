<div class="mb-12">
    <div class="mb-4 px-4 flex items-center justify-between">
        <h3 class="text-xl font-semibold dark:text-white">{{ __('multipos::messages.usage.title', ['monthYear' => Carbon\Carbon::create($selectedYear, $selectedMonth)->format('F Y')]) }}</h3>
        
        <!-- Month/Year Filter -->
        <div class="flex items-center gap-3">
            <select class="w-40 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" wire:model.live="selectedMonth">
                @foreach(range(1, 12) as $month)
                    <option value="{{ $month }}" @if($selectedMonth == $month) selected @endif>{{ Carbon\Carbon::create(null, $month)->format('F') }}</option>
                @endforeach
            </select>
            
            <select class="w-32 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" wire:model.live="selectedYear">
                @foreach(range(now()->year, now()->year - 5) as $year)
                    <option value="{{ $year }}" @if($selectedYear == $year) selected @endif>{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800 dark:text-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.no') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.alias') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.branch') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.machine_id') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.orders_current_month') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.revenue_current_month') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                            {{ __('multipos::messages.usage.headers.last_seen') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse($machines as $index => $machine)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $loop->iteration }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $machine->alias ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $machine->branch->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $machine->public_id }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($machine->status === 'active')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Active</span>
                            @elseif($machine->status === 'pending')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">Pending</span>
                            @elseif($machine->status === 'declined')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">Declined</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $machine->orders_count ?? 0 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            @if($restaurant && $restaurant->currency)
                                {{ currency_format($machine->total_revenue ?? 0, $restaurant->currency_id) }}
                            @else
                                {{ $machine->total_revenue ?? 0 }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ $machine->last_seen_at ? $machine->last_seen_at->diffForHumans() : __('multipos::messages.table.never') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-sm text-center text-gray-500 dark:text-gray-400">
                            {{ __('multipos::messages.usage.no_data', ['monthYear' => Carbon\Carbon::create($selectedYear, $selectedMonth)->format('F Y')]) }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

