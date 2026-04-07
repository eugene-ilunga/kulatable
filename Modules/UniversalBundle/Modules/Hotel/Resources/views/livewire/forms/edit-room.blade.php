<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="room_number" value="{{ __('hotel::modules.room.roomNumber') }}" required />
                <x-input id="room_number" class="block mt-1 w-full" type="text" autofocus wire:model='room_number' />
                <x-input-error for="room_number" class="mt-2" />
            </div>

            <div>
                <x-label for="room_type_id" value="{{ __('hotel::modules.room.roomType') }}" required />
                <x-select class="mt-1 block w-full" wire:model='room_type_id'>
                    <option value="">{{ __('hotel::modules.room.selectRoomType') }}</option>
                    @foreach($roomTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </x-select>
                <x-input-error for="room_type_id" class="mt-2" />
            </div>

            <div>
                <x-label for="floor" value="{{ __('hotel::modules.room.floor') }}" />
                <x-input id="floor" class="block mt-1 w-full" type="text" wire:model='floor' />
                <x-input-error for="floor" class="mt-2" />
            </div>

            <div>
                <x-label for="status" value="{{ __('hotel::modules.room.status') }}" required />
                <x-select class="mt-1 block w-full" wire:model='status'>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </x-select>
                <x-input-error for="status" class="mt-2" />
            </div>

            <div>
                <x-label for="notes" value="{{ __('hotel::modules.room.notes') }}" />
                <textarea id="notes" wire:model="notes" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-skin-base focus:ring focus:ring-skin-base focus:ring-opacity-50 dark:bg-gray-800 dark:text-white dark:border-gray-600"></textarea>
                <x-input-error for="notes" class="mt-2" />
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="is_active" wire:model="is_active" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <x-label for="is_active" value="{{ __('hotel::modules.room.active') }}" class="ml-2" />
            </div>
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button wire:loading.attr="disabled">@lang('app.update')</x-button>
            <x-button-cancel wire:click="$dispatch('hideEditRoom')" wire:loading.attr="disabled">@lang('app.cancel')</x-button-cancel>
        </div>
    </form>
</div>
