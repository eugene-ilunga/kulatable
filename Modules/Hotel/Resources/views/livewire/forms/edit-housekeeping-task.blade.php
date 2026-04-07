<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="room_id" value="{{ __('hotel::modules.housekeeping.roomLabel') }}" required />
                <x-select id="room_id" class="block mt-1 w-full" wire:model="room_id" required>
                    <option value="">{{ __('hotel::modules.housekeeping.selectRoom') }}</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}">{{ $room->room_number }} ({{ $room->roomType->name }})</option>
                    @endforeach
                </x-select>
                <x-input-error for="room_id" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="task_date" value="{{ __('hotel::modules.housekeeping.taskDate') }}" required />
                    <x-input id="task_date" class="block mt-1 w-full" type="date" wire:model="task_date" required />
                    <x-input-error for="task_date" class="mt-2" />
                </div>

                <div>
                    <x-label for="type" value="{{ __('hotel::modules.housekeeping.taskType') }}" required />
                    <x-select id="type" class="block mt-1 w-full" wire:model="type" required>
                        @foreach($types as $typeOption)
                            <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error for="type" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label for="status" value="{{ __('hotel::modules.housekeeping.status') }}" required />
                <x-select id="status" class="block mt-1 w-full" wire:model="status" required>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </x-select>
                <x-input-error for="status" class="mt-2" />
            </div>

            <div>
                <x-label for="assigned_to" value="{{ __('hotel::modules.housekeeping.assignTo') }}" />
                <x-select id="assigned_to" class="block mt-1 w-full" wire:model="assigned_to">
                    <option value="">{{ __('hotel::modules.housekeeping.unassigned') }}</option>
                    @foreach($staff as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </x-select>
            </div>

            <div>
                <x-label for="notes" value="{{ __('hotel::modules.housekeeping.notes') }}" />
                <textarea id="notes" wire:model="notes" rows="3" class="block w-full px-4 py-3 transition-all duration-200 border-2 border-gray-300 shadow-sm resize-none rounded-xl dark:border-gray-600 focus:ring-2 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button type="submit" wire:loading.attr="disabled">{{ __('hotel::modules.housekeeping.update') }}</x-button>
            <x-button-cancel type="button" wire:click="$dispatch('hideEditHousekeepingTask')" wire:loading.attr="disabled">{{ __('hotel::modules.housekeeping.cancel') }}</x-button-cancel>
        </div>
    </form>
</div>

