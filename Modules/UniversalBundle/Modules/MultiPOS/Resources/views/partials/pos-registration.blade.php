{{-- Variables are passed from parent view: $hasPosMachine, $machineStatus, $posMachine, $limitReached, $limitMessage, $shouldBlockPos --}}
@php
    // Use passed variables or set defaults if not provided
    $hasPosMachine = $hasPosMachine ?? false;
    $machineStatus = $machineStatus ?? null;
    $posMachine = $posMachine ?? null;
    $limitReached = $limitReached ?? false;
    $limitMessage = $limitMessage ?? '';
    $justRegistered = session('justRegistered', false);
    $needsApproval = session('needsApproval', false);
    $flashedMachine = session('machine');

    if (!$posMachine && $flashedMachine) {
        $posMachine = $flashedMachine;
    }

    $showPendingOverlay = ($hasPosMachine && $machineStatus === 'pending') || ($justRegistered && $needsApproval);
@endphp

@php
    $isMultiPosEnabled = module_enabled('MultiPOS') && in_array('MultiPOS', restaurant_modules());
@endphp

{{-- Show appropriate message based on machine status --}}
@if($isMultiPosEnabled)
    {{-- Declined - Centered Message (only covers POS content area) --}}
    @if($hasPosMachine && $machineStatus === 'declined')
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center bg-gray-100/95 px-4 py-6" style="min-height: calc(100vh - 4rem);">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6">
                    <svg class="h-12 w-12 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">@lang('multipos::messages.registration.declined.title')</h2>
                <p class="text-lg font-medium text-gray-700 mb-2">{{ $posMachine->alias ?? __('multipos::messages.registration.device') }}</p>
                <p class="text-gray-600 mb-6">@lang('multipos::messages.registration.declined.message')</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <x-secondary-button onclick="window.location.href='{{ route('dashboard') }}'" class="inline-flex items-center justify-center whitespace-nowrap">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        @lang('multipos::messages.registration.form.go_to_dashboard')
                    </x-secondary-button>
                    <x-button onclick="window.location.reload()" class="inline-flex items-center justify-center whitespace-nowrap">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        @lang('multipos::messages.registration.declined.check_status')
                    </x-button>
                </div>
            </div>
        </div>
        {{-- Hide all POS content for declined --}}
        <div style="display: none;">
    {{-- Pending - Centered Message (only covers POS content area) --}}
    @elseif($showPendingOverlay)
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center bg-gray-100/95 dark:bg-gray-900/95 px-4 py-6" style="min-height: calc(100vh - 4rem);">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-yellow-100 dark:bg-yellow-900 mb-6 animate-pulse">
                    <svg class="h-12 w-12 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">@lang('multipos::messages.registration.pending.title')</h2>
                <p class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $posMachine->alias ?? __('multipos::messages.registration.device') }}</p>
                <p class="text-gray-600 dark:text-gray-400 mb-6">@lang('multipos::messages.registration.pending.message')</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    @php
                        $canApprove = user() && user_can('Manage MultiPOS Machines');
                    @endphp
                    @if($canApprove && $posMachine)
                        {{-- Primary action: Approve This Machine (for admins) --}}
                        <x-button onclick="approvePendingMachine({{ $posMachine->id }})" id="approve_machine_btn" class="inline-flex items-center justify-center whitespace-nowrap">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @lang('multipos::messages.registration.pending.approve_this_machine')
                        </x-button>
                        {{-- Secondary action: Go to Settings --}}
                        <x-secondary-button onclick="window.location.href='{{ route('settings.index') }}?tab=multiposSettings'" class="inline-flex items-center justify-center whitespace-nowrap">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            @lang('multipos::messages.registration.pending.go_to_settings')
                        </x-secondary-button>
                    @else
                        {{-- For non-admins: Go to Dashboard --}}
                        <x-secondary-button onclick="window.location.href='{{ route('dashboard') }}'" class="inline-flex items-center justify-center whitespace-nowrap">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            @lang('multipos::messages.registration.form.go_to_dashboard')
                        </x-secondary-button>
                    @endif
                    {{-- Refresh Status button (always shown) --}}
                    <x-secondary-button onclick="window.location.reload()" class="inline-flex items-center justify-center whitespace-nowrap">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        @lang('multipos::messages.registration.pending.refresh_status')
                    </x-secondary-button>
                </div>
            </div>
        </div>
        {{-- Hide all POS content for pending --}}
        <div style="display: none;">
    {{-- Active --}}
    @elseif($hasPosMachine && $machineStatus === 'active')
        <div class="bg-gray-200 dark:bg-gray-800 border-l-4 border-gray-500 dark:border-gray-500 p-1.5 my-2 mx-4 text-xs rounded text-gray-600 dark:text-gray-400">
            <span class="font-medium">@lang('multipos::messages.registration.active.label')</span>
            <span class="ml-1">{{ $posMachine->alias ?? __('multipos::messages.registration.device') }}</span>
          
        </div>
    @endif
