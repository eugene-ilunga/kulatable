<x-dialog-modal wire:model.live="showRaiseSupportTicketModal">
    <x-slot name="title">
        <h2 class="text-lg">@lang('superadmin.raiseSupportTicket')</h2>
    </x-slot>

    <x-slot name="content">
        <div class="max-w-4xl mx-auto px-4">
            <!-- Header -->
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">@lang('superadmin.supportTicket.chooseOption')</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">@lang('superadmin.supportTicket.chooseOptionDescription')</p>
            </div>
            
            <!-- Support Options -->
            <div class="space-y-6">
                <!-- Envato Support Card -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center mb-4">
                        <img src="https://cdn.worldvectorlogo.com/logos/envato.svg" alt="{{ __('superadmin.supportTicket.envatoAlt') }}" class="h-8 w-8 object-contain mr-3">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">@lang('superadmin.supportTicket.envatoTitle')</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">@lang('superadmin.supportTicket.envatoSubtitle')</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-zinc-600 dark:text-zinc-400">@lang('superadmin.supportTicket.envatoFeature1')</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-zinc-600 dark:text-zinc-400">@lang('superadmin.supportTicket.envatoFeature2')</span>
                            </div>
                       
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-zinc-600 dark:text-zinc-400">@lang('superadmin.supportTicket.envatoFeature3')</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-zinc-600 dark:text-zinc-400">@lang('superadmin.supportTicket.envatoFeature4')</span>
                            </div>
                           
                        </div>
                    </div>
                    
                    <div class="flex">
                        <a href="https://froiden.freshdesk.com/support/tickets/new" target="_blank" 
                           class="inline-flex items-center px-6 py-2 bg-zinc-600 hover:bg-zinc-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            @lang('superadmin.supportTicket.raiseTicket')
                        </a>
                    </div>
                </div>

                <!-- Priority Support Card -->
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-6 border border-indigo-200 dark:border-indigo-700 relative">
                    <div class="absolute top-4 right-4">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200">
                            @lang('superadmin.supportTicket.recommended')
                        </span>
                    </div>
                    
                    <div class="flex items-center mb-4">
                        <img src="https://envato.froid.works/logo-froiden.png" alt="{{ __('superadmin.supportTicket.froidenAlt') }}" class="h-8 w-8 object-contain mr-3">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">@lang('superadmin.supportTicket.priorityTitle')</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">@lang('superadmin.supportTicket.prioritySubtitle')</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">@lang('superadmin.supportTicket.priorityFeature1')</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">@lang('superadmin.supportTicket.priorityFeature2')</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">@lang('superadmin.supportTicket.priorityFeature3')</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">@lang('superadmin.supportTicket.priorityFeature4')</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">@lang('superadmin.supportTicket.priorityFeature5')</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg class="h-4 w-4 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">@lang('superadmin.supportTicket.priorityFeature6')</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex">
                        <a href="https://envato.froid.works/priority-support?purchase_code={{ global_setting()->purchase_code }}&utm_source=tabletrack_app&utm_campaign=priority_support" target="_blank" 
                                class="inline-flex items-center px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            @lang('superadmin.supportTicket.knowMore')
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex w-full pb-4 space-x-4 rtl:space-x-reverse mt-6 justify-end">
                <x-button-cancel  wire:click="$set('showRaiseSupportTicketModal', false)">@lang('app.close')</x-button-cancel>
            </div>
        </div>
    </x-slot>
</x-dialog-modal> 
