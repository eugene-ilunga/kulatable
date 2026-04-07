<div class="grid grid-cols-1 gap-6 mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
    <div class="flex flex-col gap-2">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('fontcontrol::messages.name') }}</h3>
        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('fontcontrol::messages.description') }}</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid gap-4">
            @foreach ($fonts as $index => $font)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900/40">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $font['language_name'] }}
                            @if (!empty($font['is_default']))
                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ __('fontcontrol::messages.default_label') }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('fontcontrol::messages.preview') }}</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-label :for="'font-family-'.$index" :value="__('fontcontrol::messages.font_family')" />
                            <x-input :id="'font-family-'.$index" class="mt-1 block w-full"
                                     wire:model.defer="fonts.{{ $index }}.font_family" />
                            <x-input-error :for="'fonts.'.$index.'.font_family'" class="mt-1" />
                        </div>

                        <div>
                            <x-label :for="'font-size-'.$index" :value="__('fontcontrol::messages.font_size')" />
                            <x-input :id="'font-size-'.$index" type="number" min="10" max="30"
                                     class="mt-1 block w-full"
                                     wire:model.defer="fonts.{{ $index }}.font_size" />
                            <x-input-error :for="'fonts.'.$index.'.font_size'" class="mt-1" />
                        </div>

                        <div>
                            <x-label :for="'font-url-'.$index" :value="__('fontcontrol::messages.font_url')" />
                            <x-input :id="'font-url-'.$index" class="mt-1 block w-full"
                                     wire:model.defer="fonts.{{ $index }}.font_url"
                                     placeholder="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" />
                            <x-input-error :for="'fonts.'.$index.'.font_url'" class="mt-1" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('fontcontrol::messages.fallback_note') }}</p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-md bg-white dark:bg-gray-800 border border-dashed border-gray-200 dark:border-gray-700"
                         style="font-family: {{ e($font['font_family']) }}, Figtree, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: {{ (int) $font['font_size'] }}px;">
                        @php $sampleLocal = __('fontcontrol::messages.sample_local', ['lang' => $font['language_name']]); @endphp
                        <div class="text-sm text-gray-700 dark:text-gray-200">
                            {{ __('fontcontrol::messages.sample_en') }}
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-200 mt-1">
                            {{ $sampleLocal }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between pt-3 pb-2 border-t border-gray-200 dark:border-gray-700 mt-2">
            <div class="space-y-1">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('fontcontrol::messages.preview') }} updates after saving.</div>
                @if (session()->has('fonts_status'))
                    <div class="text-xs text-green-600 dark:text-green-400">{{ session('fonts_status') }}</div>
                @endif
            </div>
            <x-button type="submit">{{ __('fontcontrol::messages.save_fonts') }}</x-button>
        </div>

        {{-- QR Settings: Only visible for Restaurant Admins (not Super Admin) --}}
        @if ($restaurantId !== null)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex flex-col gap-2 mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('fontcontrol::messages.qr_title') }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('fontcontrol::messages.qr_desc') }}</p>
                @if (!empty($activeLanguages))
                    <div class="flex flex-wrap gap-2 text-xs text-gray-600 dark:text-gray-300">
                        @foreach ($activeLanguages as $lang)
                            <span class="px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                {{ $lang['name'] ?? $lang['code'] }} ({{ $lang['code'] }})
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <label class="inline-flex items-center space-x-2">
                        <input type="checkbox" wire:model.defer="qr.advanced_qr_enabled" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ __('fontcontrol::messages.advanced_qr_toggle') }}</span>
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('fontcontrol::messages.advanced_qr_hint') }}</p>
                </div>
                <div class="space-y-2">
                    <x-label for="qr-label-format" :value="__('fontcontrol::messages.label_format')" />
                    <x-input id="qr-label-format" class="w-full" wire:model.defer="qr.label_format" placeholder="{table_code}" />
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('fontcontrol::messages.label_format_hint') }}</p>
                    <x-input-error for="qr.label_format" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-font-family" :value="__('fontcontrol::messages.font_family')" />
                    <x-input id="qr-font-family" class="w-full" wire:model.defer="qr.font_family" />
                    <x-input-error for="qr.font_family" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-font-size" :value="__('fontcontrol::messages.font_size')" />
                    <x-input id="qr-font-size" type="number" min="10" max="48" class="w-full" wire:model.defer="qr.font_size" />
                    <x-input-error for="qr.font_size" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-ecc-level" value="{{ __('fontcontrol::messages.qr_ecc') }}" />
                    <select id="qr-ecc-level" class="w-full rounded border-gray-300" wire:model.defer="qr.qr_ecc_level">
                        <option value="NONE">{{ __('fontcontrol::messages.ecc_none') }}</option>
                        <option value="L">L (7%)</option>
                        <option value="M">M (15%)</option>
                        <option value="Q">Q (25%)</option>
                        <option value="H">H (30%)</option>
                    </select>
                    <x-input-error for="qr.qr_ecc_level" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-font-url" :value="__('fontcontrol::messages.qr_font_url')" />
                    <x-input id="qr-font-url" class="w-full" wire:model.defer="qr.font_url" placeholder="https://fonts.googleapis.com/..." />
                    <x-input-error for="qr.font_url" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-size" :value="__('fontcontrol::messages.qr_size')" />
                    <x-input id="qr-size" type="number" min="120" max="800" class="w-full" wire:model.defer="qr.qr_size" />
                    <x-input-error for="qr.qr_size" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-margin" :value="__('fontcontrol::messages.qr_margin')" />
                    <x-input id="qr-margin" type="number" min="0" max="50" class="w-full" wire:model.defer="qr.qr_margin" />
                    <x-input-error for="qr.qr_margin" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-foreground" :value="__('fontcontrol::messages.foreground')" />
                    <div class="flex items-center gap-2">
                        <input id="qr-foreground" type="color" class="h-10 w-14 p-0 border rounded" wire:model.defer="qr.qr_foreground_color" />
                        <x-input class="flex-1" wire:model.defer="qr.qr_foreground_color" placeholder="#000000" />
                    </div>
                    <x-input-error for="qr.qr_foreground_color" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-background" :value="__('fontcontrol::messages.background')" />
                    <div class="flex items-center gap-2">
                        <input id="qr-background" type="color" class="h-10 w-14 p-0 border rounded" wire:model.defer="qr.qr_background_color" />
                        <x-input class="flex-1" wire:model.defer="qr.qr_background_color" placeholder="#FFFFFF" />
                    </div>
                    <x-input-error for="qr.qr_background_color" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-label-color" :value="__('fontcontrol::messages.label_color')" />
                    <div class="flex items-center gap-2">
                        <input id="qr-label-color" type="color" class="h-10 w-14 p-0 border rounded" wire:model.defer="qr.label_color" />
                        <x-input class="flex-1" wire:model.defer="qr.label_color" placeholder="#000000" />
                    </div>
                    <x-input-error for="qr.label_color" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-logo" :value="__('fontcontrol::messages.qr_logo')" />
                    <input id="qr-logo" type="file" class="block w-full text-sm" wire:model="qr_logo_upload" accept="image/*" />
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('fontcontrol::messages.qr_logo_hint') }}</p>
                    <x-input-error for="qr_logo_upload" />
                </div>
                <div class="space-y-2">
                    <x-label for="qr-logo-size" :value="__('fontcontrol::messages.qr_logo_size')" />
                    <x-input id="qr-logo-size" type="number" min="10" max="80" class="w-full" wire:model.defer="qr.qr_logo_size" />
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('fontcontrol::messages.qr_logo_size_hint') }}</p>
                    <x-input-error for="qr.qr_logo_size" />
                </div>
                <div class="space-y-2">
                    <label class="inline-flex items-center space-x-2">
                        <input type="checkbox" wire:model.defer="qr.qr_round_block_size" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <span class="text-sm text-gray-700 dark:text-gray-200">{{ __('fontcontrol::messages.round_block_size') }}</span>
                    </label>
                    <x-input-error for="qr.qr_round_block_size" />
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 flex-wrap gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <x-button type="button"
                              wire:click="previewQr"
                              wire:loading.attr="disabled"
                              wire:target="previewQr"
                              class="bg-indigo-500 hover:bg-indigo-600 text-white flex items-center gap-2">
                        <span wire:loading.remove wire:target="previewQr">{{ __('fontcontrol::messages.preview_qr') }}</span>
                        <span wire:loading wire:target="previewQr" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span>{{ __('fontcontrol::messages.loading') }}</span>
                        </span>
                    </x-button>
                    <x-button type="button"
                              wire:click="generateStoreQr"
                              wire:loading.attr="disabled"
                              wire:target="generateStoreQr"
                              class="bg-teal-500 hover:bg-teal-600 text-white flex items-center gap-2">
                        <span wire:loading.remove wire:target="generateStoreQr">{{ __('fontcontrol::messages.generate_store_qr') }}</span>
                        <span wire:loading wire:target="generateStoreQr" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <span>{{ __('fontcontrol::messages.loading') }}</span>
                        </span>
                    </x-button>
                    {{-- regenerateNow button removed - it regenerated QR for entire server --}}
                    <x-button type="submit" class="flex items-center gap-2">
                        {{ __('fontcontrol::messages.save_qr_settings') }}
                    </x-button>
                </div>

                @if ($qrPreviewData)
                    <div class="border border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-3 bg-white dark:bg-gray-900 flex items-center gap-3">
                        <img src="{{ $qrPreviewData }}" alt="QR preview" class="h-32 w-32 object-contain">
                        <div class="text-xs text-gray-600 dark:text-gray-300">
                            {{ __('fontcontrol::messages.preview_note') }}
                        </div>
                    </div>
                @endif
            </div>

            @if (session()->has('qr_status'))
                <div class="text-sm text-green-600 dark:text-green-400 mt-2">{{ session('qr_status') }}</div>
            @endif
        </div>
        @endif
        {{-- End QR Settings --}}
    </form>
</div>