@endif

{{-- No machine - show registration modal or limit reached message --}}
@if($isMultiPosEnabled && !$hasPosMachine && !$showPendingOverlay)
    <div class="fixed inset-0 z-40 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="window.location.href='{{ route('dashboard') }}'"></div>

            <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button onclick="window.location.href='{{ route('dashboard') }}'" class="bg-white rounded-md text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    @if($limitReached)
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                @lang('multipos::messages.registration.limit_reached.title')
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm mb-4">{{ $limitMessage }}</p>
                                <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-red-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="text-sm text-red-700">
                                            <p class="font-medium">@lang('multipos::messages.registration.limit_reached.what_can_you_do')</p>
                                            <p class="mt-1">@lang('multipos::messages.registration.limit_reached.hint')</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                @lang('multipos::messages.registration.form.title')
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">@lang('multipos::messages.registration.form.description')</p>

                            <form action="{{ route('pos.claim.store') }}" method="POST" id="posRegistrationForm">
                                @csrf

                                <div class="mb-4">
                                    <label for="branch_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        @lang('multipos::messages.registration.form.select_branch') <span class="text-red-500">*</span>
                                    </label>
                                    @if(user() && user()->hasRole('Admin_'.user()->restaurant_id))
                                        <select name="branch_id" id="branch_id" required
                                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">@lang('multipos::messages.registration.form.select_branch_placeholder')</option>
                                            @foreach(branches() ?? [] as $branch)
                                                <option value="{{ $branch->id }}" {{ branch()->id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="hidden" name="branch_id" id="branch_id" value="{{ branch()->id }}">
                                        <select id="branch_id_display" disabled
                                            class="block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-600">
                                            <option value="{{ branch()->id }}" selected>{{ branch()->name }}</option>
                                        </select>
                                    @endif
                                    <div id="branch_error" class="mt-2 text-sm text-red-600 hidden">@lang('multipos::messages.registration.form.select_branch_error')</div>
                                    <div id="branch_limit_message" class="mt-2 text-sm text-red-600 hidden"></div>
                                </div>

                                <div class="mb-4" id="device_name_container">
                                    <label for="alias" class="block text-sm font-medium text-gray-700 mb-2">
                                        @lang('multipos::messages.registration.form.device_name')
                                    </label>
                                    <input type="text" name="alias" id="alias"
                                        placeholder="@lang('multipos::messages.registration.form.device_name_placeholder')"
                                        maxlength="255"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <p class="mt-1 text-sm text-gray-500">@lang('multipos::messages.registration.form.device_name_hint')</p>
                                </div>

                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="text-sm text-blue-700">
                                            <p class="font-medium">@lang('multipos::messages.registration.form.what_happens_next')</p>
                                            <p class="mt-1">@lang('multipos::messages.registration.form.what_happens_next_text')</p>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <x-secondary-button id="go_to_dashboard_button" onclick="window.location.href='{{ route('dashboard') }}'"
                        class="w-full sm:w-auto sm:text-sm"
                        style="display: {{ isset($limitReached) && $limitReached ? 'inline-flex' : 'none' }};">
                        @lang('multipos::messages.registration.form.go_to_dashboard')
                    </x-secondary-button>
                    <x-button type="button" id="register_device_button" onclick="validateAndSubmit()"
                        class="w-full sm:ml-3 sm:w-auto sm:text-sm"
                        style="display: {{ isset($limitReached) && $limitReached ? 'none' : 'inline-flex' }};">
                        @lang('multipos::messages.registration.form.register_button')
                    </x-button>
                    <x-secondary-button id="cancel_button" onclick="window.location.href='{{ route('dashboard') }}'"
                        class="mt-3 w-full sm:mt-0 sm:w-auto sm:text-sm"
                        style="display: {{ isset($limitReached) && $limitReached ? 'none' : 'inline-flex' }};">
                        @lang('multipos::messages.registration.form.cancel_button')
                    </x-secondary-button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- $shouldBlockPos is passed from parent view --}}

