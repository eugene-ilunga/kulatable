<div>
    {{-- Hero / Header --}}
    <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <div class="absolute inset-0 opacity-60 dark:opacity-30"
                style="background-image: radial-gradient(circle at 20% 20%, rgba(59,130,246,0.18), transparent 45%), radial-gradient(circle at 80% 30%, rgba(16,185,129,0.14), transparent 40%), radial-gradient(circle at 60% 80%, rgba(99,102,241,0.12), transparent 45%);">
            </div>

            <div class="relative p-5 sm:p-7">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-sm">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white leading-tight truncate">
                                    {{ __('hotel::modules.frontDesk.dashboard') }}
                                </h1>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ now(timezone())->format('l, F d, Y') }}
                                    </span>
                                    <span class="hidden sm:inline text-gray-400 dark:text-gray-500">•</span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ now(timezone())->format('g:i A') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('hotel.check-in.index') }}"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-xl bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-900">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ __('hotel::modules.frontDesk.checkIn') }}
                        </a>
                        <a href="{{ route('hotel.check-out.index') }}"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-xl bg-white text-gray-900 border border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            {{ __('hotel::modules.frontDesk.checkOut') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="p-4 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('hotel::modules.frontDesk.arrivalsToday') }}</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $arrivalsCount }}</p>
        </div>
                        <div class="h-10 w-10 rounded-xl bg-blue-600/10 text-blue-700 dark:text-blue-300 dark:bg-blue-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
        </div>
        </div>
        </div>
    </div>

            <div class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('hotel::modules.frontDesk.departuresToday') }}</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $departuresCount }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-rose-600/10 text-rose-700 dark:text-rose-300 dark:bg-rose-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('hotel::modules.frontDesk.inHouse') }}</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $inHouseCount }}</p>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-emerald-600/10 text-emerald-700 dark:text-emerald-300 dark:bg-emerald-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('hotel::modules.frontDesk.occupancyRate') }}: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $occupancyRate }}%</span>
                    </p>
                </div>
        </div>

            <div class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('hotel::modules.frontDesk.occupancyRate') }}</p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $occupancyRate }}%</p>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-indigo-600/10 text-indigo-700 dark:text-indigo-300 dark:bg-indigo-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19V6m4 13V10m4 9V4M7 19v-3M3 19h18"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                            <div class="h-2 rounded-full bg-gradient-to-r from-indigo-600 to-blue-600" style="width: {{ min(100, max(0, (float) $occupancyRate)) }}%"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>0%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main grid --}}
    <div class="grid lg:grid-cols-3 gap-4 p-4 sm:p-6 pt-0">
        {{-- Arrivals --}}
        <div class="lg:col-span-1 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.frontDesk.todaysArrivals') }}</h3>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200">
                    {{ $arrivalsCount }}
                </span>
            </div>
            <div class="p-4">
                @if($arrivals->count() > 0)
                    <div class="space-y-3">
                        @foreach($arrivals as $reservation)
                            <div class="group p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $reservation->reservation_number }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ $reservation->primaryGuest->full_name }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $reservation->check_in_time ? Carbon\Carbon::parse($reservation->check_in_time)->format('g:i A') : __('hotel::modules.frontDesk.notAvailable') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('hotel.check-in.index') }}"
                                       class="shrink-0 inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                                        {{ __('hotel::modules.frontDesk.checkIn') }}
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900/30 border border-dashed border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('hotel::modules.frontDesk.noArrivalsScheduled') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Departures --}}
        <div class="lg:col-span-1 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-rose-600"></span>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.frontDesk.todaysDepartures') }}</h3>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200">
                    {{ $departuresCount }}
                </span>
            </div>
            <div class="p-4">
                @if($departures->count() > 0)
                    <div class="space-y-3">
                        @foreach($departures as $reservation)
                            <div class="group p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white truncate">{{ $reservation->reservation_number }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ $reservation->primaryGuest->full_name }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $reservation->check_out_time ? Carbon\Carbon::parse($reservation->check_out_time)->format('g:i A') : __('hotel::modules.frontDesk.notAvailable') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('hotel.check-out.index') }}"
                                       class="shrink-0 inline-flex items-center text-sm font-medium text-rose-700 hover:text-rose-800 dark:text-rose-300 dark:hover:text-rose-200">
                                        {{ __('hotel::modules.frontDesk.checkOut') }}
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
                    <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900/30 border border-dashed border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('hotel::modules.frontDesk.noDeparturesScheduled') }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- In-house snapshot --}}
        <div class="lg:col-span-1 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.frontDesk.inHouse') }}</h3>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ $inHouseCount }}
                </span>
            </div>
            <div class="p-4">
                @if(($inHouse ?? collect())->count() > 0)
                    <div class="space-y-3">
                        @foreach(($inHouse ?? collect())->take(6) as $stay)
                            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $stay->room?->room_number ?? $stay->room?->name ?? __('app.notAvailable') }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 truncate">
                                            {{ $stay->room?->roomType?->name ?? __('app.notAvailable') }}
                                        </p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ ($stay->stayGuests?->count() ?? 0) }} {{ __('hotel::modules.frontDesk.inHouse') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-900/30 border border-dashed border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('app.notAvailable') }}</p>
                    </div>
            @endif
            </div>
        </div>
    </div>
</div>
