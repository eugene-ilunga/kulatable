<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="name" value="{{ __('hotel::modules.ratePlan.name') }}" />
                <x-input id="name" class="block mt-1 w-full" type="text" wire:model="name" required />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div>
                <x-label for="description" value="{{ __('hotel::modules.ratePlan.description') }}" />
                <textarea id="description" wire:model="description" rows="3" class="block w-full px-4 py-3 transition-all duration-200 border-2 border-gray-300 shadow-sm resize-none rounded-xl dark:border-gray-600 focus:ring-2 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>

            <div>
                <x-label for="type" value="{{ __('hotel::modules.ratePlan.planType') }}" />
                <x-select id="type" class="block mt-1 w-full" wire:model="type" required>
                    @foreach($types as $typeOption)
                        <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                    @endforeach
                </x-select>
                <x-input-error for="type" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="cancellation_hours" value="{{ __('hotel::modules.ratePlan.freeCancellationHours') }}" />
                    <x-input id="cancellation_hours" class="block mt-1 w-full" type="number" wire:model="cancellation_hours" min="0" placeholder="e.g., 24" />
                    <x-input-error for="cancellation_hours" class="mt-2" />
                </div>

                <div>
                    <x-label for="cancellation_charge_percent" value="{{ __('hotel::modules.ratePlan.cancellationChargePercent') }}" />
                    <x-input id="cancellation_charge_percent" class="block mt-1 w-full" type="number" wire:model="cancellation_charge_percent" step="0.01" min="0" max="100" required />
                    <x-input-error for="cancellation_charge_percent" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="is_active" wire:model="is_active" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <x-label for="is_active" class="ml-2">{{ __('hotel::modules.ratePlan.active') }}</x-label>
            </div>
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button type="submit" wire:loading.attr="disabled">{{ __('hotel::modules.ratePlan.update') }}</x-button>
            <x-button-cancel type="button" wire:click="$dispatch('hideEditRatePlan')" wire:loading.attr="disabled">{{ __('hotel::modules.ratePlan.cancel') }}</x-button-cancel>
        </div>
    </form>
</div>
