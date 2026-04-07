<div>

    {{-- ══════════════════════════════════════════
         PAGE HEADER & TOOLBAR
    ══════════════════════════════════════════ --}}
    <div class="px-5 pt-5 pb-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">

        {{-- Title row --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl tracking-tight">
                    {{ __('hotel::modules.reservation.reservations') }}
                </h1>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('hotel.reservations.availability') }}"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600 transition w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ __('hotel::modules.reservation.checkAvailability') }}
                </a>

                <button type="button"
                    wire:click="exportReservations"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600 transition w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v8m0 0l-3-3m3 3l3-3M4 6a2 2 0 012-2h12a2 2 0 012 2v3M6 20h12a2 2 0 002-2v-3M6 20a2 2 0 01-2-2v-3" />
                    </svg>
                    Export
                </button>
                @if(user_can('Create Hotel Reservation'))
                <a href="{{ route('hotel.reservations.create') }}"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-lg bg-skin-base text-white hover:opacity-90 transition shadow-sm w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('hotel::modules.reservation.newReservation') }}
                </a>
                @endif
            </div>
        </div>

        {{-- Filter toolbar --}}
        <div class="flex flex-col sm:flex-row gap-2">
            {{-- Search --}}
            <div class="relative flex-1 min-w-0 sm:max-w-sm">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 111 11a6 6 0 0116 0z" />
                    </svg>
                </div>
                <x-input type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('hotel::modules.reservation.searchPlaceholder') }}"
                    class="block w-full pl-9" />
            </div>

            {{-- Date filter --}}
            <x-input type="date" wire:model.live="filterDate" class="block w-full sm:w-40" />

            {{-- Status filter --}}
            <x-select wire:model.live="filterStatus" class="block w-full sm:w-44">
                <option value="">{{ __('hotel::modules.reservation.allStatuses') }}</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </x-select>
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         TABLE
    ══════════════════════════════════════════ --}}
    @php
        $statusCfg = [
            'tentative'   => ['dot' => 'bg-amber-400',   'badge' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-700'],
            'confirmed'   => ['dot' => 'bg-blue-500',    'badge' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-700'],
            'checked_in'  => ['dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-700'],
            'checked_out' => ['dot' => 'bg-slate-400',   'badge' => 'bg-slate-50 text-slate-600 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600'],
            'cancelled'   => ['dot' => 'bg-rose-500',    'badge' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:ring-rose-700'],
            'no_show'     => ['dot' => 'bg-orange-400',  'badge' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:ring-orange-700'],
        ];
    @endphp

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.reservationNumber') }}</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.guest') }}</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.checkIn') }}</th>
                    <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.checkOut') }}</th>
                    <th class="px-5 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.rooms') }}</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.amount') }}</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.status') }}</th>
                    <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.action') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @forelse ($reservations as $reservation)
                @php
                    $sc = $statusCfg[$reservation->status->value] ?? ['dot' => 'bg-gray-400', 'badge' => 'bg-gray-100 text-gray-600 ring-gray-200'];
                    $initials = collect(explode(' ', $reservation->primaryGuest->full_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
                @endphp
                <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors duration-100">

                    {{-- Reservation # --}}
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <span class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ $reservation->reservation_number }}
                        </span>
                    </td>

                    {{-- Guest --}}
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold shrink-0">
                                {{ $initials }}
                            </span>
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate max-w-[140px]">
                                {{ $reservation->primaryGuest->full_name }}
                            </span>
                        </div>
                    </td>

                    {{-- Check-in --}}
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $reservation->check_in_date->format('M d, Y') }}</span>
                        </div>
                    </td>

                    {{-- Check-out --}}
                    <td class="px-5 py-3.5 whitespace-nowrap">
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-200">{{ $reservation->check_out_date->format('M d, Y') }}</span>
                        </div>
                    </td>

                    {{-- Rooms --}}
                    <td class="px-5 py-3.5 whitespace-nowrap text-center">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 text-xs font-bold">
                            {{ $reservation->rooms_count }}
                        </span>
                    </td>

                    {{-- Amount --}}
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ currency_format($reservation->total_amount) }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold ring-1 {{ $sc['badge'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }} shrink-0"></span>
                            {{ $reservation->status->label() }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                        <div class="inline-flex items-center justify-end gap-1">
                            @if(user_can('Update Hotel Reservation'))
                                <x-secondary-button-table wire:click='showEditReservation({{ $reservation->id }})'>
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path></svg>
                                    {{ __('hotel::modules.reservation.view') }}
                                </x-secondary-button-table>
                            @endif

                            @if(user_can('Update Hotel Reservation') && in_array($reservation->status->value, ['tentative', 'confirmed']))
                                <a href="{{ route('hotel.reservations.edit', $reservation) }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
                                    {{ __('hotel::modules.reservation.edit') }}
                                </a>
                            @endif

                            @if(user_can('Cancel Hotel Reservation') && in_array($reservation->status->value, ['tentative', 'confirmed']))
                                <x-danger-button-table wire:click="showCancelReservation({{ $reservation->id }})">
                                    {{ __('hotel::modules.reservation.cancel') }}
                                </x-danger-button-table>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </span>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.noReservationsFound') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $reservations->links() }}
    </div>

    {{-- ══════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════ --}}

    {{-- View / Detail Reservation --}}
    <x-right-modal wire:model.live="showEditReservationModal" maxWidth="3xl">
        <x-slot name="title">
            @if($activeReservation ?? null)
            @php
                $titleStatusCfg = [
                    'confirmed'   => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                    'tentative'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                    'checked_in'  => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
                    'checked_out' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                    'cancelled'   => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                    'no_show'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                ];
                $titleStatusStyle = $titleStatusCfg[$activeReservation->status->value] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
            @endphp
            <span class="flex items-center gap-2.5">
                <span class="text-gray-900 dark:text-white font-bold">{{ $activeReservation->reservation_number }}</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $titleStatusStyle }}">{{ $activeReservation->status->label() }}</span>
            </span>
            @endif
        </x-slot>

        <x-slot name="content">
            @if ($showEditReservationModal && $activeReservation)
            <div class="space-y-5">

                {{-- ══ Reservation banner ══ --}}
                @php
                    $nights = $activeReservation->check_in_date->diffInDays($activeReservation->check_out_date);
                @endphp
                {{-- ══ SECTION 1: Guest ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.guest') }}</span>
                    </div>
                    <div class="px-4 py-3.5 bg-white dark:bg-gray-800 flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-sm font-bold shrink-0">
                            {{ collect(explode(' ', $activeReservation->primaryGuest?->full_name ?? ''))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('') }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $activeReservation->primaryGuest?->full_name }}</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-0.5">
                                @if($activeReservation->primaryGuest?->phone)
                                <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V21a2 2 0 01-2 2h-1C9.716 23 3 16.284 3 8V5z"/></svg>
                                    {{ $activeReservation->primaryGuest->phone }}
                                </span>
                                @endif
                                @if($activeReservation->primaryGuest?->email)
                                <span class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    {{ $activeReservation->primaryGuest->email }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ══ SECTION 2: Stay Details ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkIn.stayDetails') }}</span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.checkIn') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $activeReservation->check_in_date->format('D, M d, Y') }}
                                @if($activeReservation->check_in_time) · {{ \Carbon\Carbon::parse($activeReservation->check_in_time)->format('H:i') }} @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.checkOut') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $activeReservation->check_out_date->format('D, M d, Y') }}
                                @if($activeReservation->check_out_time) · {{ \Carbon\Carbon::parse($activeReservation->check_out_time)->format('H:i') }} @endif
                            </span>
                        </div>
                        @if($activeReservation->ratePlan)
                        <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.ratePlanLabel') }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $activeReservation->ratePlan->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ══ SECTION 3: Rooms & Pricing ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15v-6H9v6H3.75A.75.75 0 013 21V9.75z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.rooms') }}</span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                        @foreach($activeReservation->reservationRooms as $rr)
                        <div class="flex items-center justify-between px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold">{{ $rr->quantity }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rr->roomType?->name }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-gray-400">{{ currency_format($rr->rate) }} / {{ __('hotel::modules.reservation.night') }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($rr->total_amount) }}</span>
                            </div>
                        </div>
                        @endforeach
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50/60 dark:bg-gray-700/30">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.totalStay') }}</span>
                            <span class="text-base font-bold text-gray-900 dark:text-white">{{ currency_format($activeReservation->total_amount) }}</span>
                        </div>
                    </div>
                </div>

                {{-- ══ Amount Summary ══ --}}
                @php
                    $roomsTotal = (float) ($activeReservation->reservationRooms->sum('total_amount') ?? 0);
                    $extrasTotal = (float) ($activeReservation->reservationExtras->sum('total_amount') ?? 0);
                    $grossSubtotal = $roomsTotal + $extrasTotal;
                    $netAfterDiscount = (float) ($activeReservation->subtotal_before_tax ?? 0);
                    $discountAmount = max(0, $grossSubtotal - $netAfterDiscount);

                    $taxAmountSummary = (float) ($activeReservation->tax_amount ?? 0);
                    $totalAmountSummary = (float) ($activeReservation->total_amount ?? 0);

                    $advancePaidSummary = (float) ($activeReservation->advance_paid ?? 0);
                    $folioPaymentsSummary = $activeReservation->stays->flatMap(fn($s) => $s->folio ? $s->folio->folioPayments : collect());

                    $advanceInPaymentsSummary = (float) $folioPaymentsSummary
                        ->filter(fn($p) => ($p->payment_method ?? null) === 'advance')
                        ->sum('amount');
                    $otherPaymentsSummary = (float) $folioPaymentsSummary
                        ->filter(fn($p) => ($p->payment_method ?? null) !== 'advance')
                        ->sum('amount');

                    $effectiveAdvancePaidSummary = $advanceInPaymentsSummary > 0 ? $advanceInPaymentsSummary : $advancePaidSummary;
                    $totalPaidSummary = (float) ($effectiveAdvancePaidSummary + $otherPaymentsSummary);
                    $balanceDueSummary = max(0, $totalAmountSummary - $totalPaidSummary);
                @endphp

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4m16 0V5a2 2 0 00-2-2H6a2 2 0 00-2 2v2m16 0v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7m16 0H4" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.bookingSummary') }}</span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                        <div class="flex justify-between px-4 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.roomsPrice') }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($roomsTotal) }}</span>
                        </div>
                        @if($extrasTotal > 0)
                        <div class="flex justify-between px-4 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.extrasPrice') }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($extrasTotal) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between px-4 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.discount') }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">- {{ currency_format($discountAmount) }}</span>
                        </div>
                        <div class="flex justify-between px-4 py-3 border-t border-dashed border-gray-200 dark:border-gray-600">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.subtotal') }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($netAfterDiscount) }}</span>
                        </div>
                        <div class="flex justify-between px-4 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('hotel::modules.reservation.bookingTax') }}
                                @if($activeReservation->tax?->rate !== null)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">({{ $activeReservation->tax->rate }}%)</span>
                                @endif
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($taxAmountSummary) }}</span>
                        </div>
                        <div class="flex justify-between px-4 py-3 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-300">{{ __('hotel::modules.reservation.total') }}</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ currency_format($totalAmountSummary) }}</span>
                        </div>
                        <div class="flex justify-between px-4 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.advancePaid') }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($effectiveAdvancePaidSummary) }}</span>
                        </div>
                        @if($otherPaymentsSummary > 0)
                            <div class="flex justify-between px-4 py-3">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.totalPaid') }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($totalPaidSummary) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between px-4 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.reservation.balanceDue') }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ currency_format($balanceDueSummary) }}</span>
                        </div>
                    </div>
                </div>


                {{-- ══ Special Requests ══ --}}
                @if($activeReservation->special_requests)
                <div class="rounded-xl border border-amber-200 dark:border-amber-700/50 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-700/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">{{ __('hotel::modules.reservation.specialRequestsLabel') }}</span>
                    </div>
                    <div class="px-4 py-3.5 bg-white dark:bg-gray-800">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $activeReservation->special_requests }}</p>
                    </div>
                </div>
                @endif

                <div class="flex justify-end">
                    <x-secondary-button-table
                        wire:click="downloadReservationReceipt({{ $activeReservation->id }})"
                        wire:key="reservation-receipt-{{ $activeReservation->id }}"
                        title="Download Invoice PDF">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 9V2h10v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M8 22h8a2 2 0 0 0 2-2v-4H6v4a2 2 0 0 0 2 2Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <span class="ml-2">{{ __('hotel::modules.reservation.downloadInvoice') }}</span>
                    </x-secondary-button-table>
                </div>

            </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-secondary-button wire:click="closeViewReservationModal">{{ __('app.close') }}</x-secondary-button>
            </div>
        </x-slot>
    </x-right-modal>

    {{-- Cancel Confirmation --}}
    @if ($activeReservation)
    <x-confirmation-modal wire:model="confirmCancelReservationModal">
        <x-slot name="title">{{ __('hotel::modules.reservation.cancelReservation') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.reservation.cancelReservationMessage', ['number' => $activeReservation->reservation_number]) }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmCancelReservationModal')">{{ __('app.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click="cancelReservation({{ $activeReservation->id }})">{{ __('hotel::modules.reservation.confirm') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif

</div>
