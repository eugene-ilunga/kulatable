<div class="space-y-6">
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-lg">{{ __('webhooks::webhooks.pkg_defaults_title') }}</h3>
                <p class="text-xs text-gray-500">{{ __('webhooks::webhooks.pkg_defaults_desc') }}</p>
            </div>
            <button type="button" wire:click="save" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                <span wire:loading.remove wire:target="save">{{ __('webhooks::webhooks.save') }}</span>
                <span wire:loading wire:target="save">{{ __('webhooks::webhooks.saving') }}</span>
            </button>
        </div>
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Package Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('webhooks::webhooks.package') }}</label>
                    <select wire:model="selectedPackage" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @foreach ($packages as $pkg)
                            <option value="{{ $pkg['id'] }}">{{ $pkg['name'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500">
                        {{ __('Select a package to configure its default webhook settings.') }}
                    </p>
                </div>

                <!-- Settings Form -->
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <label class="inline-flex items-start space-x-3 rtl:space-x-reverse cursor-pointer">
                            <div class="flex items-center h-5">
                                <input type="checkbox" wire:model="form.auto_provision" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-900 block">{{ __('webhooks::webhooks.auto_provision') }}</span>
                                <span class="text-xs text-gray-500 block">{{ __('Automatically create a webhook for new tenants on this package.') }}</span>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.default_target_url') }}</label>
                        <input type="text" wire:model.defer="form.default_target_url" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="https://example.com/webhooks">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.default_secret') }}</label>
                            <input type="text" wire:model.defer="form.default_secret" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="{{ __('webhooks::webhooks.auto_generate_hint') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">{{ __('webhooks::webhooks.rotate_interval') }}</label>
                            <input type="number" min="1" wire:model.defer="form.rotate_interval_days" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Allowed Events Selection -->
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h4 class="font-semibold text-sm text-gray-700 uppercase tracking-wider">{{ __('webhooks::webhooks.allowed_events') }}</h4>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($catalog as $evt)
                        <label class="relative flex items-start p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors
                            {{ in_array($evt->event_key, $form['allowed_events'], true) ? 'bg-indigo-50 border-indigo-200 ring-1 ring-indigo-200' : '' }}">
                            <div class="flex items-center h-5">
                                <input type="checkbox" wire:click="toggleEvent('{{ $evt->event_key }}')" @checked(in_array($evt->event_key, $form['allowed_events'], true)) class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </div>
                            <div class="ltr:ml-3 rtl:mr-3 text-sm">
                                <span class="font-medium text-gray-900 block">{{ $evt->event_key }}</span>
                                <span class="text-xs text-gray-500 block">{{ $evt->module }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
