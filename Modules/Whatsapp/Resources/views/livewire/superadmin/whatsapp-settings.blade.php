<div>
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        
        <h3 class="mb-4 text-xl font-semibold dark:text-white inline-flex gap-4 items-center">
            {{ __('whatsapp::app.whatsappSettings') }}
        </h3>

        <form wire:submit="submitForm">
            <div class="grid gap-6">
                
                <!-- Enable WhatsApp Switch -->
                <div class="mb-4 p-4 border border-gray-200 dark:border-gray-700 rounded bg-gray-50 dark:bg-gray-800">
                    <div class="flex items-start gap-4">
                        <x-checkbox name="isEnabled" id="isEnabled" wire:model.live='isEnabled' class="mt-1" />
                        <div class="w-full">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ __('whatsapp::app.enableWhatsapp') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('whatsapp::app.enableWhatsappDescription') }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                {!! __('whatsapp::app.createCredentialsHint', ['link' => 'https://business.facebook.com/']) !!}
                            </div>
                        </div>
                    </div>

                    @if ($isEnabled)
                        <!-- WhatsApp Credentials -->
                        <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <x-label for="wabaId" :value="__('whatsapp::app.wabaId')" />
                                    </div>
                                    <x-input id="wabaId" class="block mt-1 w-full" type="text" wire:model='wabaId' 
                                        :placeholder="__('whatsapp::app.enterWabaId')" />
                                    <x-input-error for="wabaId" class="mt-2" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('whatsapp::app.wabaIdHelp') }}
                                    </p>
                                </div>

                                <div>
                                    <x-label for="phoneNumberId" :value="__('whatsapp::app.phoneNumberId')" />
                                    <x-input id="phoneNumberId" class="block mt-1 w-full" type="text" wire:model='phoneNumberId' 
                                        :placeholder="__('whatsapp::app.enterPhoneNumberId')" />
                                    <x-input-error for="phoneNumberId" class="mt-2" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('whatsapp::app.phoneNumberIdHelp') }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-label for="accessToken" :value="__('whatsapp::app.accessToken')" />
                                    <div class="relative" x-data="{ show: false }">
                                        <input
                                            id="accessToken"
                                            x-bind:type="show ? 'text' : 'password'"
                                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-gray-500 dark:focus:border-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 rounded-md shadow-sm block mt-1 w-full pr-10"
                                            wire:model='accessToken'
                                            placeholder="{{ __('whatsapp::app.enterAccessToken') }}"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                            x-on:click="show = !show"
                                            tabindex="-1"
                                        >
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.27-2.944-9.543-7a10.033 10.033 0 012.957-4.558m2.556-2.557A10.05 10.05 0 0112 5c4.478 0 8.27 2.944 9.543 7-.275.877-.681 1.693-1.2 2.422m-2.058 2.065A10.05 10.05 0 0112 19a10.05 10.05 0 01-6.473-2.464M3 3l18 18"/></svg>
                                        </button>
                                    </div>
                                    <x-input-error for="accessToken" class="mt-2" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('whatsapp::app.accessTokenHelp') }}
                                    </p>
                                </div>

                                <div>
                                    <x-label for="verifyToken" :value="__('whatsapp::app.verifyToken')" />
                                    <div class="relative" x-data="{ show: false }">
                                        <input
                                            id="verifyToken"
                                            x-bind:type="show ? 'text' : 'password'"
                                            class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-gray-500 dark:focus:border-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 rounded-md shadow-sm block mt-1 w-full pr-10"
                                            wire:model='verifyToken'
                                            placeholder="{{ __('whatsapp::app.enterVerifyToken') }}"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                            x-on:click="show = !show"
                                            tabindex="-1"
                                        >
                                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.27-2.944-9.543-7a10.033 10.033 0 012.957-4.558m2.556-2.557A10.05 10.05 0 0112 5c4.478 0 8.27 2.944 9.543 7-.275.877-.681 1.693-1.2 2.422m-2.058 2.065A10.05 10.05 0 0112 19a10.05 10.05 0 01-6.473-2.464M3 3l18 18"/></svg>
                                        </button>
                                    </div>
                                    <x-input-error for="verifyToken" class="mt-2" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('whatsapp::app.verifyTokenHelp') }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('whatsapp::app.webhookUrlExample') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <x-button>@lang('app.save')</x-button>

                    <x-button
                        type="button"
                        wire:click="testConnection"
                        wire:loading.attr="disabled"
                        wire:target="testConnection"
                        class="bg-blue-600 hover:bg-blue-700"
                    >

                        <span wire:loading.remove wire:target="testConnection">{{ __('whatsapp::app.testConnection') }}</span>
                        <span wire:loading wire:target="testConnection">{{ __('whatsapp::app.loading') }}</span>
                    </x-button>
                </div>
            </div>
        </form>
    </div>

    <!-- Templates Section -->
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-4 text-xl font-semibold dark:text-white">
            {{ __('whatsapp::app.templateLibrary') }}
        </h3>
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('whatsapp::app.templateLibraryDescription') }}
        </p>

        <!-- Template Categories -->
        <div class="mb-6">
            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" wire:click="$set('selectedCategory', null)" 
                    class="px-4 py-2 text-sm font-medium rounded-lg border {{ $selectedCategory === null ? 'bg-skin-base text-white border-skin-base' : 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ __('whatsapp::app.allTemplates') }} ({{ $templates->count() }})
                </button>
                <button type="button" wire:click="$set('selectedCategory', 'customer')" 
                    class="px-4 py-2 text-sm font-medium rounded-lg border {{ $selectedCategory === 'customer' ? 'bg-skin-base text-white border-skin-base' : 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ __('whatsapp::app.customer') }} ({{ $templates->where('category', 'customer')->count() }})
                </button>
                <button type="button" wire:click="$set('selectedCategory', 'staff')" 
                    class="px-4 py-2 text-sm font-medium rounded-lg border {{ $selectedCategory === 'staff' ? 'bg-skin-base text-white border-skin-base' : 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ __('whatsapp::app.staff') }} ({{ $templates->where('category', 'staff')->count() }})
                </button>
                <button type="button" wire:click="$set('selectedCategory', 'delivery')" 
                    class="px-4 py-2 text-sm font-medium rounded-lg border {{ $selectedCategory === 'delivery' ? 'bg-skin-base text-white border-skin-base' : 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ __('whatsapp::app.delivery') }} ({{ $templates->where('category', 'delivery')->count() }})
                </button>
                @if($templates->where('category', 'all')->count() > 0)
                <button type="button" wire:click="$set('selectedCategory', 'all')" 
                    class="px-4 py-2 text-sm font-medium rounded-lg border {{ $selectedCategory === 'all' ? 'bg-skin-base text-white border-skin-base' : 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ __('whatsapp::app.general') }} ({{ $templates->where('category', 'all')->count() }})
                </button>
                @endif
            </div>
        </div>

        <!-- Templates List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @forelse($templates as $template)
                @if($selectedCategory === null || $template->category === $selectedCategory)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-skin-base transition-colors cursor-pointer 
                        {{ $selectedTemplate === $template->notification_type ? 'border-skin-base bg-skin-base/5' : '' }}"
                        wire:click="selectTemplate('{{ $template->notification_type }}')">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                    {{ $template->template_name }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    {{ $template->description ?? __('whatsapp::app.noDescription') }}
                                </p>
                                <span class="inline-block px-2 py-1 text-xs font-medium rounded 
                                    @if($template->category === 'customer') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                    @elseif($template->category === 'all') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                    @elseif($template->category === 'staff') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                    @elseif($template->category === 'delivery') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($template->category) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 font-mono">
                            {{ __('whatsapp::app.template') }}: <span class="font-semibold">{{ $template->notification_type }}</span>
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-span-full p-8 text-center text-gray-500 dark:text-gray-400">
                    <p>{{ __('whatsapp::app.noTemplatesFound') }}</p>
                </div>
            @endforelse
        </div>

        <!-- Selected Template Details -->
        @if($selectedTemplate && $templateDetails)
            <div
                id="template-creation-guide"
                class="mt-6 p-6 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700"
                x-data
                x-on:scroll-to-template-guide.window="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
            >
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('whatsapp::app.templateCreationGuide') }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ __('whatsapp::app.followStepsBelow') }} 
                            <a href="https://business.facebook.com/latest/whatsapp_manager/message_templates" target="_blank" 
                                class="text-skin-base hover:underline">
                                {{ __('whatsapp::app.metaPortal') }}
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Important Variable Rules -->
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border-2 border-red-200 dark:border-red-800">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h5 class="text-sm font-semibold text-red-800 dark:text-red-200 mb-2">
                                {{ __('whatsapp::app.importantVariableRules') }}
                            </h5>
                            <ul class="text-xs text-red-700 dark:text-red-300 space-y-1 list-disc list-inside">
                                <li><strong>{{ __('whatsapp::app.header') }} {{ __('whatsapp::app.variables') }}:</strong> {{ __('whatsapp::app.startFrom') }} <code class="bg-red-100 dark:bg-red-900 px-1 rounded">{{1}}</code> ({{ __('whatsapp::app.separateScope') }})</li>
                                <li><strong>{{ __('whatsapp::app.body') }} {{ __('whatsapp::app.variables') }}:</strong> {{ __('whatsapp::app.startFrom') }} <code class="bg-red-100 dark:bg-red-900 px-1 rounded">{{1}}</code> ({{ __('whatsapp::app.separateScope') }}, {{ __('whatsapp::app.independentFromHeader') }})</li>
                                <li><strong>{{ __('whatsapp::app.footer') }}:</strong> <strong class="text-red-800 dark:text-red-200">{{ __('whatsapp::app.noVariablesAllowed') }}</strong> - {{ __('whatsapp::app.footerMustBeStatic') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                @if($selectedTemplate === 'operations_summary')
                    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border-2 border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h5 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">
                                    {{ __('whatsapp::app.documentHeaderRequirement') }}
                                </h5>
                                <p class="text-xs text-amber-700 dark:text-amber-300">
                                    {{ __('whatsapp::app.operationsSummaryDocumentHeaderIntro') }}
                                    <strong>{{ __('whatsapp::app.documentHeaderLabel') }}</strong>.
                                    {{ __('whatsapp::app.bodyOnlyTemplateRejectsPdf') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($selectedTemplate === 'reservation_notification')
                    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border-2 border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h5 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">
                                    {{ __('whatsapp::app.reservationButtonUrlRequirement') }}
                                </h5>
                                <p class="text-xs text-amber-700 dark:text-amber-300">
                                    {{ __('whatsapp::app.reservationButtonUrlRequirementDescription') }}
                                </p>
                                <div class="mt-2 bg-white dark:bg-gray-800 p-2 rounded border text-xs text-amber-800 dark:text-amber-200">
                                    <code>{{ rtrim(config('app.url'), '/') . '/restaurant/my-bookings/' }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-6">
                    <!-- Step 1: Template Name -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">1</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('whatsapp::app.step1TemplateName') }}
                                </label>
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <code class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $templateDetails['name'] }}</code>
                                        <div class="flex items-center gap-2">
                                            <button type="button" 
                                                x-data="{ copied: false }"
                                                @click="navigator.clipboard.writeText('{{ $templateDetails['name'] }}').then(() => { copied = true; setTimeout(() => { copied = false; }, 2000); })"
                                                class="px-2 py-1 text-xs text-skin-base hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
                                                <span x-show="!copied">{{ __('whatsapp::app.copy') }}</span>
                                                <span x-show="copied" class="text-green-600 dark:text-green-400">{{ __('whatsapp::app.copied') }}</span>
                                            </button>
                                            <button type="button" 
                                                wire:click="fetchTemplatePreview('{{ $templateDetails['name'] }}')"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors disabled:opacity-50"
                                                title="Preview template from Meta API">
                                                <svg wire:loading.remove wire:target="fetchTemplatePreview" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                <svg wire:loading wire:target="fetchTemplatePreview" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="fetchTemplatePreview">{{ __('whatsapp::app.preview') }}</span>
                                                <span wire:loading wire:target="fetchTemplatePreview">{{ __('whatsapp::app.loading') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ __('whatsapp::app.enterInNameField') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Template Preview from Meta API -->
                    @if($templatePreview || $previewError)
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-purple-200 dark:border-purple-800">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <span class="flex-shrink-0 w-8 h-8 bg-purple-500 text-white rounded-full flex items-center justify-center font-bold text-sm">👁️</span>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ __('whatsapp::app.templatePreview') }}
                                    </label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('whatsapp::app.templatePreviewDescription') }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" 
                                wire:click="closePreview"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        @if($previewError)
                        <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-200 dark:border-red-800">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-red-800 dark:text-red-200">{{ __('whatsapp::app.errorLoadingPreview') }}</p>
                                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $previewError }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($templatePreview)
                        <div class="space-y-4">
                            <!-- Template Status -->
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('whatsapp::app.templateStatus') }}</h4>
                                <div class="flex flex-wrap items-center gap-2">
                                    @php
                                        $status = $templatePreview['status'] ?? 'UNKNOWN';
                                        $statusMap = [
                                            'APPROVED' => ['label' => __('whatsapp::app.statusApproved'), 'class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', 'icon' => '✓'],
                                            'PENDING' => ['label' => __('whatsapp::app.statusPendingReview'), 'class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', 'icon' => '⏳'],
                                            'PENDING_DELETION' => ['label' => __('whatsapp::app.statusPendingDeletion'), 'class' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300', 'icon' => '🗑️'],
                                            'REJECTED' => ['label' => __('whatsapp::app.statusRejected'), 'class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', 'icon' => '✗'],
                                            'DISABLED' => ['label' => __('whatsapp::app.statusDisabled'), 'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'icon' => '⊘'],
                                            'LIMIT_EXCEEDED' => ['label' => __('whatsapp::app.statusLimitExceeded'), 'class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300', 'icon' => '⚠'],
                                            'PAUSED' => ['label' => __('whatsapp::app.statusPaused'), 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', 'icon' => '⏸'],
                                        ];
                                        $statusInfo = $statusMap[$status] ?? ['label' => $status, 'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'icon' => '?'];
                                    @endphp
                                    <span class="px-3 py-1.5 text-xs font-semibold rounded {{ $statusInfo['class'] }}">
                                        <span class="mr-1">{{ $statusInfo['icon'] }}</span>
                                        {{ $statusInfo['label'] }}
                                    </span>
                                    <span class="px-3 py-1.5 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ $templatePreview['category'] ?? 'UTILITY' }}
                                    </span>
                                    <span class="px-3 py-1.5 text-xs font-semibold rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ strtoupper($templatePreview['language'] ?? 'en') }}
                                    </span>
                                    @if(isset($templatePreview['quality_score']))
                                        <span class="px-3 py-1.5 text-xs font-semibold rounded 
                                            @if(($templatePreview['quality_score']['score'] ?? 'UNKNOWN') === 'GREEN') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                            @elseif(($templatePreview['quality_score']['score'] ?? 'UNKNOWN') === 'YELLOW') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                            @elseif(($templatePreview['quality_score']['score'] ?? 'UNKNOWN') === 'RED') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endif">
                                            {{ __('whatsapp::app.quality') }}: {{ $templatePreview['quality_score']['score'] ?? __('whatsapp::app.statusUnknown') }}
                                        </span>
                                    @endif
                                </div>
                                @if($status === 'REJECTED' && isset($templatePreview['reason']))
                                    <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                                        <p class="text-xs font-semibold text-red-800 dark:text-red-200 mb-1">{{ __('whatsapp::app.rejectionReason') }}:</p>
                                        <p class="text-xs text-red-700 dark:text-red-300">{{ $templatePreview['reason'] }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- WhatsApp Message Preview -->
                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="max-w-md mx-auto">
                                    <!-- WhatsApp Message Bubble -->
                                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 space-y-3" dir="ltr" style="text-align: left !important; direction: ltr !important;">
                                        @if(isset($templatePreview['components']))
                                            @foreach($templatePreview['components'] as $component)
                                                @if($component['type'] === 'HEADER')
                                                    <div class="font-semibold text-gray-900 dark:text-gray-100 text-base pb-2 border-b border-gray-200 dark:border-gray-700" dir="ltr" style="text-align: left !important; direction: ltr !important;">
                                                        @if($component['format'] === 'TEXT')
                                                            <div style="text-align: left !important; direction: ltr !important; display: block; width: 100%;">{{ $component['text'] ?? __('whatsapp::app.headerText') }}</div>
                                                        @elseif($component['format'] === 'IMAGE')
                                                            <div class="bg-gray-100 dark:bg-gray-700 rounded p-2 text-xs text-gray-500" style="text-align: left !important; direction: ltr !important;">{{ __('whatsapp::app.imagePlaceholder') }}</div>
                                                        @elseif($component['format'] === 'VIDEO')
                                                            <div class="bg-gray-100 dark:bg-gray-700 rounded p-2 text-xs text-gray-500" style="text-align: left !important; direction: ltr !important;">{{ __('whatsapp::app.videoPlaceholder') }}</div>
                                                        @elseif($component['format'] === 'DOCUMENT')
                                                            <div class="bg-gray-100 dark:bg-gray-700 rounded p-2 text-xs text-gray-500" style="text-align: left !important; direction: ltr !important;">{{ __('whatsapp::app.documentPlaceholder') }}</div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @if($component['type'] === 'BODY')
                                                    <div class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap" dir="ltr" style="text-align: left !important; direction: ltr !important; display: block; width: 100%;">{{ trim($component['text'] ?? __('whatsapp::app.bodyText')) }}</div>
                                                @endif

                                                @if($component['type'] === 'FOOTER')
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700" dir="ltr" style="text-align: left !important; direction: ltr !important; display: block; width: 100%;">{{ trim($component['text'] ?? __('whatsapp::app.footerText')) }}</div>
                                                @endif

                                                @if($component['type'] === 'BUTTONS' && isset($component['buttons']))
                                                    <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                        @foreach($component['buttons'] as $button)
                                                            @if($button['type'] === 'URL')
                                                                <div class="bg-blue-50 dark:bg-blue-900/20 px-3 py-2 rounded text-sm text-blue-600 dark:text-blue-400">
                                                                    🔗 {{ $button['text'] ?? __('whatsapp::app.button') }}
                                                                </div>
                                                            @elseif($button['type'] === 'QUICK_REPLY')
                                                                <div class="bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded text-sm text-gray-700 dark:text-gray-300">
                                                                    {{ $button['text'] ?? __('whatsapp::app.quickReply') }}
                                                                </div>
                                                            @elseif($button['type'] === 'PHONE_NUMBER')
                                                                <div class="bg-green-50 dark:bg-green-900/20 px-3 py-2 rounded text-sm text-green-600 dark:text-green-400">
                                                                    📞 {{ $button['text'] ?? __('whatsapp::app.call') }}
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Template Details -->
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('whatsapp::app.templateDetails') }}</h4>
                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-400">
                                    <div>
                                        <p><strong>{{ __('whatsapp::app.templateId') }}:</strong></p>
                                        <p class="font-mono text-xs break-all">{{ $templatePreview['id'] ?? __('whatsapp::app.na') }}</p>
                                    </div>
                                    <div>
                                        <p><strong>{{ __('whatsapp::app.name') }}:</strong></p>
                                        <p>{{ $templatePreview['name'] ?? __('whatsapp::app.na') }}</p>
                                    </div>
                                    <div>
                                        <p><strong>{{ __('whatsapp::app.status') }}:</strong></p>
                                        <p class="font-semibold">{{ $templatePreview['status'] ?? __('whatsapp::app.na') }}</p>
                                    </div>
                                    <div>
                                        <p><strong>{{ __('whatsapp::app.category') }}:</strong></p>
                                        <p>{{ $templatePreview['category'] ?? __('whatsapp::app.na') }}</p>
                                    </div>
                                    <div>
                                        <p><strong>{{ __('whatsapp::app.language') }}:</strong></p>
                                        <p>{{ strtoupper($templatePreview['language'] ?? 'en') }}</p>
                                    </div>
                                    @if(isset($templatePreview['message_send_tolerance']))
                                        <div>
                                            <p><strong>{{ __('whatsapp::app.sendTolerance') }}:</strong></p>
                                            <p>{{ $templatePreview['message_send_tolerance'] }}</p>
                                        </div>
                                    @endif
                                    @if(isset($templatePreview['components']))
                                        <div>
                                            <p><strong>{{ __('whatsapp::app.components') }}:</strong></p>
                                            <p>{{ count($templatePreview['components']) }} {{ count($templatePreview['components']) == 1 ? __('whatsapp::app.component') : __('whatsapp::app.componentsPlural') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Step 2: Category -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">2</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('whatsapp::app.step2Category') }}
                                </label>
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                                    <span class="px-3 py-1 text-sm font-semibold rounded 
                                        @if($templateDetails['category'] === 'UTILITY') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                        @elseif($templateDetails['category'] === 'MARKETING') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                        @elseif($templateDetails['category'] === 'AUTHENTICATION') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        {{ $templateDetails['category'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ __('whatsapp::app.selectCategory') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Language -->
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">3</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('whatsapp::app.step3Language') }}
                                </label>
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                                    <code class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ strtoupper($templateDetails['language']) }}</code>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ __('whatsapp::app.selectLanguage') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Header -->
                    @if($templateDetails['header'])
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">4</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    {{ __('whatsapp::app.step4Header') }}
                                </label>
                                
                                @if($templateDetails['header']['text'])
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                        {{ __('whatsapp::app.headerText') }}:
                                    </label>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                                        <div class="flex items-center justify-between">
                                            <code class="font-mono text-sm text-gray-900 dark:text-gray-100 break-all">{{ $templateDetails['header']['text'] }}</code>
                                            <button type="button" 
                                                x-data="{ copied: false }"
                                                @click="navigator.clipboard.writeText('{{ addslashes($templateDetails['header']['text']) }}').then(() => { copied = true; setTimeout(() => { copied = false; }, 2000); })"
                                                class="ml-2 px-2 py-1 text-xs text-skin-base hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
                                                <span x-show="!copied">{{ __('whatsapp::app.copy') }}</span>
                                                <span x-show="copied" class="text-green-600 dark:text-green-400">{{ __('whatsapp::app.copied') }}</span>
                                            </button>
                                        </div>
                                    </div>
                                    @if(str_contains($templateDetails['header']['text'], '{{1}}'))
                                    <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                                        <p class="text-xs text-red-800 dark:text-red-200">
                                            <strong>⚠️ {{ __('whatsapp::app.important') }}:</strong> {{ __('whatsapp::app.headerCannotStartWithVariable') }} 
                                            @if(str_starts_with(trim($templateDetails['header']['text']), '{{1}}'))
                                                <strong class="block mt-1 text-red-600 dark:text-red-400">{{ __('whatsapp::app.thisHeaderStartsWithVariable') }}</strong>
                                                <span class="block mt-1">{{ __('whatsapp::app.addStaticTextBeforeVariable') }} <code class="bg-red-100 dark:bg-red-800 px-1 rounded">Status: {{1}}</code></span>
                                            @else
                                                <span class="block mt-1">{{ __('whatsapp::app.headerFormatCorrect') }}</span>
                                            @endif
                                            <strong class="block mt-2">{{ __('whatsapp::app.remember') }}:</strong> {{ __('whatsapp::app.headerVariablesSeparateScope') }}
                                        </p>
                                    </div>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('whatsapp::app.pasteInHeaderField') }}
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Step 5: Body -->
                    @if($templateDetails['body'])
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">5</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    {{ __('whatsapp::app.step5Body') }}
                                </label>
                                
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600 mb-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <code class="font-mono text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words flex-1">{{ $templateDetails['body']['text'] }}</code>
                                        <button type="button" 
                                            x-data="{ copied: false, bodyText: @js($templateDetails['body']['text']) }"
                                            @click="navigator.clipboard.writeText(bodyText).then(() => { copied = true; setTimeout(() => { copied = false; }, 2000); })"
                                            class="ml-2 px-2 py-1 text-xs text-skin-base hover:bg-gray-200 dark:hover:bg-gray-600 rounded flex-shrink-0 transition-colors">
                                            <span x-show="!copied">{{ __('whatsapp::app.copy') }}</span>
                                            <span x-show="copied" class="text-green-600 dark:text-green-400">{{ __('whatsapp::app.copied') }}</span>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                    {{ __('whatsapp::app.pasteInBodyField') }}
                                </p>
                                
                                @if(!empty($templateDetails['variables']))
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded border border-yellow-200 dark:border-yellow-800">
                                    <label class="block text-xs font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                        {{ __('whatsapp::app.variablesToAdd') }}:
                                    </label>
                                    
                                    @php
                                        $headerVars = [];
                                        $bodyVars = [];
                                        $bodyVarCounter = 1;
                                        foreach($templateDetails['variables'] as $index => $variable) {
                                            if(str_starts_with($variable, 'Header:')) {
                                                $headerVars[] = ['varIndex' => 1, 'desc' => str_replace('Header: ', '', $variable)];
                                            } elseif(str_starts_with($variable, 'Body ')) {
                                                // Extract body variable index from "Body 1:", "Body 2:", etc.
                                                preg_match('/Body (\d+):/', $variable, $matches);
                                                $bodyVarIndex = isset($matches[1]) ? (int)$matches[1] : $bodyVarCounter;
                                                $bodyVars[] = ['varIndex' => $bodyVarIndex, 'desc' => preg_replace('/Body \d+:\s*/', '', $variable)];
                                                $bodyVarCounter++;
                                            } else {
                                                // Fallback: assume body variable if no prefix
                                                $bodyVars[] = ['varIndex' => $bodyVarCounter, 'desc' => $variable];
                                                $bodyVarCounter++;
                                            }
                                        }
                                    @endphp
                                    
                                    @if(!empty($headerVars))
                                    <div class="mb-3">
                                        <label class="block text-xs font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                            {{ __('whatsapp::app.headerVariablesStartFrom') }}
                                        </label>
                                        <div class="space-y-2">
                                            @foreach($headerVars as $var)
                                            <div class="flex items-center gap-3 text-xs bg-white dark:bg-gray-800 p-2 rounded">
                                                <span class="font-mono bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">
                                                    @php
                                                        $varNum = $var['varIndex'];
                                                        $varPlaceholder = '{{' . $varNum . '}}';
                                                    @endphp
                                                    {!! $varPlaceholder !!}
                                                </span>
                                                <span class="text-gray-700 dark:text-gray-300 flex-1">{{ $var['desc'] }}</span>
                                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                    {{ __('whatsapp::app.text') }}
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    
                                    @if(!empty($bodyVars))
                                    <div>
                                        <label class="block text-xs font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                            {{ __('whatsapp::app.bodyVariablesStartFrom') }}
                                        </label>
                                        <div class="space-y-2">
                                            @foreach($bodyVars as $var)
                                            <div class="flex items-center gap-3 text-xs bg-white dark:bg-gray-800 p-2 rounded">
                                                <span class="font-mono bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">
                                                    @php
                                                        $varNum = $var['varIndex'];
                                                        $varPlaceholder = '{{' . $varNum . '}}';
                                                    @endphp
                                                    {!! $varPlaceholder !!}
                                                </span>
                                                <span class="text-gray-700 dark:text-gray-300 flex-1">{{ $var['desc'] }}</span>
                                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                    {{ __('whatsapp::app.text') }}
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-3">
                                        <strong>{{ __('whatsapp::app.important') }}:</strong> {{ __('whatsapp::app.variablesSeparateScopes') }}
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Step 6: Buttons -->
                    @if(isset($templateDetails['buttons']) && is_array($templateDetails['buttons']) && count($templateDetails['buttons']) > 0)
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">6</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    {{ __('whatsapp::app.step7Buttons') }}
                                </label>
                                <div class="space-y-3">
                                    @foreach($templateDetails['buttons'] as $buttonIndex => $button)
                                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <div class="mb-3">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                {{ __('whatsapp::app.buttonType') }}:
                                            </label>
                                            <span class="px-3 py-1 text-sm font-semibold rounded 
                                                @if($button['type'] === 'QUICK_REPLY') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                @elseif($button['type'] === 'URL') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                                @elseif($button['type'] === 'PHONE_NUMBER') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                                @elseif($button['type'] === 'OTP') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                                @endif">
                                                @if($button['type'] === 'QUICK_REPLY') {{ __('whatsapp::app.quickReply') }}
                                                @elseif($button['type'] === 'URL') {{ __('whatsapp::app.visitWebsite') }}
                                                @elseif($button['type'] === 'PHONE_NUMBER') {{ __('whatsapp::app.callPhoneNumber') }}
                                                @elseif($button['type'] === 'OTP') {{ __('whatsapp::app.copyOfferCode') }}
                                                @else {{ $button['type'] }}
                                                @endif
                                            </span>
                                        </div>
                                        @if($button['text'])
                                        <div class="mb-2">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                {{ __('whatsapp::app.buttonText') }}:
                                            </label>
                                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded border">
                                                <code class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $button['text'] }}</code>
                                                <button type="button" 
                                                    x-data="{ copied: false }"
                                                    @click="navigator.clipboard.writeText('{{ addslashes($button['text']) }}').then(() => { copied = true; setTimeout(() => { copied = false; }, 2000); })"
                                                    class="ml-2 px-2 py-1 text-xs text-skin-base hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
                                                    <span x-show="!copied">{{ __('whatsapp::app.copy') }}</span>
                                                    <span x-show="copied" class="text-green-600 dark:text-green-400">{{ __('whatsapp::app.copied') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                        @endif
                                        @if($button['url'])
                                        <div class="mb-2">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                {{ __('whatsapp::app.url') }}:
                                            </label>
                                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded border mb-2">
                                                <code class="font-mono text-xs text-gray-900 dark:text-gray-100 break-all">{{ $button['original_url'] ?? $button['url'] }}</code>
                                                <button type="button" 
                                                    x-data="{ copied: false }"
                                                    @click="navigator.clipboard.writeText('{{ addslashes($button['original_url'] ?? $button['url']) }}').then(() => { copied = true; setTimeout(() => { copied = false; }, 2000); })"
                                                    class="ml-2 px-2 py-1 text-xs text-skin-base hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
                                                    <span x-show="!copied">{{ __('whatsapp::app.copy') }}</span>
                                                    <span x-show="copied" class="text-green-600 dark:text-green-400">{{ __('whatsapp::app.copied') }}</span>
                                                </button>
                                            </div>
                                            @if(!empty($button['url_variables']))
                                            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded border border-yellow-200 dark:border-yellow-800 mb-2">
                                                <label class="block text-xs font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                                    {{ __('whatsapp::app.buttonUrlVariables') }}:
                                                </label>
                                                <div class="space-y-2">
                                                    @foreach($button['url_variables'] as $urlVar)
                                                    <div class="flex items-center gap-3 text-xs bg-white dark:bg-gray-800 p-2 rounded">
                                                        <span class="font-mono bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">
                                                            {!! $urlVar['var'] !!}
                                                        </span>
                                                        <span class="text-gray-700 dark:text-gray-300 flex-1">{{ $urlVar['description'] }}</span>
                                                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                            {{ __('whatsapp::app.text') }}
                                                        </span>
                                                    </div>
                                                    @endforeach
                                                </div>
                                                <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-2">
                                                    <strong>{{ __('whatsapp::app.important') }}:</strong> {{ __('whatsapp::app.buttonUrlVariableDescription') }}
                                                </p>
                                            </div>
                                            @endif
                                            @if($button['example'])
                                            <div class="mt-2">
                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                    {{ __('whatsapp::app.exampleUrl') }}:
                                                </label>
                                                <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded text-xs text-gray-600 dark:text-gray-400">
                                                    {{ $button['example'] }}
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                        @if($button['phone_number'])
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                {{ __('whatsapp::app.phoneNumber') }}:
                                            </label>
                                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2 rounded border">
                                                <code class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $button['phone_number'] }}</code>
                                                <button type="button" 
                                                    x-data="{ copied: false }"
                                                    @click="navigator.clipboard.writeText('{{ addslashes($button['phone_number']) }}').then(() => { copied = true; setTimeout(() => { copied = false; }, 2000); })"
                                                    class="ml-2 px-2 py-1 text-xs text-skin-base hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
                                                    <span x-show="!copied">{{ __('whatsapp::app.copy') }}</span>
                                                    <span x-show="copied" class="text-green-600 dark:text-green-400">{{ __('whatsapp::app.copied') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                                    {{ __('whatsapp::app.addButtonsInOrder') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Step 7: Footer -->
                    @if($templateDetails['footer'] && $templateDetails['footer']['text'])
                    <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border-2 border-blue-200 dark:border-blue-800">
                        <div class="flex items-start gap-3 mb-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-sm">7</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    {{ __('whatsapp::app.step6Footer') }}
                                </label>
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <code class="font-mono text-sm text-gray-900 dark:text-gray-100">{{ $templateDetails['footer']['text'] }}</code>
                                        <button type="button" 
                                            x-data="{ copied: false }"
                                            @click="navigator.clipboard.writeText('{{ addslashes($templateDetails['footer']['text']) }}').then(() => { copied = true; setTimeout(() => { copied = false; }, 2000); })"
                                            class="ml-2 px-2 py-1 text-xs text-skin-base hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors">
                                            <span x-show="!copied">{{ __('whatsapp::app.copy') }}</span>
                                            <span x-show="copied" class="text-green-600 dark:text-green-400">{{ __('whatsapp::app.copied') }}</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                                    <p class="text-xs text-red-800 dark:text-red-200">
                                        <strong>⚠️ {{ __('whatsapp::app.critical') }}:</strong> {{ __('whatsapp::app.footerMustBeStatic') }} 
                                        {{ __('whatsapp::app.footerNoVariablesAllowed') }} 
                                        {{ __('whatsapp::app.useExactTextNoVariables') }}
                                    </p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ __('whatsapp::app.pasteInFooterField') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Final Step: Submit for Review -->
                    <div class="bg-green-50 dark:bg-green-900/20 p-5 rounded-lg border-2 border-green-200 dark:border-green-800">
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm">✓</span>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-green-800 dark:text-green-200 mb-2">
                                    {{ __('whatsapp::app.finalStep') }}
                                </label>
                                <p class="text-sm text-green-700 dark:text-green-300 mb-3">
                                    {{ __('whatsapp::app.reviewAndSubmit') }}
                                </p>
                                <a href="https://business.facebook.com/latest/whatsapp_manager/message_templates" target="_blank"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                                    {{ __('whatsapp::app.openMetaPortal') }}
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Template JSON (Reference - Collapsible) -->
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div x-data="{ showJson: false }">
                            <button type="button" @click="showJson = !showJson" 
                                class="flex items-center justify-between w-full text-sm font-semibold text-gray-700 dark:text-gray-300">
                                <span>{{ __('whatsapp::app.viewJsonReference') }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showJson }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="showJson" x-transition class="mt-3">
                                <div class="relative">
                                    <pre class="p-4 bg-gray-900 text-gray-100 rounded-lg overflow-x-auto text-xs font-mono"><code>{{ $templateJson }}</code></pre>
                                    <div class="absolute top-2 right-2">
                                        <button type="button" wire:click="copyTemplateJson" 
                                            class="px-3 py-1 text-xs font-medium text-white bg-skin-base rounded hover:bg-skin-base/90">
                                            {{ __('whatsapp::app.copy') }}
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ __('whatsapp::app.jsonForReference') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($selectedTemplate)
            <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    {{ __('whatsapp::app.templateDefinitionNotFound') }}
                </p>
            </div>
        @endif
    </div>

    @script
    <script>
        $wire.on('copy-to-clipboard', (event) => {
            navigator.clipboard.writeText(event.content).then(() => {
                // Success handled by alert
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        });
    </script>
    @endscript
</div>
