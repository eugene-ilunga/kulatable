<div wire:id="rest-api-settings">
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm 2xl:col-span-2 dark:border-gray-700 sm:p-6 dark:bg-gray-800">

        <div class="space-y-6" x-data>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('applicationintegration::messages.breadcrumb') }}</p>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('applicationintegration::messages.api_docs') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ __('applicationintegration::messages.api_docs_help') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $docUrl }}" target="_blank" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold bg-skin-base text-white hover:opacity-90 transition">
                            {{ __('applicationintegration::messages.open_docs') }}
                        </a>
                        <button wire:click="copyDocs" onclick="window.aiInstantCopy('{{ $docUrl }}')" data-copy-link="{{ $docUrl }}" type="button" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold border border-gray-300 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            {{ __('applicationintegration::messages.copy_link') }}
                        </button>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-700 dark:text-gray-200 break-words">
                    {{ $docUrl }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('applicationintegration::app.public_link') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('applicationintegration::messages.public_link_help') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="generatePublicLink" type="button" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold bg-skin-base text-white hover:opacity-90 transition">
                            {{ __('applicationintegration::messages.generate_link') }}
                        </button>
                        @if($publicLink)
                            <button wire:click="revokePublicLink" type="button" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold border border-red-200 text-red-600 hover:bg-red-50 transition">
                                {{ __('applicationintegration::messages.revoke_link') }}
                            </button>
                        @endif
                        @if($publicLink)
                            <button wire:click="copyPublic" onclick="window.aiInstantCopy('{{ $publicLink }}')" data-copy-link="{{ $publicLink }}" type="button" class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-semibold border border-gray-300 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                {{ __('applicationintegration::messages.copy_link') }}
                            </button>
                        @endif
                    </div>
                </div>
                @if($publicLink)
                    <div class="mt-3 text-sm text-gray-700 dark:text-gray-200 break-words">
                        {{ $publicLink }}
                    </div>
                @else
                    <div class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('applicationintegration::messages.public_link_empty') }}
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('applicationintegration::messages.firebase_title') }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('applicationintegration::messages.firebase_help') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <input type="checkbox" wire:model.defer="firebaseEnabled"
                                       class="rounded border-gray-300 text-skin-base focus:ring-skin-base">
                                <span>{{ __('applicationintegration::messages.firebase_enabled') }}</span>
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('applicationintegration::messages.firebase_enabled_help') }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ __('applicationintegration::messages.firebase_json_label') }}
                            </label>
                            <input type="file"
                                   wire:model="firebaseServiceAccountJson"
                                   accept=".json,application/json,text/plain"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                            @error('firebaseServiceAccountJson')
                                <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                            @if(!empty($firebaseServiceAccountJsonName))
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('applicationintegration::messages.firebase_json_current', ['file' => $firebaseServiceAccountJsonName]) }}
                                </p>
                                <div class="mt-2 flex gap-2">
                                    <button
                                        type="button"
                                        wire:click="toggleFirebaseJsonPreview"
                                        class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold border border-gray-300 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                                    >
                                        {{ $showFirebaseJson ? __('applicationintegration::messages.firebase_json_hide') : __('applicationintegration::messages.firebase_json_view') }}
                                    </button>
                                </div>
                                @if($showFirebaseJson)
                                    <pre class="mt-3 text-xs font-mono whitespace-pre overflow-x-auto max-h-80 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-gray-800 dark:text-gray-100">{{ $firebaseJsonPreview ?? __('applicationintegration::messages.firebase_json_not_found') }}</pre>
                                @endif
                            @endif
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('applicationintegration::messages.firebase_json_help') }}
                            </p>
                        </div>

                        <div class="pt-1">
                            <button wire:click="saveFirebaseSettings"
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold bg-skin-base text-white hover:opacity-90 transition">
                                {{ __('applicationintegration::messages.firebase_save_button') }}
                            </button>
                        </div>

                        @if(!empty($firebaseSettingsMessage))
                            @php
                                $bg = ($firebaseSettingsMessageType === 'success') ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-900 text-emerald-700 dark:text-emerald-200' : 'bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-200';
                            @endphp
                            <div class="border rounded-lg p-3 text-sm {{ $bg }}">
                                {{ $firebaseSettingsMessage }}
                            </div>
                        @endif
                    </div>

                    <div class="text-xs text-gray-600 dark:text-gray-300 space-y-2">
                        <p class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ __('applicationintegration::messages.firebase_json_steps_title') }}
                        </p>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>
                                {{ __('applicationintegration::messages.open') }}
                                <a href="https://console.cloud.google.com/iam-admin/serviceaccounts"
                                   class="text-skin-base hover:underline"
                                   target="_blank" rel="noopener noreferrer">
                                    {{ __('applicationintegration::messages.firebase_json_step_1') }}
                                </a>
                            </li>
                            <li>{{ __('applicationintegration::messages.firebase_json_step_2') }}</li>
                            <li>{{ __('applicationintegration::messages.firebase_json_step_3') }}</li>
                            <li>{{ __('applicationintegration::messages.firebase_json_step_4') }}</li>
                            <li>{{ __('applicationintegration::messages.firebase_json_step_5') }}</li>
                            <li>{{ __('applicationintegration::messages.firebase_json_step_6') }}</li>
                            <li>{{ __('applicationintegration::messages.firebase_json_step_7') }}</li>
                            <li>{{ __('applicationintegration::messages.firebase_json_step_8') }}</li>
                        </ol>
                    </div>
                </div>

            </div>

            <div class="relative min-h-[320px] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm space-y-4">
                {{-- Loading overlay: blocks entire tester section while runDiagnostics runs --}}
                <div
                    wire:loading
                    wire:target="runDiagnostics"
                    class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/90 dark:bg-gray-900/90 backdrop-blur-sm"
                >
                    <div class="flex flex-col items-center gap-3">
                        <svg class="animate-spin h-10 w-10 text-skin-base" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('applicationintegration::messages.testing') }}</span>
                    </div>
                </div>

                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('applicationintegration::messages.tester_title') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('applicationintegration::messages.tester_help') }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ __('applicationintegration::messages.status_ok') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400"></span>{{ __('applicationintegration::messages.status_permission') }}</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span>{{ __('applicationintegration::messages.status_fail') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-1 space-y-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('applicationintegration::messages.username') }}</label>
                        <input type="text" wire:model.defer="email" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" placeholder="admin@example.com">

                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('applicationintegration::messages.password') }}</label>
                        <input type="password" wire:model.defer="password" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('applicationintegration::messages.password_placeholder') }}">

                        <button wire:click="runDiagnostics" wire:loading.attr="disabled" type="button" class="w-full inline-flex justify-center items-center px-4 py-2 rounded-lg text-sm font-semibold bg-skin-base text-white hover:opacity-90 transition disabled:opacity-70 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="runDiagnostics">{{ __('applicationintegration::messages.run_test') }}</span>
                            <span wire:loading wire:target="runDiagnostics">{{ __('applicationintegration::messages.testing') }}</span>
                        </button>
                    </div>

                    <div class="md:col-span-2">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($results as $result)
                                <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800/50">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $result['label'] }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300">
                                            {{ $result['message'] }}
                                            @if(!empty($result['http_status'])) (HTTP {{ $result['http_status'] }}) @endif
                                        </p>
                                    </div>
                                    @php
                                        $color = $result['status'] === 'ok' ? 'bg-emerald-500' : ($result['status'] === 'permission' ? 'bg-amber-400' : 'bg-rose-500');
                                        $text = $result['status'] === 'ok' ? __('applicationintegration::messages.status_ok') : ($result['status'] === 'permission' ? __('applicationintegration::messages.status_permission') : __('applicationintegration::messages.status_fail'));
                                    @endphp
                                    <span class="inline-flex items-center gap-2 text-xs font-semibold px-2 py-1 rounded-full text-white {{ $color }}">{{ $text }}</span>
                                </div>
                            @empty
                                <div class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">
                                    {{ __('applicationintegration::messages.no_tests') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if (!window.aiInstantCopy) {
        window.aiInstantCopy = (text) => {
            if (!text) return;
            const fallback = (t) => {
                const el = document.createElement('textarea');
                el.value = t;
                el.setAttribute('readonly', '');
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(el);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).catch(() => fallback(text));
            } else {
                fallback(text);
            }
        };
    }

    document.addEventListener('livewire:load', () => {
        if (window.Livewire) {
            Livewire.on('ai-doc-link', (payload) => {
                const value = payload?.value ?? payload;
                if (!value) return;

                const copyFallback = (text) => {
                    const el = document.createElement('textarea');
                    el.value = text;
                    el.setAttribute('readonly', '');
                    el.style.position = 'absolute';
                    el.style.left = '-9999px';
                    document.body.appendChild(el);
                    el.select();
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(el);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).catch(() => copyFallback(value));
                } else {
                    copyFallback(value);
                }

                // Simple user feedback
                if (window.toast) {
                    window.toast('{{ __('applicationintegration::messages.copy_link') }}', { type: 'success' });
                }
            });
            Livewire.on('ai-test-finished', () => {
                // placeholder for future UI feedback
            });
        }

        const directCopy = (text) => {
            if (!text) return;
            const fallback = (t) => {
                const el = document.createElement('textarea');
                el.value = t;
                el.setAttribute('readonly', '');
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(el);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).catch(() => fallback(text));
            } else {
                fallback(text);
            }
        };

        const attachCopyHandlers = () => {
            document.querySelectorAll('[data-copy-link]').forEach(btn => {
                btn.onclick = () => {
                    const val = btn.getAttribute('data-copy-link') || btn.innerText;
                    directCopy(val);
                };
            });
        };

        // Initial attach and re-attach after Livewire updates
        attachCopyHandlers();
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                attachCopyHandlers();
            });
        }
    });
</script>



