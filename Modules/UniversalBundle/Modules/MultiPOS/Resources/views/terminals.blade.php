@extends('layouts.app')

@section('content')
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('multipos::messages.terminals.title') }}</h2>
            <div class="flex space-x-3">
                <button onclick="openAddTerminalModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('multipos::messages.terminals.add_terminal') }}
                </button>
                <a href="{{ route('multi-pos.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('multipos::messages.terminals.back_to_dashboard') }}
                </a>
            </div>
        </div>

        <!-- Terminals Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('multipos::messages.terminals.pos_terminals') }}</h3>

                @if(count($terminals) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('multipos::messages.terminals.table.name')</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('multipos::messages.terminals.table.type')</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('multipos::messages.terminals.table.printer')</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('multipos::messages.terminals.table.status')</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('multipos::messages.terminals.table.default')</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">@lang('multipos::messages.terminals.table.actions')</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($terminals as $terminal)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $terminal->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($terminal->type === 'food') bg-green-100 text-green-800
                                                @elseif($terminal->type === 'beverage') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ __('multipos::messages.terminals.type_options.' . $terminal->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $terminal->printer_name ?? __('multipos::messages.terminals.no_printer') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $terminal->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $terminal->is_active ? __('multipos::messages.terminals.status.active') : __('multipos::messages.terminals.status.inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($terminal->is_default)
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    @lang('multipos::messages.terminals.default')
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="editTerminal({{ $terminal->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('multipos::messages.actions.edit') }}</button>
                                            @if(!$terminal->is_default)
                                                <button onclick="deleteTerminal({{ $terminal->id }})" class="text-red-600 hover:text-red-900">{{ __('multipos::messages.terminals.delete_terminal') }}</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('multipos::messages.terminals.no_terminals') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('multipos::messages.terminals.get_started') }}</p>
                        <div class="mt-6">
                            <button onclick="openAddTerminalModal()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                {{ __('multipos::messages.terminals.add_terminal') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add/Edit Terminal Modal -->
    <div id="terminalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle">{{ __('multipos::messages.terminals.add_terminal') }}</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="terminalForm">
                    <div class="mb-4">
                        <label for="terminalName" class="block text-sm font-medium text-gray-700">{{ __('multipos::messages.terminals.terminal_name') }}</label>
                        <input type="text" id="terminalName" name="name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div class="mb-4">
                        <label for="terminalType" class="block text-sm font-medium text-gray-700">{{ __('multipos::messages.terminals.type') }}</label>
                        <select id="terminalType" name="type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="food">@lang('multipos::messages.terminals.type_options.food')</option>
                            <option value="beverage">@lang('multipos::messages.terminals.type_options.beverage')</option>
                            <option value="general">@lang('multipos::messages.terminals.type_options.general')</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="terminalActive" name="is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">{{ __('multipos::messages.terminals.active') }}</span>
                        </label>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ __('multipos::messages.terminals.cancel') }}
                        </button>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('multipos::messages.terminals.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let editingTerminalId = null;

        function openAddTerminalModal() {
            editingTerminalId = null;
            document.getElementById('modalTitle').textContent = {!! json_encode(__('multipos::messages.terminals.add_terminal')) !!};
            document.getElementById('terminalForm').reset();
            document.getElementById('terminalActive').checked = true;
            document.getElementById('terminalModal').classList.remove('hidden');
        }

        function editTerminal(id) {
            editingTerminalId = id;
            document.getElementById('modalTitle').textContent = {!! json_encode(__('multipos::messages.terminals.edit_terminal')) !!};
            // Here you would populate the form with existing data
            document.getElementById('terminalModal').classList.remove('hidden');
        }

        function deleteTerminal(id) {
            if (confirm({!! json_encode(__('multipos::messages.terminals.delete_confirm')) !!})) {
                // Here you would make an API call to delete the terminal
                console.log('Delete terminal:', id);
            }
        }

        function closeModal() {
            document.getElementById('terminalModal').classList.add('hidden');
        }

        document.getElementById('terminalForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            if (editingTerminalId) {
                // Update existing terminal
                console.log('Update terminal:', editingTerminalId, data);
            } else {
                // Create new terminal
                console.log('Create terminal:', data);
            }

            closeModal();
        });
    </script>
@endsection
