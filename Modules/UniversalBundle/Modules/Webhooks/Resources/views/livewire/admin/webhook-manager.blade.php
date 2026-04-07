<div class="space-y-6">
    @if ($accessError)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            {{ $accessError }}
        </div>
    @else
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between" id="webhook-form">
            <div>
                @if($editingId)
                    <h3 class="font-semibold text-lg flex items-center space-x-2 rtl:space-x-reverse">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>{{ __('Edit Webhook') }}</span>
                    </h3>
                    <p class="text-xs text-gray-500">{{ __('Update webhook configuration') }}</p>
                @else
                    <h3 class="font-semibold text-lg">{{ __('webhooks::webhooks.create_webhook') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('webhooks::webhooks.create_webhook_subtitle') }}</p>
                @endif
            </div>
            <div class="flex space-x-2 rtl:space-x-reverse">
                @if($editingId)
                    <button type="button" wire:click="cancelEdit" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-1 transition-colors">
                        <svg class="h-4 w-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="save" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                        <svg class="h-4 w-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('Update') }}
                    </button>
                @else
                    <button type="button" wire:click="resetForm" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                        {{ __('webhooks::webhooks.reset') }}
                    </button>
                    <button type="button" wire:click="save" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                        {{ __('webhooks::webhooks.save') }}
                    </button>
                @endif
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('webhooks::webhooks.name') }}</label>
                    <input wire:model.defer="name" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. POS Orders Integration">
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('webhooks::webhooks.target_url') }}</label>
                    <input wire:model.defer="target_url" type="url" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="https://example.com/webhook">
                    @error('target_url') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('webhooks::webhooks.secret') }}</label>
                    <input wire:model.defer="secret" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="{{ __('webhooks::webhooks.leave_blank_auto') }}">
                    @error('secret') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('webhooks::webhooks.max_attempts') }}</label>
                        <input wire:model.defer="max_attempts" type="number" min="1" max="10" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('max_attempts') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('webhooks::webhooks.backoff_seconds') }}</label>
                        <input wire:model.defer="backoff_seconds" type="number" min="5" max="3600" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('backoff_seconds') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <input wire:model.defer="is_active" id="is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="is_active" class="text-sm text-gray-700">{{ __('webhooks::webhooks.is_active') }}</label>
                </div>
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <input wire:model.defer="redact_payload" id="redact_payload" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="redact_payload" class="text-sm text-gray-700">{{ __('webhooks::webhooks.redact_payload') }}</label>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-700">
                    {{ __('Webhooks created here apply only to the current branch.') }}
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('webhooks::webhooks.subscribed_events') }}</label>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-48 overflow-y-auto border border-gray-200 grid grid-cols-1 gap-2">
                        @foreach (['order.created','order.updated','reservation.received','reservation.confirmed','kot.updated'] as $event)
                            <label class="inline-flex items-center space-x-2 rtl:space-x-reverse cursor-pointer">
                                <input type="checkbox" wire:model.defer="subscribed_events" value="{{ $event }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700 font-mono">{{ $event }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('subscribed_events.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('webhooks::webhooks.source_modules') }}</label>
                    <div class="bg-gray-50 rounded-lg p-3 max-h-48 overflow-y-auto border border-gray-200 grid grid-cols-2 gap-2">
                        @foreach (['Order','Reservation','Kitchen','Inventory'] as $module)
                            <label class="inline-flex items-center space-x-2 rtl:space-x-reverse cursor-pointer">
                                <input type="checkbox" wire:model.defer="source_modules" value="{{ $module }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">{{ $module }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('source_modules.*') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold">{{ __('webhooks::webhooks.configured_webhooks') }}</h3>
            <button type="button" wire:click="refreshData" class="text-xs text-gray-500 hover:text-indigo-600 transition-colors">{{ __('webhooks::webhooks.refresh') }}</button>
        </div>
        <div class="p-4">
            @if (count($webhooks) === 0)
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">{{ __('webhooks::webhooks.no_webhooks_yet') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.name') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.target_url') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.branch') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.subscribed_events') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.status') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('webhooks::webhooks.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($webhooks as $hook)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $hook['name'] }}</td>
                                    <td class="px-4 py-3 truncate max-w-xs text-gray-500 font-mono text-xs">{{ $hook['target_url'] }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $hook['branch_id'] ? '#'.$hook['branch_id'] : __('webhooks::webhooks.all_branches') }}</td>
                                    <td class="px-4 py-3">
                                        @if (!empty($hook['subscribed_events']))
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-indigo-50 text-xs text-indigo-700 border border-indigo-100">
                                                {{ count($hook['subscribed_events']) }} {{ __('webhooks::webhooks.event') }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-500">{{ __('webhooks::webhooks.all_events') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $hook['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            <span class="w-1.5 h-1.5 rounded-full ltr:mr-1.5 rtl:ml-1.5 {{ $hook['is_active'] ? 'bg-green-500' : 'bg-gray-500' }}"></span>
                                            {{ $hook['is_active'] ? __('webhooks::webhooks.is_active') : __('webhooks::webhooks.status_disabled') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2 rtl:space-x-reverse">
                                        <button type="button" wire:click="viewDetails({{ $hook['id'] }})" class="text-blue-600 hover:text-blue-900 text-xs font-medium">
                                            <svg class="inline h-3.5 w-3.5 ltr:mr-0.5 rtl:ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            {{ __('View') }}
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" wire:click="edit({{ $hook['id'] }})" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">
                                            <svg class="inline h-3.5 w-3.5 ltr:mr-0.5 rtl:ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            {{ __('Edit') }}
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" wire:click="sendTest({{ $hook['id'] }})" class="text-green-600 hover:text-green-900 text-xs font-medium">
                                            <svg class="inline h-3.5 w-3.5 ltr:mr-0.5 rtl:ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                            {{ __('webhooks::webhooks.test') }}
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" wire:click="delete({{ $hook['id'] }})" class="text-red-600 hover:text-red-900 text-xs font-medium">
                                            <svg class="inline h-3.5 w-3.5 ltr:mr-0.5 rtl:ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            {{ __('webhooks::webhooks.delete') }}
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

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold">{{ __('webhooks::webhooks.recent_deliveries') }}</h3>
                <span class="text-xs text-gray-500">{{ __('webhooks::webhooks.latest_count', ['count' => 10]) }}</span>
            </div>
        </div>
        <div class="p-4">
            @if (count($recentDeliveries) === 0)
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">{{ __('webhooks::webhooks.no_deliveries') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider text-xs">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.event') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.status') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.attempts') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.response') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.duration_ms') }}</th>
                                <th class="px-4 py-3 font-medium">{{ __('webhooks::webhooks.created_at') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('webhooks::webhooks.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($recentDeliveries as $delivery)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-mono font-medium">{{ $delivery['event'] }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                                            @if($delivery['status'] === 'succeeded') bg-green-100 text-green-700
                                            @elseif($delivery['status'] === 'pending') bg-yellow-100 text-yellow-700
                                            @else bg-red-100 text-red-700 @endif">
                                            {{ __('webhooks::webhooks.status_' . $delivery['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $delivery['attempts'] }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $delivery['response_code'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $delivery['duration_ms'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ \Illuminate\Support\Carbon::parse($delivery['created_at'])->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-right space-x-2 rtl:space-x-reverse">
                                        <button type="button" wire:click="$dispatch('show-payload', { id: {{ $delivery['id'] }} })" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium">
                                            {{ __('webhooks::webhooks.view') }}
                                        </button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" wire:click="replay({{ $delivery['id'] }})" class="text-gray-600 hover:text-gray-900 text-xs font-medium">
                                            {{ __('webhooks::webhooks.replay') }}
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

    @include('webhooks::livewire.admin.components.payload-modal')
    @include('webhooks::livewire.admin.components.webhook-details-modal')
    @endif
</div>

<script>
    // Scroll to form when editing
    window.addEventListener('scroll-to-form', () => {
        document.getElementById('webhook-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
</script>
