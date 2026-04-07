@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('multipos::messages.machines.title') }}</h2>
        <div class="flex items-center space-x-3">
            <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">
                {{ __('multipos::messages.dashboard.branch_label') }} {{ $currentBranch->name }}
            </span>
            <span class="inline-flex items-center rounded-md bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                {{ __('multipos::messages.dashboard.used_label') }} {{ $activeCount }} / {{ $limit === null || $limit < 0 ? __('multipos::messages.dashboard.unlimited') : $limit }}
            </span>
            <a href="{{ route('multi-pos.pending') }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                {{ __('multipos::messages.machines.pending_approvals', ['count' => $pendingCount ?? 0]) }}
            </a>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button onclick="filterMachines('all')" id="filter-all" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('multipos::messages.machines.filters.all') }}
            </button>
            <button onclick="filterMachines('active')" id="filter-active" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('multipos::messages.machines.filters.active') }}
            </button>
            <button onclick="filterMachines('pending')" id="filter-pending" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('multipos::messages.machines.filters.pending') }}
            </button>
            <button onclick="filterMachines('declined')" id="filter-declined" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('multipos::messages.machines.filters.declined') }}
            </button>
        </nav>
    </div>

    <!-- Machines Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:p-6">
            @if(count($machines) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="machinesTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.machines.table.alias') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.machines.table.machine_id') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.machines.table.status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.machines.table.last_seen') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.machines.table.created') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('multipos::messages.machines.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($machines as $machine)
                                <tr data-status="{{ $machine->status }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $machine->alias ?? __('multipos::messages.machines.table.no_alias') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $machine->public_id }}</code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($machine->status === 'active')
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">{{ __('multipos::messages.info.status_active') }}</span>
                                        @elseif($machine->status === 'pending')
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ __('multipos::messages.info.status_pending') }}</span>
                                        @elseif($machine->status === 'declined')
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ __('multipos::messages.info.status_declined') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $machine->last_seen_at ? $machine->last_seen_at->diffForHumans() : __('multipos::messages.table.never') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $machine->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewMachine({{ $machine->id }})" class="text-blue-600 hover:text-blue-900 mr-3">{{ __('multipos::messages.machines.buttons.view') }}</button>
                                        @if($machine->status === 'pending')
                                            <button onclick="approveMachine({{ $machine->id }})" class="text-green-600 hover:text-green-900 mr-3">{{ __('multipos::messages.machines.buttons.approve') }}</button>
                                        @endif
                                        <button onclick="editMachine({{ $machine->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">{{ __('multipos::messages.machines.buttons.edit') }}</button>
                                        @if($machine->status !== 'declined')
                                            <button onclick="disableMachine({{ $machine->id }})" class="text-red-600 hover:text-red-900">{{ __('multipos::messages.machines.buttons.decline') }}</button>
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
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('multipos::messages.machines.empty.title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('multipos::messages.machines.empty.hint') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Machine Details Modal -->
<div id="machineModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">{{ __('multipos::messages.machines.modal.title') }}</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="machineDetails"></div>
        </div>
    </div>
</div>

<script>
function filterMachines(status) {
    const rows = document.querySelectorAll('#machinesTable tbody tr');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    // Update active tab
    document.querySelectorAll('[id^="filter-"]').forEach(btn => btn.classList.remove('border-blue-500', 'text-blue-600'));
    document.getElementById('filter-' + status).classList.add('border-blue-500', 'text-blue-600');
}

function viewMachine(id) {
    // Fetch machine details and show in modal
    fetch(`/admin/multi-pos/machines/${id}/statistics`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('machineDetails').innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('multipos::messages.machines.modal.statistics') }}</label>
                        <div class="mt-2 grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded">
                                <p class="text-sm text-gray-500">{{ __('multipos::messages.machines.modal.total_orders') }}</p>
                                <p class="text-2xl font-bold">${data.stats.total_orders}</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded">
                                <p class="text-sm text-gray-500">{{ __('multipos::messages.machines.modal.today_orders') }}</p>
                                <p class="text-2xl font-bold">${data.stats.today_orders}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('machineModal').classList.remove('hidden');
        });
}

function approveMachine(id) {
    if (confirm(@json(__('multipos::messages.machines.confirm.approve')))) {
        fetch(`/admin/multi-pos/machines/${id}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

function disableMachine(id) {
    if (confirm(@json(__('multipos::messages.machines.confirm.decline')))) {
        fetch(`/admin/multi-pos/machines/${id}/disable`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

function editMachine(id) {
    // TODO: Implement edit functionality
    alert({!! json_encode(__('multipos::messages.js.edit_coming_soon')) !!});
}

function closeModal() {
    document.getElementById('machineModal').classList.add('hidden');
}
</script>
@endsection

