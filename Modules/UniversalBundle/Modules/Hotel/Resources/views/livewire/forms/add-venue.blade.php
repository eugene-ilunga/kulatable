<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="name" value="{{ __('hotel::modules.banquet.name') }}" required />
                <x-input id="name" class="block mt-1 w-full" type="text" wire:model="name" required />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div>
                <x-label for="description" value="{{ __('hotel::modules.banquet.description') }}" />
                <textarea id="description" wire:model="description" rows="3" class="block w-full px-4 py-3 transition-all duration-200 border-2 border-gray-300 shadow-sm resize-none rounded-xl dark:border-gray-600 focus:ring-2 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="capacity" value="{{ __('hotel::modules.banquet.capacity') }}" required />
                    <x-input id="capacity" class="block mt-1 w-full" type="number" wire:model="capacity" min="0" required />
                    <x-input-error for="capacity" class="mt-2" />
                </div>

                <div>
                    <x-label for="base_rate" value="{{ __('hotel::modules.banquet.baseRate') }}" required />
                    <x-input id="base_rate" class="block mt-1 w-full" type="number" wire:model="base_rate" step="0.01" min="0" required />
                    <x-input-error for="base_rate" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label for="amenityInput" value="{{ __('hotel::modules.banquet.amenities') }}" />
                <div class="flex gap-2 mt-2">
                    <x-input id="amenityInput" class="block w-full" type="text" wire:model="amenityInput" wire:keydown.enter.prevent="addAmenity" placeholder="{{ __('hotel::modules.banquet.addAmenityPlaceholder') }}" />
                    <x-button type="button" wire:click.prevent="addAmenity" wire:loading.attr="disabled">{{ __('hotel::modules.banquet.addAmenity') }}</x-button>
                </div>
                @if(count($amenities) > 0)
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($amenities as $index => $amenity)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $amenity }}
                        <button type="button" wire:click.prevent="removeAmenity({{ $index }})" class="ml-2 text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        </button>
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="is_active" wire:model="is_active" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <x-label for="is_active" class="ml-2">{{ __('hotel::modules.banquet.active') }}</x-label>
            </div>
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button type="submit" wire:loading.attr="disabled">{{ __('hotel::modules.banquet.save') }}</x-button>
            <x-button-cancel type="button" wire:click="$dispatch('hideAddVenue')" wire:loading.attr="disabled">{{ __('hotel::modules.banquet.cancel') }}</x-button-cancel>
        </div>
    </form>
</div>
