<div>
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="me-2">
                <button type="button" wire:click="setTab('taxes')"
                    @class([
                        'inline-block p-4 border-b-2 rounded-t-lg transition',
                        'border-skin-base text-skin-base' => $activeTab === 'taxes',
                        'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' => $activeTab !== 'taxes',
                    ])>
                    {{ __('hotel::modules.settings.bookingTaxes') }}
                </button>
            </li>
            <li class="me-2">
                <button type="button" wire:click="setTab('extras')"
                    @class([
                        'inline-block p-4 border-b-2 rounded-t-lg transition',
                        'border-skin-base text-skin-base' => $activeTab === 'extras',
                        'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300' => $activeTab !== 'extras',
                    ])>
                    {{ __('hotel::modules.settings.extraServices') }}
                </button>
            </li>
        </ul>
    </div>

    @if($activeTab === 'taxes')
        @livewire('hotel::settings.hotel-tax-settings')
    @else
        @livewire('hotel::settings.hotel-extra-service-settings')
    @endif
</div>
