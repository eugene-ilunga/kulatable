<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.roomStatusBoard.roomStatusBoard') }}</h1>
            </div>

            <div class="flex flex-col gap-3 mb-4">
                <div class="flex flex-col sm:flex-row gap-2">
                    <select wire:model.live="selectedFloor" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-48 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">{{ __('hotel::modules.roomStatusBoard.allFloors') }}</option>
                        @foreach($floors as $floor)
                            <option value="{{ $floor }}">{{ __('hotel::modules.roomStatusBoard.floor', ['floor' => $floor]) }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterStatus" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-48 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">{{ __('hotel::modules.room.allStatuses') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Room type tabs (styled like settings tabs) --}}
                <ul class="flex flex-wrap -mb-px border-b border-gray-200 dark:border-gray-700">
                    <li class="me-2">
                        <button
                            type="button"
                            wire:click="$set('filterRoomType', '')"
                            @class([
                                "inline-block px-4 py-2 border-b-2 rounded-t-lg border-transparent text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-600. text-xs w-full",
                                "border-skin-base text-skin-base dark:text-skin-base dark:border-skin-base" => ($filterRoomType === ''),
                            ])
                        >
                            {{ __('hotel::modules.roomType.allRooms') ?? 'All Rooms' }}
                        </button>
                    </li>

                    @foreach($roomTypes as $roomType)
                        <li class="me-2">
                            <button
                                type="button"
                                wire:click="$set('filterRoomType', {{ $roomType->id }})"
                                @class([
                                    "inline-block px-4 py-2 border-b-2 rounded-t-lg border-transparent text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:border-gray-300 dark:hover:border-gray-600. text-xs w-full",
                                    "border-skin-base text-skin-base dark:text-skin-base dark:border-skin-base" => ((string)$filterRoomType === (string)$roomType->id),
                                ])
                            >
                                {{ $roomType->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>

            </div>
        </div>
    </div>

    <div class="p-4">
        {{-- Legend --}}
        <div class="mb-5 flex flex-wrap items-center gap-x-5 gap-y-2">
            @foreach([
                ['bg-emerald-500', __('hotel::modules.roomStatusBoard.vacantClean')],
                ['bg-amber-400',   __('hotel::modules.roomStatusBoard.vacantDirty')],
                ['bg-sky-500',     __('hotel::modules.roomStatusBoard.occupied')],
                ['bg-orange-400',  __('hotel::modules.roomStatusBoard.maintenance')],
                ['bg-rose-500',    __('hotel::modules.roomStatusBoard.outOfOrder')],
                ['bg-slate-400',   __('hotel::modules.roomStatusBoard.outOfService')],
            ] as [$dot, $label])
            <div class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                <span class="w-2 h-2 rounded-full {{ $dot }} shrink-0"></span>
                <span>{{ $label }}</span>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($rooms as $room)
            @php
                $cfg = [
                    'vacant_clean'   => ['accent' => 'bg-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:ring-emerald-700', 'dot' => 'bg-emerald-500', 'action' => 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800/50'],
                    'vacant_dirty'   => ['accent' => 'bg-amber-400',   'badge' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-700',   'dot' => 'bg-amber-400',   'action' => ''],
                    'occupied'       => ['accent' => 'bg-sky-500',     'badge' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-900/30 dark:text-sky-400 dark:ring-sky-700',             'dot' => 'bg-sky-500',     'action' => ''],
                    'maintenance'    => ['accent' => 'bg-orange-400',  'badge' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:ring-orange-700', 'dot' => 'bg-orange-400', 'action' => ''],
                    'out_of_order'   => ['accent' => 'bg-rose-500',    'badge' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:ring-rose-700',         'dot' => 'bg-rose-500',    'action' => ''],
                    'out_of_service' => ['accent' => 'bg-slate-400',   'badge' => 'bg-slate-50 text-slate-600 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-400 dark:ring-slate-600',   'dot' => 'bg-slate-400',   'action' => ''],
                ][$room->status->value] ?? ['accent' => 'bg-gray-400', 'badge' => 'bg-gray-100 text-gray-600 ring-gray-200', 'dot' => 'bg-gray-400', 'action' => ''];

                $isVacantClean = $room->status === \Modules\Hotel\Enums\RoomStatus::VACANT_CLEAN;
                $isOccupied    = $room->status === \Modules\Hotel\Enums\RoomStatus::OCCUPIED;

                $primaryGuest = $isOccupied
                    ? ($room->currentStay?->stayGuests->firstWhere('is_primary', true) ?? $room->currentStay?->stayGuests->first())
                    : null;
            @endphp

            <div @class([
                'group relative flex flex-col rounded-xl border bg-white dark:bg-gray-800 dark:border-gray-700 overflow-hidden shadow-sm transition-all duration-200 min-h-[130px]',
                'border-gray-200 hover:shadow-md hover:-translate-y-0.5' => !$isVacantClean,
                'border-emerald-200 dark:border-emerald-800/60 hover:shadow-lg hover:-translate-y-0.5' => $isVacantClean,
            ])>

                {{-- Coloured left accent bar --}}
                <div class="absolute inset-y-0 left-0 w-1 {{ $cfg['accent'] }}"></div>

                {{-- Card body --}}
                <div class="pl-4 pr-3 pt-3 pb-2.5 flex flex-col flex-1 gap-2">

                    {{-- Top row: room number + status dot --}}
                    <div class="flex items-start justify-between gap-1">
                        <span class="text-xl font-bold leading-none text-gray-900 dark:text-white tracking-tight">
                            {{ $room->room_number }}
                        </span>
                        <span class="mt-0.5 shrink-0 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-semibold ring-1 {{ $cfg['badge'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $cfg['dot'] }} shrink-0"></span>
                            {{ $room->status->label() }}
                        </span>
                    </div>

                    {{-- Room type --}}
                    <p class="text-xs text-gray-400 dark:text-gray-500 leading-none truncate">
                        {{ $room->roomType->name }}
                        @if($room->floor)
                            · {{ __('hotel::modules.roomStatusBoard.floor', ['floor' => $room->floor]) }}
                        @endif
                    </p>

                    {{-- Occupied: guest name + checkout --}}
                    @if($isOccupied && $room->currentStay)
                    <div class="mt-auto pt-1 border-t border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">
                                {{ $primaryGuest?->guest?->full_name ?? __('hotel::modules.roomStatusBoard.guest') }}
                            </span>
                        </div>
                    
                    </div>
                    @endif

                    {{-- Vacant Clean: integrated check-in footer --}}
                    {{-- @if($isVacantClean)
                    <div class="mt-auto pt-1.5 border-t border-emerald-100 dark:border-emerald-800/40 flex items-center justify-between {{ $cfg['action'] }} rounded-b -mx-0 transition-colors duration-150">
                        <span class="text-[11px] font-semibold">{{ __('hotel::modules.checkIn.checkInButton') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </div>
                    @endif --}}

                </div>
            </div>
            @endforeach
        </div>

        @if($rooms->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.room.noRoomsFound') }}</p>
        </div>
        @endif
    </div>

    {{-- Walk-in Instant Check-In Modal --}}
    @if($showCheckInModal && $selectedRoom)
    <x-right-modal wire:model.live="showCheckInModal" maxWidth="3xl">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                {{ __('hotel::modules.checkIn.checkIn') }} — {{ __('hotel::modules.roomStatusBoard.room') }} {{ $selectedRoom->room_number }}
                <span class="text-sm font-normal text-gray-400 dark:text-gray-500">({{ __('hotel::modules.checkIn.walkIn') }})</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-5">

                {{-- ══ Room banner ══ --}}
                <div class="flex items-center justify-between px-4 py-3 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 dark:from-emerald-900/20 dark:to-teal-900/20 dark:border-emerald-700/60">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-800/60 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-600 dark:text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15v-6H9v6H3.75A.75.75 0 013 21V9.75z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-bold text-emerald-900 dark:text-emerald-100 leading-tight">
                                {{ __('hotel::modules.roomStatusBoard.room') }} {{ $selectedRoom->room_number }}
                                <span class="font-normal text-emerald-700 dark:text-emerald-300">— {{ $selectedRoom->roomType->name }}</span>
                            </p>
                            @if($selectedRoom->floor)
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5">
                                {{ __('hotel::modules.roomStatusBoard.floor', ['floor' => $selectedRoom->floor]) }}
                                <span class="mx-1.5 opacity-40">·</span>
                                <span class="font-medium">{{ __('hotel::modules.roomStatusBoard.vacantClean') }}</span>
                            </p>
                            @endif
                        </div>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700 ring-1 ring-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:ring-purple-700">
                        Walk-in
                    </span>
                </div>

                {{-- ══ SECTION 1: Stay Details ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Section header --}}
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkIn.stayDetails') ?? 'Stay Details' }}</span>
                    </div>
                    {{-- Fields --}}
                    <div class="p-4 grid grid-cols-2 gap-x-4 gap-y-3 bg-white dark:bg-gray-800">
                        <div>
                            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                {{ __('hotel::modules.checkIn.checkOutDate') }} <span class="text-red-500">*</span>
                            </x-label>
                            <x-input type="date" wire:model.live="checkOutDate" class="block w-full" />
                            @error('checkOutDate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                {{ __('hotel::modules.checkIn.checkOutTime') }}
                            </x-label>
                            <x-input type="time" wire:model="checkOutTime" class="block w-full" />
                        </div>
                        <div>
                            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                {{ __('hotel::modules.reservation.adults') }} <span class="text-red-500">*</span>
                            </x-label>
                            <x-input type="number" min="1" wire:model="adults" class="block w-full" />
                            @error('adults')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                {{ __('hotel::modules.reservation.children') }}
                            </x-label>
                            <x-input type="number" min="0" wire:model="children" class="block w-full" />
                        </div>
                    </div>
                </div>

                {{-- ══ SECTION 2: Billing ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Section header --}}
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkIn.pricing') }}</span>
                    </div>

                    <div class="p-4 bg-white dark:bg-gray-800 space-y-3">
                        {{-- Rate chip --}}
                        <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <span class="text-xs text-indigo-700 dark:text-indigo-300">
                                <span class="font-semibold">{{ currency_format($ratePerNight) }}</span>
                                <span class="opacity-70"> / night</span>
                                <span class="mx-2 opacity-30">·</span>
                                <span class="font-semibold">{{ $nights }}</span>
                                <span class="opacity-70"> {{ Str::plural('night', $nights) }}</span>
                            </span>
                        </div>

                        {{-- Charge inputs --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                    {{ __('hotel::modules.checkIn.roomCharge') }} <span class="text-red-500">*</span>
                                </x-label>
                                <x-input type="number" step="0.01" min="0" wire:model.live="totalRoomCharge" class="block w-full font-semibold" />
                                @error('totalRoomCharge')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 block">
                                    {{ __('hotel::modules.checkIn.advancePaid') }}
                                </x-label>
                                <x-input type="number" step="0.01" min="0" wire:model.live="advancePaid" class="block w-full" placeholder="0.00" />
                                @error('advancePaid')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Balance summary strip --}}
                        @php $balance = max(0, (float)$totalRoomCharge - (float)$advancePaid); @endphp
                        <div class="flex items-center justify-between px-3 py-2.5 rounded-lg {{ $balance > 0 ? 'bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-700/50' : 'bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-700/50' }}">
                            <span class="text-xs font-medium {{ $balance > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                                {{ __('hotel::modules.checkOut.balanceDue') ?? 'Balance Due at Checkout' }}
                            </span>
                            <span class="text-sm font-bold {{ $balance > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $balance > 0 ? currency_format($balance) : '✓ Fully Paid' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ══ SECTION 3: Guests ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Section header --}}
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

                        {{-- Primary guest --}}
                        <div x-data="{ open: true }">
                            <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition text-left">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white text-xs font-bold shrink-0">1</span>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex-1 truncate">
                                    {{ trim(($checkInGuest['first_name'] ?? '') . ' ' . ($checkInGuest['last_name'] ?? '')) ?: __('hotel::modules.checkIn.primaryGuest') }}
                                </span>
                                <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 ring-1 ring-green-200 dark:bg-green-900/40 dark:text-green-400 dark:ring-green-700">
                                    {{ __('hotel::modules.checkIn.primaryBadge') }}
                                </span>
                            </button>

                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="px-4 pb-4 pt-1 bg-white dark:bg-gray-800 grid grid-cols-2 gap-x-4 gap-y-3">
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.firstName') }} <span class="text-red-500">*</span></x-label>
                                    <x-input wire:model="checkInGuest.first_name" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.firstName') }}" />
                                    @error('checkInGuest.first_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.lastName') }}</x-label>
                                    <x-input wire:model="checkInGuest.last_name" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.lastName') }}" />
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.phone') }} <span class="text-red-500">*</span></x-label>
                                    <x-input wire:model="checkInGuest.phone" type="text" class="block w-full" placeholder="+91 00000 00000" />
                                    @error('checkInGuest.phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.email') }}</x-label>
                                    <x-input wire:model="checkInGuest.email" type="email" class="block w-full" placeholder="email@example.com" />
                                    @error('checkInGuest.email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.idType') }} <span class="text-red-500">*</span></x-label>
                                    <x-select wire:model="checkInGuest.id_type" class="block w-full">
                                        <option value="">{{ __('hotel::modules.guest.selectIdType') }}</option>
                                        <option value="passport">{{ __('hotel::modules.guest.passport') }}</option>
                                        <option value="aadhaar">{{ __('hotel::modules.guest.aadhaar') }}</option>
                                        <option value="driving_license">{{ __('hotel::modules.guest.drivingLicense') }}</option>
                                        <option value="national_id">{{ __('hotel::modules.guest.nationalId') }}</option>
                                        <option value="other">{{ __('hotel::modules.guest.other') }}</option>
                                    </x-select>
                                    @error('checkInGuest.id_type')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.guest.idNumber') }} <span class="text-red-500">*</span></x-label>
                                    <x-input wire:model="checkInGuest.id_number" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.idNumber') }}" />
                                    @error('checkInGuest.id_number')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
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
                            </div>
                        </div>
                        @endforeach

                    </div>
                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-secondary-button wire:click="closeCheckInModal">{{ __('hotel::modules.checkIn.cancel') }}</x-secondary-button>
                <x-button wire:click="processRoomCheckIn" wire:loading.attr="disabled">
                    {{ __('hotel::modules.checkIn.confirmCheckIn') }}
                </x-button>
            </div>
        </x-slot>
    </x-right-modal>
    @endif
</div>
