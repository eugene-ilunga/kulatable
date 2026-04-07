<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.room.rooms') }}</h1>
            </div>

            <div class="items-center justify-between block sm:flex">
                <div class="flex items-center mb-4 sm:mb-0">
                    <form class="ltr:pr-3 rtl:pl-3" action="#" method="GET">
                        <label for="rooms-search" class="sr-only">{{ __('hotel::modules.room.searchPlaceholder') }}</label>
                        <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                            <x-input id="rooms-search" class="block mt-1 w-full" type="text" placeholder="{{ __('hotel::modules.room.searchPlaceholder') }}" wire:model.live.debounce.500ms="search" />
                        </div>
                    </form>

                    <x-secondary-button wire:click="$toggle('showFilters')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter mr-1" viewBox="0 0 16 16">
                            <path d="M6 10.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m-2-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/>
                        </svg> @lang('app.showFilter')
                    </x-secondary-button>
                </div>

                @if(user_can('Create Hotel Room'))
                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-button type='button' wire:click="$toggle('showAddRoomModal')">{{ __('hotel::modules.room.addRoom') }}</x-button>
                </div>
                @endif
            </div>

            @if($showFilters)
            <div class="w-full p-4 flex gap-4 mt-2 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <div>
                    <x-dropdown align="left">
                        <x-slot name="trigger">
                            <span class="inline-flex rounded-md">
                                <button type="button"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    {{ __('hotel::modules.room.filterRoomType') }}
                                    <div class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium text-white bg-red-500 rounded-md dark:border-gray-900 ml-1 {{ !$filterRoomType ? 'hidden' : '' }}">1</div>
                                    <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                    </svg>
                                </button>
                            </span>
                        </x-slot>

                        <x-slot name="content">
                            <div class="block px-4 py-2 text-sm font-medium text-gray-500">
                                <h6 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('hotel::modules.room.roomType') }}
                                </h6>
                            </div>

                            <x-dropdown-link wire:click.prevent="$set('filterRoomType', '')" wire:key='room_type-all'>
                                <input id="room_type_all" type="radio" name="room_type_filter" value="" @if(!$filterRoomType) checked @endif
                                class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500" />
                                <label for="room_type_all" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('hotel::modules.room.allRoomTypes') }}
                                </label>
                            </x-dropdown-link>

                            @foreach ($roomTypes as $type)
                                <x-dropdown-link wire:click.prevent="$set('filterRoomType', '{{ $type->id }}')" wire:key='room_type-{{ $type->id }}'>
                                    <input id="room_type_{{ $type->id }}" type="radio" name="room_type_filter" value="{{ $type->id }}" @if($filterRoomType == $type->id) checked @endif
                                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500" />
                                    <label for="room_type_{{ $type->id }}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $type->name }}
                                    </label>
                                </x-dropdown-link>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                </div>

                <div>
                    <x-dropdown align="left">
                        <x-slot name="trigger">
                            <span class="inline-flex rounded-md">
                                <button type="button"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    {{ __('hotel::modules.room.filterStatus') }}
                                    <div class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium text-white bg-red-500 rounded-md dark:border-gray-900 ml-1 {{ !$filterStatus ? 'hidden' : '' }}">1</div>
                                    <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path clip-rule="evenodd" fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                    </svg>
                                </button>
                            </span>
                        </x-slot>

                        <x-slot name="content">
                            <div class="block px-4 py-2 text-sm font-medium text-gray-500">
                                <h6 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('hotel::modules.room.status') }}
                                </h6>
                            </div>

                            <x-dropdown-link wire:click.prevent="$set('filterStatus', '')" wire:key='status-all'>
                                <input id="status_all" type="radio" name="status_filter" value="" @if(!$filterStatus) checked @endif
                                class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500" />
                                <label for="status_all" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ __('hotel::modules.room.allStatuses') }}
                                </label>
                            </x-dropdown-link>

                            @foreach ($statuses as $status)
                                <x-dropdown-link wire:click.prevent="$set('filterStatus', '{{ $status->value }}')" wire:key='status-{{ $status->value }}'>
                                    <input id="status_{{ $status->value }}" type="radio" name="status_filter" value="{{ $status->value }}" @if($filterStatus == $status->value) checked @endif
                                    class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-gray-600 focus:ring-gray-500 dark:focus:ring-gray-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500" />
                                    <label for="status_{{ $status->value }}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $status->label() }}
                                    </label>
                                </x-dropdown-link>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                </div>

                @if($filterRoomType || $filterStatus)
                    <div wire:key='filter-btn-{{ microtime() }}'>
                        <x-danger-button wire:click='clearFilters'>
                            <svg aria-hidden="true" class="w-5 h-5 -ml-1 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            @lang('app.clearFilter')
                        </x-danger-button>
                    </div>
                @endif

                <div>
                    <x-secondary-button wire:click="$toggle('showFilters')">@lang('app.hideFilter')</x-secondary-button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.room.roomNumber') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.room.roomType') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.room.floor') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.room.status') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">{{ __('hotel::modules.room.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($rooms as $room)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white font-semibold">
                                    {{ $room->room_number }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $room->roomType->name }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $room->floor ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-base whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'vacant_clean' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'vacant_dirty' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'occupied' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'out_of_service' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                            'out_of_order' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'maintenance' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                        ];
                                        $color = $statusColors[$room->status->value] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $color }}">
                                        {{ $room->status->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if(user_can('Update Hotel Room'))
                                    <x-secondary-button-table wire:click='showEditRoom({{ $room->id }})'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg>
                                        {{ __('hotel::modules.room.update') }}
                                    </x-secondary-button-table>
                                    @endif

                                    @if(user_can('Delete Hotel Room'))
                                    <x-danger-button-table wire:click="showDeleteRoom({{ $room->id }})">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    </x-danger-button-table>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="py-8 px-4 text-center text-gray-900 dark:text-gray-400" colspan="5">
                                    <p class="text-base font-medium">{{ __('hotel::modules.room.noRoomsFound') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4">
        {{ $rooms->links() }}
    </div>

    <x-right-modal wire:model.live="showAddRoomModal">
        <x-slot name="title">{{ __('hotel::modules.room.addRoom') }}</x-slot>
        <x-slot name="content">
            @if ($showAddRoomModal)
                <livewire:hotel::forms.add-room />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddRoomModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    @if ($activeRoom)
    <x-right-modal wire:model.live="showEditRoomModal">
        <x-slot name="title">{{ __('hotel::modules.room.editRoom') }}</x-slot>
        <x-slot name="content">
            <livewire:hotel::forms.edit-room :activeRoom="$activeRoom" :key="str()->random(50)" />
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditRoomModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteRoomModal">
        <x-slot name="title">{{ __('hotel::modules.room.deleteRoom') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.room.deleteRoomMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteRoomModal')">{{ __('app.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click='deleteRoom({{ $activeRoom->id }})'>{{ __('app.delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif
</div>
