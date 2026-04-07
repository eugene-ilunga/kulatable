<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="name" value="{{ __('hotel::modules.roomType.name') }}" required />
                <x-input id="name" class="block mt-1 w-full" type="text" autofocus wire:model='name' />
                <x-input-error for="name" class="mt-2" />
            </div>

            <div>
                <x-label for="imageTemp" value="{{ __('hotel::modules.roomType.image') }}" />
                <input
                    class="block w-full text-sm border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 text-slate-500 mt-1"
                    type="file" wire:model="imageTemp" accept="image/*">
                <x-input-error for="imageTemp" class="mt-2" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('hotel::modules.roomType.imageUploadHelp') }}</p>
                @if($imageTemp)
                <div class="mt-2 flex items-center gap-2">
                    <img src="{{ $imageTemp->temporaryUrl() }}" alt="Preview" class="w-24 h-24 object-cover rounded-lg border border-gray-300 dark:border-gray-600">
                    <button type="button" wire:click="removeSelectedImage" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                @endif
            </div>

            <div>
                <x-label for="description" value="{{ __('hotel::modules.roomType.description') }}" />
                <textarea id="description" wire:model="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base focus:ring-opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600"></textarea>
                <x-input-error for="description" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="base_occupancy" value="{{ __('hotel::modules.roomType.baseOccupancy') }}" required />
                    <x-input id="base_occupancy" class="block mt-1 w-full" type="number" wire:model='base_occupancy' min="1" />
                    <x-input-error for="base_occupancy" class="mt-2" />
                </div>

                <div>
                    <x-label for="max_occupancy" value="{{ __('hotel::modules.roomType.maxOccupancy') }}" required />
                    <x-input id="max_occupancy" class="block mt-1 w-full" type="number" wire:model='max_occupancy' min="1" />
                    <x-input-error for="max_occupancy" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label for="base_rate" value="{{ __('hotel::modules.roomType.baseRate') }}" required />
                <x-input id="base_rate" class="block mt-1 w-full" type="number" step="0.01" min="0" wire:model='base_rate' />
                <x-input-error for="base_rate" class="mt-2" />
            </div>

            <div>
                <x-label for="amenityInput" value="{{ __('hotel::modules.roomType.amenities') }}" />
                <div class="flex gap-2 mt-2">
                    <x-input id="amenityInput" class="block w-full" type="text" wire:model='amenityInput' wire:keydown.enter.prevent="addAmenity" placeholder="{{ __('hotel::modules.roomType.addAmenityPlaceholder') }}" />
                    <x-button type="button" wire:click="addAmenity">{{ __('hotel::modules.roomType.addAmenity') }}</x-button>
                </div>
                @if(count($amenities) > 0)
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($amenities as $index => $amenity)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $amenity }}
                        <button type="button" wire:click="removeAmenity({{ $index }})" class="ml-2 text-blue-600 hover:text-blue-800">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                        </button>
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="sort_order" value="{{ __('hotel::modules.roomType.sortOrder') }}" />
                    <x-input id="sort_order" class="block mt-1 w-full" type="number" min="0" wire:model='sort_order' />
                    <x-input-error for="sort_order" class="mt-2" />
                </div>

                <div class="flex items-center mt-6">
                    <input type="checkbox" id="is_active" wire:model="is_active" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <x-label for="is_active" value="{{ __('hotel::modules.roomType.active') }}" class="ml-2" />
                </div>
            </div>
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button wire:loading.attr="disabled">@lang('app.save')</x-button>
            <x-button-cancel wire:click="$dispatch('hideAddRoomType')" wire:loading.attr="disabled">@lang('app.cancel')</x-button-cancel>
        </div>
    </form>
</div>
