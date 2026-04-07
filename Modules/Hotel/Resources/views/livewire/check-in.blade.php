<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.checkIn.checkIn') }}</h1>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mb-4">
                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <x-input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('hotel::modules.checkIn.searchPlaceholder') }}" class="block w-full sm:w-96" />
                    <x-input type="date" wire:model.live="filterDate" class="block w-full sm:w-40" />
                </div>
                {{-- <a href="{{ route('hotel.rooms.status-board', ['filterStatus' => 'vacant_clean']) }}" class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-skin-base text-white text-sm font-semibold rounded-lg shadow hover:opacity-90 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                    </svg>
                    {{ __('hotel::modules.checkIn.checkInButton') }}
                </a> --}}
            </div>
        </div>
    </div>

    <div class="p-4">
        @if($reservations->count())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                @foreach ($reservations as $reservation)
                    <div class="rounded-2xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700 flex flex-col overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">

                        <div class="p-5 flex flex-col flex-1">
                            {{-- Reservation number + rooms badge --}}
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <p class="text-base font-bold text-gray-900 dark:text-white leading-tight tracking-tight">
                                    {{ $reservation->reservation_number }}
                                </p>
                                <span class="shrink-0 inline-flex items-center rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800">
                                    {{ $reservation->rooms_count }} {{ __('hotel::modules.checkIn.rooms') }}
                                </span>
                            </div>

                            {{-- Guest name --}}
                            <div class="flex items-center gap-2 mb-5">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                    {{ $reservation->primaryGuest->full_name }}
                                </span>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-gray-100 dark:border-gray-700/60 mb-3"></div>

                            {{-- Date/time + button on same row --}}
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 min-w-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                                    </svg>
                                    <span class="font-medium truncate">{{ $reservation->check_in_date->format('M d, Y') }}</span>
                                    @if($reservation->check_in_time)
                                        <span class="text-gray-300 dark:text-gray-600 shrink-0">·</span>
                                        <span class="shrink-0">{{ Carbon\Carbon::parse($reservation->check_in_time)->format('g:i A') }}</span>
                                    @endif
                                </div>
                                <button wire:click='showCheckInForm({{ $reservation->id }})'
                                    class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 bg-skin-base text-white text-sm font-semibold rounded-lg shadow hover:opacity-90 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14" />
                                    </svg>
                                    {{ __('hotel::modules.checkIn.checkInButton') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-20 flex flex-col items-center justify-center gap-3">
                <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </span>
                <p class="text-base font-medium text-gray-500 dark:text-gray-400">
                    {{ __('hotel::modules.checkIn.noReservationsForCheckIn') }}
                </p>
            </div>
        @endif
    </div>

    <div class="p-4">
        {{ $reservations->links() }}
    </div>

    @if ($selectedReservation)
    <x-right-modal wire:model.live="showCheckInModal" maxWidth="3xl">
        <x-slot name="title">{{ __('hotel::modules.checkIn.checkInModalTitle', ['number' => $selectedReservation->reservation_number]) }}</x-slot>

        <x-slot name="content">
            <div class="space-y-5">

                {{-- ══ Reservation banner ══ --}}
                <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 dark:from-blue-900/20 dark:to-indigo-900/20 dark:border-blue-700/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-800/60 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-bold text-blue-900 dark:text-blue-100 leading-tight">
                                {{ $selectedReservation->reservation_number }}
                                <span class="font-normal text-blue-700 dark:text-blue-300">— {{ $selectedReservation->primaryGuest->full_name }}</span>
                            </p>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                                {{ $selectedReservation->check_in_date->format('M d, Y') }}
                                @if($selectedReservation->check_in_time)
                                    · {{ \Carbon\Carbon::parse($selectedReservation->check_in_time)->format('g:i A') }}
                                @endif
                                <span class="mx-1.5 opacity-40">→</span>
                                {{ $selectedReservation->check_out_date->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-700">
                        {{ $selectedReservation->rooms_count }} {{ __('hotel::modules.checkIn.rooms') }}
                    </span>
                </div>

                {{-- ══ SECTION 1: Room Assignment ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15v-6H9v6H3.75A.75.75 0 013 21V9.75z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkIn.assignRooms') }}</span>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach($selectedReservation->reservationRooms as $reservationRoom)
                        <div>
                            {{-- Room-type sub-header --}}
                            <div class="flex items-center gap-2.5 px-4 py-2 bg-gray-50/60 dark:bg-gray-700/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 flex-1">{{ $reservationRoom->roomType->name }}</span>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 ring-1 ring-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:ring-gray-600">
                                    {{ __('hotel::modules.checkIn.quantity') }} {{ $reservationRoom->quantity }}
                                </span>
                            </div>

                            {{-- Dropdowns or no-rooms warning --}}
                            <div class="px-4 py-3 bg-white dark:bg-gray-800 space-y-2">
                                @if(empty($availableRooms[$reservationRoom->room_type_id]) || $availableRooms[$reservationRoom->room_type_id]->isEmpty())
                                    <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        <p class="text-xs font-medium text-amber-700 dark:text-amber-400">
                                            {{ __('hotel::modules.checkIn.noVacantRoomsForType', ['type' => $reservationRoom->roomType->name]) }}
                                        </p>
                                    </div>
                                @else
                                    @for($i = 0; $i < $reservationRoom->quantity; $i++)
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[10px] font-bold shrink-0">
                                            {{ $i + 1 }}
                                        </span>
                                        <x-select wire:model="roomAssignments.{{ $reservationRoom->id }}.{{ $i }}" class="block w-full">
                                            <option value="">— {{ __('hotel::modules.checkIn.selectRoom') }} —</option>
                                            @foreach($availableRooms[$reservationRoom->room_type_id] as $room)
                                                <option value="{{ $room->id }}">
                                                    {{ __('hotel::modules.roomStatusBoard.room') }} {{ $room->room_number }}{{ !empty($room->floor) ? ' (' . $room->floor . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                    </div>
                                    @endfor
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ══ SECTION 2: Guest Details ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkIn.guestDetails') }}</span>
                        </div>
                        <button type="button" wire:click="addGuest"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-sky-50 text-sky-600 border border-sky-200 hover:bg-sky-100 dark:bg-sky-900/30 dark:text-sky-300 dark:border-sky-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ __('hotel::modules.checkIn.addGuest') }}
                        </button>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700/60">

                        {{-- Primary guest — collapsible read-only --}}
                        @php $pg = $selectedReservation->primaryGuest; @endphp
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition text-left">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold shrink-0">1</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 leading-tight">
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $pg->full_name }}</span>
                                        <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-900/40 dark:text-green-400 dark:ring-green-700">
                                            {{ __('hotel::modules.checkIn.primaryBadge') }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate" x-show="!open">
                                        {{ $pg->phone ?: '—' }}
                                        @if($pg->id_type) &nbsp;·&nbsp; {{ ucwords(str_replace('_', ' ', $pg->id_type)) }} @endif
                                    </p>
                                </div>
                            </button>
                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                                @php
                                    $pgFields = [
                                        __('hotel::modules.guest.firstName') => $pg->first_name ?: '—',
                                        __('hotel::modules.guest.lastName')  => $pg->last_name  ?: '—',
                                        __('hotel::modules.guest.phone')     => $pg->phone      ?: '—',
                                        __('hotel::modules.guest.email')     => $pg->email      ?: '—',
                                        __('hotel::modules.guest.idType')    => $pg->id_type    ? ucwords(str_replace('_', ' ', $pg->id_type)) : '—',
                                        __('hotel::modules.guest.idNumber')  => $pg->id_number  ?: '—',
                                    ];
                                @endphp
                                <div class="grid grid-cols-2 divide-x divide-y divide-gray-100 dark:divide-gray-700/60">
                                    @foreach($pgFields as $label => $value)
                                    <div class="px-4 py-3">
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">{{ $label }}</p>
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $value }}</p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Additional guests --}}
                        @foreach($additionalGuests as $gIndex => $guest)
                        <div x-data="{ open: true }">
                            <div class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800">
                                <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 min-w-0 text-left">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-sky-500 text-white text-xs font-bold shrink-0">{{ $gIndex + 2 }}</span>
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                                        {{ trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? '')) ?: __('hotel::modules.checkIn.guestNumber', ['number' => $gIndex + 2]) }}
                                    </span>
                                </button>
                                <button type="button" wire:click="removeGuest({{ $gIndex }})"
                                    class="shrink-0 p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="px-4 pb-4 pt-1 bg-white dark:bg-gray-800 grid grid-cols-2 gap-x-4 gap-y-3">
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.firstName') }} <span class="text-red-500">*</span></x-label>
                                    <x-input wire:model="additionalGuests.{{ $gIndex }}.first_name" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.firstName') }}" />
                                    @error("additionalGuests.{$gIndex}.first_name")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.lastName') }}</x-label>
                                    <x-input wire:model="additionalGuests.{{ $gIndex }}.last_name" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.lastName') }}" />
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.phone') }} <span class="text-red-500">*</span></x-label>
                                    <x-input wire:model="additionalGuests.{{ $gIndex }}.phone" type="text" class="block w-full" placeholder="+91 00000 00000" />
                                    @error("additionalGuests.{$gIndex}.phone")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.email') }}</x-label>
                                    <x-input wire:model="additionalGuests.{{ $gIndex }}.email" type="email" class="block w-full" placeholder="email@example.com" />
                                    @error("additionalGuests.{$gIndex}.email")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.idType') }} <span class="text-red-500">*</span></x-label>
                                    <x-select wire:model="additionalGuests.{{ $gIndex }}.id_type" class="block w-full">
                                        <option value="">{{ __('hotel::modules.guest.selectIdType') }}</option>
                                        <option value="passport">{{ __('hotel::modules.guest.passport') }}</option>
                                        <option value="aadhaar">{{ __('hotel::modules.guest.aadhaar') }}</option>
                                        <option value="driving_license">{{ __('hotel::modules.guest.drivingLicense') }}</option>
                                        <option value="national_id">{{ __('hotel::modules.guest.nationalId') }}</option>
                                        <option value="other">{{ __('hotel::modules.guest.other') }}</option>
                                    </x-select>
                                    @error("additionalGuests.{$gIndex}.id_type")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.idNumber') }} <span class="text-red-500">*</span></x-label>
                                    <x-input wire:model="additionalGuests.{{ $gIndex }}.id_number" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.idNumber') }}" />
                                    @error("additionalGuests.{$gIndex}.id_number")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-span-2">
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.idProof') }}</x-label>
                                    <input type="file" wire:model="additionalGuests.{{ $gIndex }}.id_proof_file"
                                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-skin-base file:text-white hover:file:opacity-90">
                                    @if(isset($additionalGuests[$gIndex]['id_proof_file']) && is_object($additionalGuests[$gIndex]['id_proof_file']))
                                    <p class="mt-1 text-xs text-gray-500">{{ $additionalGuests[$gIndex]['id_proof_file']->getClientOriginalName() }}</p>
                                    @endif
                                    @error("additionalGuests.{$gIndex}.id_proof_file")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        </div>
                        @endforeach

                    </div>
                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-secondary-button wire:click="$set('showCheckInModal', false)">{{ __('hotel::modules.checkIn.cancel') }}</x-secondary-button>
                <x-button wire:click="processCheckIn({{ $selectedReservation->id }})" wire:loading.attr="disabled">{{ __('hotel::modules.checkIn.confirmCheckIn') }}</x-button>
            </div>
        </x-slot>
    </x-right-modal>
    @endif
</div>
