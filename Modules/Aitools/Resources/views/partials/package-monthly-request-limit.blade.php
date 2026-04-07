
@if(module_enabled('Aitools'))
    <div class="mt-6">
        <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        @lang('aitools::app.settings.monthlyTokenLimitInfo')
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <x-label for="aiMonthlyTokenLimit" value="{{ __('aitools::app.settings.monthlyTokenLimit') }}" required="true" class="text-sm font-medium text-gray-700 dark:text-gray-300"/>
            <x-input id="aiMonthlyTokenLimit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm" type="number" min="-1" wire:model.live='aiMonthlyTokenLimit' />
            <x-input-error for="aiMonthlyTokenLimit" class="mt-2" />
            <p class="mt-1 text-sm text-gray-500">@lang('aitools::app.settings.monthlyTokenLimitDescription')</p>
        </div>
    </div>
@endif



