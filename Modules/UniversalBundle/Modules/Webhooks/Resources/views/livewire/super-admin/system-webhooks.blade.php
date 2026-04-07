<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ __('webhooks::webhooks.system_webhooks') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ __('webhooks::webhooks.system_webhooks_description') }}</p>
        </div>
        <button wire:click="openCreateModal"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
            <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            {{ __('webhooks::webhooks.add_system_webhook') }}
        </button>
    </div>

    {{-- Webhooks List --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($webhooks->isEmpty())
            <div class="text-center py-12 px-4">
                <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">{{ __('webhooks::webhooks.no_webhooks') }}</h3>
                <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">{{ __('webhooks::webhooks.no_webhooks_description') }}</p>
                <button wire:click="openCreateModal" class="mt-6 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                    {{ __('webhooks::webhooks.get_started') }}
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('webhooks::webhooks.name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('webhooks::webhooks.target_url') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('webhooks::webhooks.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('webhooks::webhooks.subscribed_events') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('webhooks::webhooks.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($webhooks as $webhook)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $webhook->name }}</div>
                                    @if($webhook->description)
                                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ $webhook->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-gray-600 truncate max-w-xs block">{{ $webhook->target_url }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="toggleActive({{ $webhook->id }})"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium transition-colors
                                        {{ $webhook->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        <span class="w-2 h-2 rounded-full ltr:mr-1.5 rtl:ml-1.5 {{ $webhook->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                        {{ $webhook->is_active ? __('webhooks::webhooks.is_active') : __('webhooks::webhooks.status_disabled') }}
                                    </button>
                                </td>
                                <td class="px-6 py-4">
                                    @if(empty($webhook->subscribed_events))
                                        <span class="text-sm text-gray-500">{{ __('webhooks::webhooks.all_events') }}</span>
                                    @else
                                        <span class="text-sm text-gray-600">{{ count($webhook->subscribed_events) }} {{ __('webhooks::webhooks.event') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 rtl:space-x-reverse">
                                    <button wire:click="sendTest({{ $webhook->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                        {{ __('webhooks::webhooks.test') }}
                                    </button>
                                    <button wire:click="openEditModal({{ $webhook->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                        {{ __('webhooks::webhooks.edit') }}
                                    </button>
                                    <button wire:click="confirmDelete({{ $webhook->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-red-200 rounded-md text-xs text-red-600 bg-white hover:bg-red-50 transition-colors">
                                        {{ __('webhooks::webhooks.delete') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $webhooks->links() }}
            </div>
        @endif
    </div>

    {{-- Recent Deliveries --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ __('webhooks::webhooks.recent_deliveries') }}</h3>
            <span class="text-xs text-gray-500">{{ __('webhooks::webhooks.latest_count', ['count' => 10]) }}</span>
        </div>
        @if($recentDeliveries->isEmpty())
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="mt-2 text-sm text-gray-600">{{ __('webhooks::webhooks.no_deliveries') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('webhooks::webhooks.event') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('webhooks::webhooks.restaurant') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('webhooks::webhooks.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('webhooks::webhooks.response') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('webhooks::webhooks.created_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentDeliveries as $delivery)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $delivery->event }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $delivery->restaurant_id ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium
                                        @if($delivery->status === 'succeeded') bg-green-100 text-green-700
                                        @elseif($delivery->status === 'pending') bg-yellow-100 text-yellow-700
                                        @else bg-red-100 text-red-700 @endif">
                                        {{ __('webhooks::webhooks.status_' . $delivery->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($delivery->response_code)
                                        <span class="font-mono text-xs {{ $delivery->response_code >= 200 && $delivery->response_code < 300 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $delivery->response_code }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $delivery->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeCreateModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <form wire:submit="create">
                        <div class="bg-white px-6 py-5">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('webhooks::webhooks.create_webhook') }}</h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.name') }} *</label>
                                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.description') }}</label>
                                    <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.target_url') }} *</label>
                                    <input type="url" wire:model="target_url" placeholder="https://..." class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('target_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.secret') }}</label>
                                    <input type="text" wire:model="secret" placeholder="{{ __('webhooks::webhooks.secret_hint') }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.max_attempts') }}</label>
                                        <input type="number" wire:model="max_attempts" min="1" max="10" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.backoff_seconds') }}</label>
                                        <input type="number" wire:model="backoff_seconds" min="5" max="3600" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('webhooks::webhooks.subscribed_events') }}</label>
                                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto bg-gray-50 rounded-lg p-3">
                                        @foreach($availableEvents as $event)
                                            <label class="flex items-center">
                                                <input type="checkbox" wire:model="subscribed_events" value="{{ $event }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="ml-2 text-sm text-gray-700 font-mono">{{ $event }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('webhooks::webhooks.all_events') }} (if none selected)</p>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <label class="ml-2 text-sm text-gray-700">{{ __('webhooks::webhooks.is_active') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                {{ __('webhooks::webhooks.cancel') }}
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700">
                                {{ __('webhooks::webhooks.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEditModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <form wire:submit="update">
                        <div class="bg-white px-6 py-5">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('webhooks::webhooks.edit_webhook') }}</h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.name') }} *</label>
                                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.description') }}</label>
                                    <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.target_url') }} *</label>
                                    <input type="url" wire:model="target_url" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('target_url') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.secret') }}</label>
                                    <input type="text" wire:model="secret" placeholder="Leave empty to keep current" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.max_attempts') }}</label>
                                        <input type="number" wire:model="max_attempts" min="1" max="10" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">{{ __('webhooks::webhooks.backoff_seconds') }}</label>
                                        <input type="number" wire:model="backoff_seconds" min="5" max="3600" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('webhooks::webhooks.subscribed_events') }}</label>
                                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto bg-gray-50 rounded-lg p-3">
                                        @foreach($availableEvents as $event)
                                            <label class="flex items-center">
                                                <input type="checkbox" wire:model="subscribed_events" value="{{ $event }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="ml-2 text-sm text-gray-700 font-mono">{{ $event }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <label class="ml-2 text-sm text-gray-700">{{ __('webhooks::webhooks.is_active') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                {{ __('webhooks::webhooks.cancel') }}
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700">
                                {{ __('webhooks::webhooks.save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cancelDelete"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white px-6 py-5">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 text-center">{{ __('webhooks::webhooks.confirm_delete') }}</h3>
                        <p class="mt-2 text-sm text-gray-500 text-center">{{ __('webhooks::webhooks.confirm_delete_message') }}</p>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                        <button type="button" wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            {{ __('webhooks::webhooks.cancel') }}
                        </button>
                        <button type="button" wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700">
                            {{ __('webhooks::webhooks.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