{{-- Registration Validation Script --}}
@if(module_enabled('MultiPOS'))
<script>
    // MultiPOS Translations
    window.multiposTranslations = {
        networkError: {!! json_encode(__('multipos::messages.js.network_error')) !!},
        errorCheckingLimit: {!! json_encode(__('multipos::messages.js.error_checking_limit')) !!},
        limitReachedMessage: {!! json_encode(__('multipos::messages.js.limit_reached_message')) !!},
    };

    // Helper function to format translated messages with parameters
    function translate(key, params = {}) {
        let message = window.multiposTranslations[key] || key;
        Object.keys(params).forEach(param => {
            message = message.replace(':' + param, params[param]);
        });
        return message;
    }

    // Make validateAndSubmit globally accessible
    window.validateAndSubmit = function() {
        const branchId = document.getElementById('branch_id');
        const errorDiv = document.getElementById('branch_error');
        const limitMessageDiv = document.getElementById('branch_limit_message');
        const form = document.getElementById('posRegistrationForm');
        const registerButton = document.getElementById('register_device_button');
        const cancelButton = document.getElementById('cancel_button');

        if (!branchId || !branchId.value) {
            if (errorDiv) errorDiv.classList.remove('hidden');
            if (branchId) branchId.focus();
            return;
        }

        if (errorDiv) errorDiv.classList.add('hidden');

        // Check if limit is reached before submitting
        const limitReached = limitMessageDiv && !limitMessageDiv.classList.contains('hidden');
        if (limitReached) {
            return;
        }

        if (!form) {
            return;
        }

        if (registerButton) {
            registerButton.disabled = true;
        }

        if (cancelButton) {
            cancelButton.disabled = true;
        }

        form.submit();
    };

    // Also make it available without window prefix for compatibility
    function validateAndSubmit() {
        return window.validateAndSubmit();
    }

    // Approve pending machine function
    window.approvePendingMachine = function(machineId) {
        const approveBtn = document.getElementById('approve_machine_btn');
        const originalText = approveBtn ? approveBtn.innerHTML : '';
        
        // Disable button and show loading state
        if (approveBtn) {
            approveBtn.disabled = true;
            approveBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Approving...';
        }

        const csrfToken = getCsrfToken();
        const baseUrl = {!! json_encode(url('/multi-pos/machines')) !!};
        const url = baseUrl + '/' + machineId + '/approve';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Failed to approve machine');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message and reload page
                if (typeof window.Livewire !== 'undefined') {
                    window.Livewire.dispatch('alert', {
                        type: 'success',
                        message: data.message || 'Machine approved successfully'
                    });
                }
                // Reload page after short delay to show success
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                throw new Error(data.message || 'Failed to approve machine');
            }
        })
        .catch(error => {
            console.error('Error approving machine:', error);
            // Re-enable button
            if (approveBtn) {
                approveBtn.disabled = false;
                approveBtn.innerHTML = originalText;
            }
            // Show error message
            alert(error.message || 'Failed to approve machine. Please try again or go to Settings.');
        });
    };

    // Make it available without window prefix for compatibility
    function approvePendingMachine(machineId) {
        return window.approvePendingMachine(machineId);
    }

    function getCsrfToken() {
        // Try to get CSRF token from meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        // Fallback: try to get from form
        const form = document.getElementById('posRegistrationForm');
        if (form) {
            const csrfInput = form.querySelector('input[name="_token"]');
            if (csrfInput) {
                return csrfInput.value;
            }
        }
        return {!! json_encode(csrf_token()) !!};
    }

    function checkBranchLimit(branchId) {
        if (!branchId) {
            // Reset UI if no branch selected
            resetBranchLimitUI();
            return;
        }

        const csrfToken = getCsrfToken();
        const url = {!! json_encode(route('pos.claim.check-branch-limit')) !!};

        console.log('Checking branch limit for branch ID:', branchId);
        console.log('Using URL:', url);

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ branch_id: parseInt(branchId) })
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || window.multiposTranslations.networkError);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Branch limit check response:', data);
            const limitMessageDiv = document.getElementById('branch_limit_message');
            const deviceNameContainer = document.getElementById('device_name_container');
            const registerButton = document.getElementById('register_device_button');
            const cancelButton = document.getElementById('cancel_button');
            const goToDashboardButton = document.getElementById('go_to_dashboard_button');

            if (data.limit_reached) {
                console.log('Limit reached for branch');
                // Show limit message
                if (limitMessageDiv) {
                    // Use message from API if available, otherwise use translation
                    limitMessageDiv.textContent = data.message || translate('limitReachedMessage', { limit: data.limit });
                    limitMessageDiv.classList.remove('hidden');
                }

                // Hide device name field
                if (deviceNameContainer) {
                    deviceNameContainer.style.display = 'none';
                }

                // Hide register button, show go to dashboard button
                if (registerButton) {
                    console.log('Hiding register button');
                    registerButton.style.display = 'none';
                    registerButton.style.visibility = 'hidden';
                } else {
                    console.warn('Register button not found!');
                }
                if (cancelButton) {
                    cancelButton.style.display = 'none';
                    cancelButton.style.visibility = 'hidden';
                }
                if (goToDashboardButton) {
                    goToDashboardButton.style.display = 'inline-flex';
                    goToDashboardButton.style.visibility = 'visible';
                }
            } else {
                console.log('Limit not reached for branch');
                // Reset UI - limit not reached
                resetBranchLimitUI();
            }
        })
        .catch(error => {
            console.error(window.multiposTranslations.errorCheckingLimit + ':', error);
            resetBranchLimitUI();
        });
    }

    function resetBranchLimitUI() {
        const limitMessageDiv = document.getElementById('branch_limit_message');
        const deviceNameContainer = document.getElementById('device_name_container');
        const registerButton = document.getElementById('register_device_button');
        const cancelButton = document.getElementById('cancel_button');
        const goToDashboardButton = document.getElementById('go_to_dashboard_button');

        if (limitMessageDiv) {
            limitMessageDiv.classList.add('hidden');
            limitMessageDiv.textContent = '';
        }

        if (deviceNameContainer) {
            deviceNameContainer.style.display = 'block';
        }

        if (registerButton) {
            registerButton.style.display = 'inline-flex';
            registerButton.style.visibility = 'visible';
        }
        if (cancelButton) {
            cancelButton.style.display = 'inline-flex';
            cancelButton.style.visibility = 'visible';
        }
        if (goToDashboardButton) {
            goToDashboardButton.style.display = 'none';
            goToDashboardButton.style.visibility = 'hidden';
        }
    }

    // Initialize function that can be called on page load and Livewire navigation
    function initializePosRegistration() {
        console.log('POS Registration script initializing');

        // Check if registration form exists (it only shows when device needs registration)
        const registrationForm = document.getElementById('posRegistrationForm');
        if (!registrationForm) {
            console.log('Registration form not found - device may already be registered');
            return;
        }

        const branchSelect = document.getElementById('branch_id');
        const branchIdInput = document.getElementById('branch_id');
        const limitReachedFromPHP = {!! json_encode(isset($limitReached) && $limitReached) !!};
        const isAdminUser = {!! json_encode(user() && user()->hasRole('Admin_'.user()->restaurant_id)) !!};

        // Check if limit is already reached from PHP (initial state)
        if (limitReachedFromPHP) {
            const limitMessageDiv = document.getElementById('branch_limit_message');
            const deviceNameContainer = document.getElementById('device_name_container');
            const registerButton = document.getElementById('register_device_button');
            const cancelButton = document.getElementById('cancel_button');
            const goToDashboardButton = document.getElementById('go_to_dashboard_button');

            if (limitMessageDiv) {
                limitMessageDiv.textContent = {!! json_encode($limitMessage ?? '') !!};
                limitMessageDiv.classList.remove('hidden');
            }
            if (registerButton) {
                registerButton.style.display = 'none';
                registerButton.style.visibility = 'hidden';
            }
            if (cancelButton) {
                cancelButton.style.display = 'none';
                cancelButton.style.visibility = 'hidden';
            }
            if (goToDashboardButton) {
                goToDashboardButton.style.display = 'inline-flex';
                goToDashboardButton.style.visibility = 'visible';
            }
            if (deviceNameContainer) {
                deviceNameContainer.style.display = 'none';
            }
        }

        if (isAdminUser) {
            // For admin users who can select branch
            if (branchSelect && branchSelect.tagName === 'SELECT') {
                console.log('Admin user - setting up branch select listener');
                // Remove existing listeners to avoid duplicates
                const newBranchSelect = branchSelect.cloneNode(true);
                branchSelect.parentNode.replaceChild(newBranchSelect, branchSelect);
                
                newBranchSelect.addEventListener('change', function() {
                    const selectedBranchId = this.value;
                    console.log('Branch changed to:', selectedBranchId);
                    checkBranchLimit(selectedBranchId);
                });

                // Check limit on page load if branch is already selected (only if not already reached from PHP)
                if (!limitReachedFromPHP && newBranchSelect.value) {
                    console.log('Checking limit for initial branch:', newBranchSelect.value);
                    checkBranchLimit(newBranchSelect.value);
                }
            }
        } else {
            // For non-admin users, check limit for current branch on page load (only if not already reached from PHP)
            if (!limitReachedFromPHP && branchIdInput && branchIdInput.tagName === 'INPUT' && branchIdInput.value) {
                console.log('Non-admin user - checking limit for branch:', branchIdInput.value);
                checkBranchLimit(branchIdInput.value);
            }
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initializePosRegistration);

    // Initialize on Livewire navigation (when component is updated)
    document.addEventListener('livewire:init', initializePosRegistration);
    document.addEventListener('livewire:navigated', initializePosRegistration);
</script>
@endif

{{-- $shouldBlockPos variable is set for use in main file --}}
