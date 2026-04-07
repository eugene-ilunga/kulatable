<div class="space-y-6">
    <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold">{{ __('webhooks::webhooks.overview') }}</h3>
                <p class="text-xs text-gray-500">{{ __('webhooks::webhooks.super_admin_only') }}</p>
            </div>
            <div class="w-64">
                <label class="block text-xs font-medium text-gray-700">{{ __('webhooks::webhooks.restaurant') }}</label>
                <select wire:model.live="selectedRestaurant" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($restaurants as $restaurant)
                        <option value="{{ $restaurant['id'] }}">{{ $restaurant['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <div class="p-4 rounded-lg bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200">
                <p class="text-xs text-blue-600 font-medium">{{ __('webhooks::webhooks.webhooks') }}</p>
                <p class="text-2xl font-bold text-blue-700">{{ $summary['webhooks'] ?? 0 }}</p>
            </div>
            <div class="p-4 rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200">
                <p class="text-xs text-gray-600 font-medium">{{ __('webhooks::webhooks.deliveries') }}</p>
                <p class="text-2xl font-bold text-gray-700">{{ $summary['deliveries'] ?? 0 }}</p>
            </div>
            <div class="p-4 rounded-lg bg-gradient-to-br from-red-50 to-red-100 border border-red-200">
                <p class="text-xs text-red-600 font-medium">{{ __('webhooks::webhooks.failed') }}</p>
                <p class="text-2xl font-bold text-red-700">{{ $summary['failed'] ?? 0 }}</p>
            </div>
            <div class="p-4 rounded-lg bg-gradient-to-br from-yellow-50 to-yellow-100 border border-yellow-200">
                <p class="text-xs text-yellow-600 font-medium">{{ __('webhooks::webhooks.pending') }}</p>
                <p class="text-2xl font-bold text-yellow-700">{{ $summary['pending'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold">{{ __('webhooks::webhooks.recent_deliveries') }}</h3>
            <span class="text-xs text-gray-500">{{ __('webhooks::webhooks.latest_for_tenant', ['count' => 15]) }}</span>
        </div>
        <div class="p-4">
            @if (count($deliveries) === 0)
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">{{ __('webhooks::webhooks.no_deliveries') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 font-medium text-gray-500">{{ __('webhooks::webhooks.event') }}</th>
                                <th class="px-3 py-2 font-medium text-gray-500">{{ __('webhooks::webhooks.status') }}</th>
                                <th class="px-3 py-2 font-medium text-gray-500">{{ __('webhooks::webhooks.attempts') }}</th>
                                <th class="px-3 py-2 font-medium text-gray-500">{{ __('webhooks::webhooks.response') }}</th>
                                <th class="px-3 py-2 font-medium text-gray-500">{{ __('webhooks::webhooks.duration_ms') }}</th>
                                <th class="px-3 py-2 font-medium text-gray-500">{{ __('webhooks::webhooks.created_at') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">{{ __('webhooks::webhooks.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($deliveries as $delivery)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $delivery['event'] }}</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                                            @if($delivery['status'] === 'succeeded') bg-green-100 text-green-700
                                            @elseif($delivery['status'] === 'pending') bg-yellow-100 text-yellow-700
                                            @else bg-red-100 text-red-700 @endif">
                                            {{ __('webhooks::webhooks.status_' . $delivery['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $delivery['attempts'] }}</td>
                                    <td class="px-3 py-2">
                                        @if($delivery['response_code'])
                                            <span class="font-mono text-xs {{ $delivery['response_code'] >= 200 && $delivery['response_code'] < 300 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $delivery['response_code'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">--</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $delivery['duration_ms'] ?? '--' }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ \Illuminate\Support\Carbon::parse($delivery['created_at'])->diffForHumans() }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" 
                                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors" 
                                            x-on:click="$dispatch('show-delivery', { id: {{ $delivery['id'] }} })">
                                            {{ __('webhooks::webhooks.view') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @livewire('webhooks::super-admin.delivery-detail')
</div>
