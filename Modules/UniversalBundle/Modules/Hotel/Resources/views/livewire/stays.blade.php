<div>
    {{-- ── Header ── --}}
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.stays.title') }}</h1>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 mb-2">
                <div class="flex flex-col sm:flex-row gap-2 w-full">
                    <x-input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('hotel::modules.stays.searchPlaceholder') }}"
                        class="block w-full sm:w-80" />

                    <x-select wire:model.live="filterStatus" class="block w-full sm:w-44">
                        <option value="">{{ __('hotel::modules.stays.allStatuses') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-select>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Table ── --}}
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.stayNumber') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.room') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.primaryGuest') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.checkIn') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.checkOut') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.guests') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.balance') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.status') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.stays.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($stays as $stay)
                            @php
                                $primaryStayGuest = $stay->stayGuests->firstWhere('is_primary', true) ?? $stay->stayGuests->first();
                                $primaryGuest = $primaryStayGuest?->guest;
                                $totalGuests = $stay->adults + $stay->children;
                                $balance = $stay->folio?->balance ?? 0;
                                $statusColor = match($stay->status) {
                                    \Modules\Hotel\Enums\StayStatus::CHECKED_IN  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    \Modules\Hotel\Enums\StayStatus::CHECKED_OUT => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    \Modules\Hotel\Enums\StayStatus::EXTENDED    => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    default => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">

                                {{-- Stay Number --}}
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $stay->stay_number }}</span>
                                    @if(!$stay->reservation_id)
                                        <span class="ml-1.5 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                            Walk-in
                                        </span>
                                    @endif
                                </td>

                                {{-- Room --}}
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $stay->room?->room_number ?? '—' }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $stay->room?->roomType?->name ?? '' }}</div>
                                </td>

                                {{-- Primary Guest --}}
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($primaryGuest)
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $primaryGuest->full_name }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $primaryGuest->phone ?? '—' }}</div>
                                    @else
                                        <span class="text-gray-400 text-sm">—</span>
                                    @endif
                                </td>

                                {{-- Check-in --}}
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-800 dark:text-gray-200">{{ $stay->check_in_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $stay->check_in_at->format('g:i A') }}</div>
                                </td>

                                {{-- Check-out --}}
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($stay->actual_checkout_at)
                                        <div class="text-sm text-gray-800 dark:text-gray-200">{{ $stay->actual_checkout_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $stay->actual_checkout_at->format('g:i A') }}</div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Exp: {{ $stay->expected_checkout_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $stay->expected_checkout_at->format('g:i A') }}</div>
                                    @endif
                                </td>

                                {{-- Guests --}}
                                <td class="py-3 px-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">{{ $stay->adults }}</span> <span class="text-xs text-gray-400">A</span>
                                    @if($stay->children > 0)
                                        &nbsp;· <span class="font-medium">{{ $stay->children }}</span> <span class="text-xs text-gray-400">C</span>
                                    @endif
                                </td>

                                {{-- Balance --}}
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($balance > 0)
                                        <span class="text-sm font-semibold text-amber-600 dark:text-amber-400">{{ number_format($balance, 2) }}</span>
                                    @else
                                        <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Settled</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                        {{ $stay->status->label() }}
                                    </span>
                                </td>

                                {{-- Action --}}
                                <td class="py-3 px-4 whitespace-nowrap text-right">
                                    <button wire:click="viewStay({{ $stay->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-skin-base text-white hover:opacity-90 transition shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ __('hotel::modules.stays.view') }}
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="py-12 px-4 text-center" colspan="9">
                                    <div class="flex flex-col items-center gap-3">
                                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </span>
                                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('hotel::modules.stays.noStaysFound') }}</p>
                                    </div>
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
        {{ $stays->links() }}
    </div>

    {{-- ── View Stay Modal ── --}}
    @if($selectedStay)
    <x-right-modal wire:model.live="showViewModal" maxWidth="3xl">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <span>{{ $selectedStay->stay_number }}</span>
                @php
                    $mColor = match($selectedStay->status) {
                        \Modules\Hotel\Enums\StayStatus::CHECKED_IN  => 'bg-emerald-100 text-emerald-700',
                        \Modules\Hotel\Enums\StayStatus::CHECKED_OUT => 'bg-gray-100 text-gray-600',
                        \Modules\Hotel\Enums\StayStatus::EXTENDED    => 'bg-blue-100 text-blue-700',
                        default => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $mColor }}">{{ $selectedStay->status->label() }}</span>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">

                {{-- ── Room Info ── --}}
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/50 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                        </svg>
                        <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">{{ __('hotel::modules.stays.roomDetails') }}</span>
                        @if(!$selectedStay->reservation_id)
                            <span class="ml-auto text-[10px] font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">Walk-in</span>
                        @else
                            <span class="ml-auto text-[10px] font-medium text-gray-400 dark:text-gray-500">Res: {{ $selectedStay->reservation?->reservation_number }}</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 divide-x divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <div class="px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.stays.room') }}</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $selectedStay->room?->room_number ?? '—' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.stays.roomType') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $selectedStay->room?->roomType?->name ?? '—' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.stays.checkIn') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $selectedStay->check_in_at->format('M d, Y · g:i A') }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">
                                {{ $selectedStay->actual_checkout_at ? __('hotel::modules.stays.actualCheckOut') : __('hotel::modules.stays.expectedCheckOut') }}
                            </p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                {{ ($selectedStay->actual_checkout_at ?? $selectedStay->expected_checkout_at)->format('M d, Y · g:i A') }}
                            </p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.stays.adults') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $selectedStay->adults }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.stays.children') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $selectedStay->children }}</p>
                        </div>
                        @if($selectedStay->check_in_notes)
                        <div class="px-4 py-3 col-span-2">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.stays.checkInNotes') }}</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $selectedStay->check_in_notes }}</p>
                        </div>
                        @endif
                        @if($selectedStay->check_out_notes)
                        <div class="px-4 py-3 col-span-2">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.stays.checkOutNotes') }}</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $selectedStay->check_out_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ── Guests ── --}}
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">
                        {{ __('hotel::modules.stays.guestDetails') }}
                    </p>
                    <div class="space-y-2">
                        @forelse($selectedStay->stayGuests as $stayGuest)
                        @php $g = $stayGuest->guest; @endphp
                        <div class="rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden">
                            <div class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $stayGuest->is_primary ? 'bg-green-500' : 'bg-blue-500' }} text-white text-xs font-bold shrink-0">
                                    {{ $loop->iteration }}
                                </span>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex-1 truncate">{{ $g?->full_name ?? '—' }}</span>
                                @if($stayGuest->is_primary)
                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 border border-green-200 dark:border-green-700">
                                        {{ __('hotel::modules.checkIn.primaryBadge') }}
                                    </span>
                                @endif
                            </div>
                            @if($g)
                            <div class="grid grid-cols-2 divide-x divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                <div class="px-4 py-2.5">
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.guest.phone') }}</p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $g->phone ?: '—' }}</p>
                                </div>
                                <div class="px-4 py-2.5">
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.guest.email') }}</p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $g->email ?: '—' }}</p>
                                </div>
                                <div class="px-4 py-2.5">
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.guest.idType') }}</p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $g->id_type ? ucwords(str_replace('_', ' ', $g->id_type)) : '—' }}</p>
                                </div>
                                <div class="px-4 py-2.5">
                                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">{{ __('hotel::modules.guest.idNumber') }}</p>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $g->id_number ?: '—' }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                        @empty
                        <p class="text-xs text-gray-400 italic px-1">{{ __('hotel::modules.stays.noGuests') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- ── Folio Summary ── --}}
                @if($selectedStay->folio)
                @php $folio = $selectedStay->folio; @endphp
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">
                        {{ __('hotel::modules.stays.folioSummary') }}
                    </p>

                    {{-- Totals card --}}
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-3 text-center">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">{{ __('hotel::modules.stays.totalCharges') }}</p>
                            <p class="text-base font-bold text-gray-800 dark:text-white">{{ number_format($folio->total_charges, 2) }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-3 text-center">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">{{ __('hotel::modules.stays.totalPaid') }}</p>
                            <p class="text-base font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($folio->total_payments, 2) }}</p>
                        </div>
                        <div class="rounded-xl border {{ $folio->balance > 0 ? 'border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20' : 'border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-900/10' }} px-4 py-3 text-center">
                            <p class="text-[10px] uppercase tracking-wide {{ $folio->balance > 0 ? 'text-amber-500' : 'text-emerald-500' }} mb-1">{{ __('hotel::modules.stays.balance') }}</p>
                            <p class="text-base font-bold {{ $folio->balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($folio->balance, 2) }}</p>
                        </div>
                    </div>

                    {{-- Folio Lines --}}
                    @if($folio->folioLines->count())
                    <div class="rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden mb-3">
                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('hotel::modules.stays.charges') }}</p>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach($folio->folioLines as $line)
                            <div class="flex items-center justify-between px-4 py-2.5 gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 dark:text-gray-200 truncate">{{ $line->description }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($line->posting_date)->format('M d, Y') }} · {{ str_replace('_', ' ', $line->type->value) }}</p>
                                </div>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200 shrink-0">{{ number_format($line->net_amount, 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Payments --}}
                    @if($folio->folioPayments->count())
                    <div class="rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden">
                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('hotel::modules.stays.payments') }}</p>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach($folio->folioPayments as $payment)
                            <div class="flex items-center justify-between px-4 py-2.5 gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 dark:text-gray-200 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</p>
                                    @if($payment->transaction_reference)
                                        <p class="text-xs text-gray-400">Ref: {{ $payment->transaction_reference }}</p>
                                    @endif
                                </div>
                                <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400 shrink-0">{{ number_format($payment->amount, 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- ── Staff Info ── --}}
                @if($selectedStay->checkedInBy || $selectedStay->checkedOutBy)
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">{{ __('hotel::modules.stays.staffInfo') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        @if($selectedStay->checkedInBy)
                        <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">{{ __('hotel::modules.stays.checkedInBy') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $selectedStay->checkedInBy->name }}</p>
                        </div>
                        @endif
                        @if($selectedStay->checkedOutBy)
                        <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-3">
                            <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">{{ __('hotel::modules.stays.checkedOutBy') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $selectedStay->checkedOutBy->name }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end">
                <x-secondary-button wire:click="closeViewModal">{{ __('app.close') }}</x-secondary-button>
            </div>
        </x-slot>
    </x-right-modal>
    @endif
</div>
