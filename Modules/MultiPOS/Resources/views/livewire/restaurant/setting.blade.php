<div class="p-6 mx-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
    <div class="mb-6">
        <h3 class="text-xl font-semibold dark:text-white">{{ __('multipos::messages.settings_title') }}</h3>
        <x-help-text class="mb-6">{{ __('multipos::messages.help_text') }}</x-help-text>
    </div>

    <!-- Information Sections -->
    <div class="space-y-6 mb-6">
        <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('multipos::messages.info.registration_title') }}</h4>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('multipos::messages.info.registration_text') }}</p>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('multipos::messages.info.status_title') }}</h4>
            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <li><span class="font-medium text-green-600 dark:text-green-400">{{ __('multipos::messages.info.status_active') }}</span> {{ __('multipos::messages.info.status_active_text') }}</li>
                <li><span class="font-medium text-yellow-600 dark:text-yellow-400">{{ __('multipos::messages.info.status_pending') }}</span> {{ __('multipos::messages.info.status_pending_text') }}</li>
                <li><span class="font-medium text-red-600 dark:text-red-400">{{ __('multipos::messages.info.status_declined') }}</span> {{ __('multipos::messages.info.status_declined_text') }}</li>
            </ul>
        </div>
    </div>

    <!-- POS Machines Table -->
    <div>
        <div class="mb-4">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('multipos::messages.table.registered_for_branch', ['branch' => $currentBranch->name]) }}</h4>
        </div>

        @if($machines->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('multipos::messages.table.alias') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('multipos::messages.table.machine_id') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('multipos::messages.table.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('multipos::messages.table.last_seen') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('multipos::messages.table.registered') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ __('multipos::messages.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($machines as $machine)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @if($editingMachineId === $machine->id)
                                        <input type="text" wire:model="editingMachineAlias"
                                            class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            autofocus>
                                    @else
                                        {{ $machine->alias ?? __('multipos::messages.table.no_alias') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <code class="text-xs bg-gray-100 dark:bg-gray-700 dark:text-gray-300 px-2 py-1 rounded">{{ $machine->public_id }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($machine->status === 'active')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">{{ __('multipos::messages.info.status_active') }}</span>
                                    @elseif($machine->status === 'pending')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200">{{ __('multipos::messages.info.status_pending') }}</span>
                                    @elseif($machine->status === 'declined')
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">{{ __('multipos::messages.info.status_declined') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $machine->last_seen_at ? $machine->last_seen_at->diffForHumans() : __('multipos::messages.table.never') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $machine->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 space-x-2 whitespace-nowrap text-left">
                                    @if($editingMachineId === $machine->id)
                                        {{-- Show only Save and Cancel when editing --}}
                                        <div class="relative inline-block group">
                                            <x-secondary-button-table wire:click="saveEdit" wire:key='save-{{ $machine->id . microtime() }}'>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </x-secondary-button-table>
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                                {{ __('multipos::messages.actions.save') }}
                                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                    <div class="border-4 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative inline-block group">
                                            <x-secondary-button-table wire:click="cancelEdit" wire:key='cancel-{{ $machine->id . microtime() }}'>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                            </x-secondary-button-table>
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                                {{ __('multipos::messages.actions.cancel') }}
                                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                    <div class="border-4 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Show all action buttons when not editing --}}
                                        @if($machine->status === 'pending')
                                            <div class="relative inline-block group">
                                                <x-secondary-button-table wire:click="approveMachine({{ $machine->id }})" wire:key='approve-{{ $machine->id . microtime() }}'>
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </x-secondary-button-table>
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                                    {{ __('multipos::messages.actions.approve') }}
                                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                        <div class="border-4 border-transparent border-t-gray-900"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        @if($machine->status === 'pending')
                                            <div class="relative inline-block group">
                                                <x-secondary-button-table wire:click="disableMachine({{ $machine->id }})" wire:key='decline-{{ $machine->id . microtime() }}'>
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </x-secondary-button-table>
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                                    {{ __('multipos::messages.actions.decline') }}
                                                    <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                        <div class="border-4 border-transparent border-t-gray-900"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="relative inline-block group">
                                            <x-secondary-button-table wire:click="editMachine({{ $machine->id }})" wire:key='edit-{{ $machine->id . microtime() }}'>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                                                </svg>
                                            </x-secondary-button-table>
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                                {{ __('multipos::messages.actions.edit') }}
                                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                    <div class="border-4 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative inline-block group">
                                            <x-danger-button-table wire:click="showDeleteMachine({{ $machine->id }})" wire:key='delete-{{ $machine->id . microtime() }}'>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                            </x-danger-button-table>
                                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                                {{ __('multipos::messages.actions.delete') }}
                                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                                    <div class="border-4 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Delete Confirmation Modal -->
            <x-confirmation-modal wire:model="confirmDeleteMachineModal">
                <x-slot name="title">
                    @lang('multipos::messages.settings.delete_machine_title')
                </x-slot>

                <x-slot name="content">
                    @lang('multipos::messages.settings.delete_machine_message')
                </x-slot>

                <x-slot name="footer">
                    <x-secondary-button wire:click="$toggle('confirmDeleteMachineModal')" wire:loading.attr="disabled">
                        {{ __('app.cancel') }}
                    </x-secondary-button>

                    <x-button type="button" class="ml-3" wire:click="deleteMachine" wire:loading.attr="disabled">
                        {{ __('app.delete') }}
                    </x-button>
                </x-slot>
            </x-confirmation-modal>

            @script
            <script>
                $wire.on('clear_pos_cookie', (data) => {
                    try {
                        const cookieName = data.name || 'pos_token';
                        // Expire cookie for current path and root path
                        document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                        document.cookie = cookieName + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT;';
                    } catch (e) {
                        console.warn('Failed clearing POS cookie', e);
                    }
                });
            </script>
            @endscript

            <div class="mt-4">
                {{ $machines->links() }}
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"></svg>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('multipos::messages.table.no_machines') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('multipos::messages.table.no_machines_hint') }}</p>
            </div>
        @endif
    </div>

</div>
