<div>

    {{-- ══════════════════════════════════════════
         PAGE HEADER & TOOLBAR
    ══════════════════════════════════════════ --}}
    <div class="px-5 pt-5 pb-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl tracking-tight">
                    {{ __('hotel::modules.roomType.roomTypes') }}
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 ring-1 ring-indigo-200 dark:ring-indigo-700">
                    {{ $roomTypes->count() }}
                </span>
            </div>

            @if(user_can('Create Hotel Room Type'))
            <button type="button" wire:click="$toggle('showAddRoomTypeModal')"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-lg bg-skin-base text-white hover:opacity-90 transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('hotel::modules.roomType.addRoomType') }}
            </button>
            @endif
        </div>

        {{-- Filter toolbar --}}
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1 min-w-0 sm:max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 111 11a6 6 0 0116 0z" />
                    </svg>
                </div>
                <x-input type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('hotel::modules.roomType.searchPlaceholder') }}"
                    class="block w-full pl-9" />
            </div>

            <x-select wire:model.live="filterStatus" class="block w-full sm:w-40">
                <option value="">{{ __('hotel::modules.roomType.allStatuses') }}</option>
                <option value="1">{{ __('app.active') }}</option>
                <option value="0">{{ __('app.inactive') }}</option>
            </x-select>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         CARD GRID
    ══════════════════════════════════════════ --}}
    <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @forelse ($roomTypes as $roomType)
            @php
                $isActive = (bool) $roomType->is_active;
            @endphp
            <div class="group flex flex-col rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">

                {{-- Image --}}
                <div class="relative shrink-0 w-full h-48 bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <img
                        src="{{ $roomType->image_url ?: asset('img/room.png') }}"
                        alt="{{ $roomType->name }}"
                        class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300"
                        onerror="this.src='{{ asset('img/room.png') }}'; this.onerror=null;"
                    >
                    {{-- Gradient scrim --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>

                    {{-- Status badge --}}
                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold ring-1 {{ $isActive ? 'bg-emerald-50/90 text-emerald-700 ring-emerald-300 dark:bg-emerald-900/70 dark:text-emerald-300 dark:ring-emerald-700' : 'bg-gray-100/90 text-gray-600 ring-gray-300 dark:bg-gray-800/80 dark:text-gray-400 dark:ring-gray-600' }} backdrop-blur-sm">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-gray-400' }} shrink-0"></span>
                            {{ $isActive ? __('app.active') : __('app.inactive') }}
                        </span>
                    </div>

                    {{-- Rate chip on bottom-left --}}
                    <div class="absolute bottom-3 left-3">
                        <span class="inline-flex items-baseline gap-1 px-2.5 py-1 rounded-lg bg-black/50 backdrop-blur-sm text-white text-sm font-bold">
                            {{ currency_format($roomType->base_rate) }}
                            <span class="text-[10px] font-normal opacity-80">/ {{ __('hotel::modules.reservation.night') }}</span>
                        </span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-4 flex flex-col flex-1">

                    {{-- Name --}}
                    <h3 class="text-base font-bold text-gray-900 dark:text-white leading-snug mb-1.5">
                        {{ $roomType->name }}
                    </h3>

                    {{-- Occupancy + Rooms meta --}}
                    <div class="flex items-center gap-3 mb-3">
                        <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            {{ __('hotel::modules.roomType.maxOccupancy') }}: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $roomType->max_occupancy }}</span>
                        </span>
                        <span class="w-px h-3 bg-gray-200 dark:bg-gray-600"></span>
                        <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15v-6H9v6H3.75A.75.75 0 013 21V9.75z" />
                            </svg>
                            {{ __('hotel::modules.roomType.rooms') }}: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $roomType->rooms_count }}</span>
                        </span>
                    </div>

                    {{-- Amenities --}}
                    @if(!empty($roomType->amenities))
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        @foreach(array_slice($roomType->amenities ?? [], 0, 5) as $amenity)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-violet-50 text-violet-600 ring-1 ring-violet-200 dark:bg-violet-900/20 dark:text-violet-300 dark:ring-violet-800">
                            {{ $amenity }}
                        </span>
                        @endforeach
                        @if(count($roomType->amenities ?? []) > 5)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-500 ring-1 ring-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:ring-gray-600">
                            +{{ count($roomType->amenities) - 5 }}
                        </span>
                        @endif
                    </div>
                    @endif

                    {{-- Description --}}
                    @if($roomType->description)
                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 flex-1 mb-3">
                        {{ $roomType->description }}
                    </p>
                    @else
                    <div class="flex-1"></div>
                    @endif

                    {{-- Action footer --}}
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2">
                        @if(user_can('Update Hotel Room Type'))
                        <button type="button" wire:click="showEditRoomType({{ $roomType->id }})"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ __('hotel::modules.roomType.update') }}
                        </button>
                        @endif

                        @if(user_can('Delete Hotel Room Type'))
                        <button type="button" wire:click="showDeleteRoomType({{ $roomType->id }})"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:text-rose-400 dark:hover:bg-rose-900/30 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            @empty
            <div class="col-span-full py-20 flex flex-col items-center justify-center gap-3">
                <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15v-6H9v6H3.75A.75.75 0 013 21V9.75z" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('hotel::modules.roomType.noRoomTypesAdded') }}</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════ --}}

    {{-- Add --}}
    <x-right-modal wire:model.live="showAddRoomTypeModal">
        <x-slot name="title">{{ __('hotel::modules.roomType.addRoomType') }}</x-slot>
        <x-slot name="content">
            @if ($showAddRoomTypeModal)
                <livewire:hotel::forms.add-room-type />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddRoomTypeModal', false)" wire:loading.attr="disabled">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    {{-- Edit --}}
    @if ($activeRoomType)
    <x-right-modal wire:model.live="showEditRoomTypeModal">
        <x-slot name="title">{{ __('hotel::modules.roomType.editRoomType') }}</x-slot>
        <x-slot name="content">
            <livewire:hotel::forms.edit-room-type :activeRoomType="$activeRoomType" :key="str()->random(50)" />
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditRoomTypeModal', false)" wire:loading.attr="disabled">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    {{-- Delete confirmation --}}
    <x-confirmation-modal wire:model="confirmDeleteRoomTypeModal">
        <x-slot name="title">{{ __('hotel::modules.roomType.deleteRoomType') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.roomType.deleteRoomTypeMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteRoomTypeModal')" wire:loading.attr="disabled">{{ __('app.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click="deleteRoomType({{ $activeRoomType->id }})" wire:loading.attr="disabled">{{ __('app.delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif

</div>
