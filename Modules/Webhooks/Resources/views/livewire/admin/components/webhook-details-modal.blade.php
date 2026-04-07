<div x-data="{ open: false, webhook: null }" @show-webhook-details.window="open = true; webhook = $event.detail" @keydown.escape.window="open = false" class="relative z-50">
    <!-- Modal Overlay -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="open = false" style="display: none;"></div>

    <!-- Modal Content -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl" @click.away="open = false">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 rtl:space-x-reverse">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <h3 class="text-lg font-semibold text-white" x-text="webhook?.name || '{{ __('webhooks::webhooks.webhook_details') }}'"></h3>
                        </div>
                        <button @click="open = false" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-5 space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.name') }}</label>
                            <p class="text-sm font-medium text-gray-900" x-text="webhook?.name"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.status') }}</label>
                            <span x-show="webhook?.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 ltr:mr-1.5 rtl:ml-1.5"></span>
                                {{ __('webhooks::webhooks.is_active') }}
                            </span>
                            <span x-show="!webhook?.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-500 ltr:mr-1.5 rtl:ml-1.5"></span>
                                {{ __('webhooks::webhooks.status_disabled') }}
                            </span>
                        </div>
                    </div>

                    <!-- Target URL -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.target_url') }}</label>
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <p class="text-sm font-mono text-gray-700 break-all" x-text="webhook?.target_url"></p>
                        </div>
                    </div>

                    <!-- Secret (Masked) -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.secret') }}</label>
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                            <p class="text-sm font-mono text-gray-600">••••••••••••••••••••••••</p>
                        </div>
                    </div>

                    <!-- Configuration -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.max_attempts') }}</label>
                            <p class="text-sm text-gray-900" x-text="webhook?.max_attempts"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.backoff_seconds') }}</label>
                            <p class="text-sm text-gray-900" x-text="webhook?.backoff_seconds + 's'"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.branch') }}</label>
                            <p class="text-sm text-gray-900" x-text="webhook?.branch_id ? '#' + webhook.branch_id : '{{ __('webhooks::webhooks.all_branches') }}'"></p>
                        </div>
                    </div>

                    <!-- Subscribed Events -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('webhooks::webhooks.subscribed_events') }}</label>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <template x-if="!webhook?.subscribed_events || webhook?.subscribed_events.length === 0">
                                <p class="text-sm text-gray-500 italic">{{ __('webhooks::webhooks.all_events') }}</p>
                            </template>
                            <template x-if="webhook?.subscribed_events && webhook?.subscribed_events.length > 0">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <template x-for="event in webhook.subscribed_events" :key="event">
                                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                            <svg class="h-4 w-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="text-sm font-mono text-gray-700" x-text="event"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Source Modules -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('webhooks::webhooks.source_modules') }}</label>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <template x-if="!webhook?.source_modules || webhook?.source_modules.length === 0">
                                <p class="text-sm text-gray-500 italic">{{ __('webhooks::webhooks.all_modules') }}</p>
                            </template>
                            <template x-if="webhook?.source_modules && webhook?.source_modules.length > 0">
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="module in webhook.source_modules" :key="module">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 border border-indigo-200" x-text="module"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Additional Settings -->
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex items-center space-x-6 rtl:space-x-reverse">
                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <svg x-show="webhook?.redact_payload" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg x-show="!webhook?.redact_payload" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm text-gray-700">{{ __('webhooks::webhooks.redact_payload') }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">ID: </span>
                                <span class="text-xs font-mono text-gray-600" x-text="webhook?.id"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 rtl:space-x-reverse">
                    <button @click="open = false" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                        {{ __('webhooks::webhooks.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
