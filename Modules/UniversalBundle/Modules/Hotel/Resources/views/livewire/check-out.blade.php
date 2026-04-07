<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.checkOut.checkOut') }}</h1>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 mb-4">
                <x-input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('hotel::modules.checkOut.searchPlaceholder') }}" class="block w-full sm:w-96" />
                <x-input type="date" wire:model.live="filterDate" class="block w-full sm:w-40" />
            </div>
        </div>
    </div>

    <div class="p-4">
        @if($stays->count())
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                @foreach ($stays as $stay)
                    @php
                        $isOverdue  = $stay->expected_checkout_at->isPast();
                        $isDueToday = $stay->expected_checkout_at->isToday() && !$isOverdue;
                    @endphp
                    <div class="rounded-2xl border {{ $isOverdue ? 'border-rose-200 dark:border-rose-800/50' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-900 flex flex-col overflow-hidden shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">

                        <div class="p-5 flex flex-col flex-1">
                            {{-- Stay number + balance badge --}}
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <p class="text-base font-bold text-gray-900 dark:text-white leading-tight tracking-tight">
                                    {{ $stay->stay_number }}
                                </p>
                                @if($stay->folio)
                                    <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold border
                                        {{ $stay->folio->balance > 0
                                            ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-700'
                                            : 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-700' }}">
                                        {{ currency_format($stay->folio->balance) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Room info --}}
                            <div class="flex items-center gap-2 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15v-6H9v6H3.75A.75.75 0 013 21V9.75z" />
                                </svg>
                                <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $stay->room->room_number }}</span>
                                <span class="text-xs text-gray-400">·</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $stay->room->roomType->name }}</span>
                            </div>

                            {{-- Guest name --}}
                            <div class="flex items-center gap-2 mb-5">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                    {{ $stay->stayGuests->first()?->guest->full_name ?? 'N/A' }}
                                </span>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-gray-100 dark:border-gray-700/60 mb-3"></div>

                            {{-- Date/time + button on same row --}}
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-1.5 text-xs min-w-0 {{ $isOverdue ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="truncate">{{ $stay->expected_checkout_at->format('M d, Y') }}</span>
                                    <span class="opacity-50 shrink-0">·</span>
                                    <span class="shrink-0">{{ $stay->expected_checkout_at->format('g:i A') }}</span>
                                </div>
                                <button wire:click='showCheckOutForm({{ $stay->id }})'
                                    class="shrink-0 inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg text-white transition-colors duration-150
                                        {{ $isOverdue ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                    {{ __('hotel::modules.checkOut.checkOutButton') }}
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </span>
                <p class="text-base font-medium text-gray-500 dark:text-gray-400">
                    {{ __('hotel::modules.checkOut.noStaysReadyForCheckOut') }}
                </p>
            </div>
        @endif
    </div>

    <div class="p-4">
        {{ $stays->links() }}
    </div>

    @if ($selectedStay && $selectedStay->folio)
    <x-right-modal wire:model.live="showCheckOutModal">
        <x-slot name="title">{{ __('hotel::modules.checkOut.checkOutModalTitle', ['number' => $selectedStay->stay_number]) }}</x-slot>

        <x-slot name="content">
            @php
                $advancePayments    = $selectedStay->folio->folioPayments->where('payment_method', 'advance');
                $otherPayments      = $selectedStay->folio->folioPayments->where('payment_method', '!=', 'advance');
                $advanceTotal       = $advancePayments->sum('amount');
                $otherTotal         = $otherPayments->sum('amount');
                $paidRsTotal        = $paidRoomServiceTotal ?? 0;
                $baseBalance        = $effectiveBalance ?? $selectedStay->folio->balance;
                $discount           = max(0, (float)($discountAmount ?? 0));
                $displayBalance     = max(0, $baseBalance - $discount);
            @endphp

            <div class="space-y-5">

                {{-- ══ Stay banner ══ --}}
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-slate-50 to-gray-50 border border-gray-200 dark:from-gray-700/40 dark:to-gray-700/20 dark:border-gray-600">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-slate-500 dark:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15v-6H9v6H3.75A.75.75 0 013 21V9.75z" />
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight">
                            {{ __('hotel::modules.checkOut.room') }} {{ $selectedStay->room->room_number }}
                            <span class="font-normal text-gray-500 dark:text-gray-400">— {{ $selectedStay->room->roomType->name }}</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                            {{ $selectedStay->stayGuests->first()?->guest->full_name ?? '—' }}
                        </p>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full
                        {{ $displayBalance > 0 ? 'bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-700' : 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-700' }}">
                        {{ $displayBalance > 0 ? currency_format($displayBalance) . ' due' : '✓ Paid' }}
                    </span>
                </div>

                {{-- ══ SECTION 1: Folio lines ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkOut.folioSummary') }}</span>
                    </div>

                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700/60">
                                <th class="py-2 px-4 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('hotel::modules.checkOut.date') }}</th>
                                <th class="py-2 px-4 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('hotel::modules.checkOut.description') }}</th>
                                <th class="py-2 px-4 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('hotel::modules.checkOut.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700/50">
                            @foreach($selectedStay->folio->folioLines as $line)
                            <tr>
                                <td class="py-2 px-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $line->posting_date->format('M d, Y') }}</td>
                                <td class="py-2 px-4 text-sm text-gray-800 dark:text-gray-200">{{ $line->description }}</td>
                                <td class="py-2 px-4 text-sm font-medium text-gray-900 dark:text-white text-right whitespace-nowrap">{{ currency_format($line->net_amount) }}</td>
                            </tr>
                            @endforeach

                            {{-- Advance payment row --}}
                            @if($advanceTotal > 0)
                            <tr class="bg-emerald-50/50 dark:bg-emerald-900/10">
                                <td class="py-2 px-4 text-xs text-gray-400"></td>
                                <td class="py-2 px-4 text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ __('hotel::modules.reservation.advancePaid') }}</td>
                                <td class="py-2 px-4 text-sm font-semibold text-emerald-700 dark:text-emerald-400 text-right">−{{ currency_format($advanceTotal) }}</td>
                            </tr>
                            @endif

                            {{-- Prior payments row --}}
                            @if($otherTotal > 0)
                            <tr class="bg-emerald-50/50 dark:bg-emerald-900/10">
                                <td class="py-2 px-4 text-xs text-gray-400"></td>
                                <td class="py-2 px-4 text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ __('hotel::modules.checkOut.payments') }}</td>
                                <td class="py-2 px-4 text-sm font-semibold text-emerald-700 dark:text-emerald-400 text-right">−{{ currency_format($otherTotal) }}</td>
                            </tr>
                            @endif

                            {{-- Paid room-service row --}}
                            @if($paidRsTotal > 0)
                            <tr class="bg-emerald-50/50 dark:bg-emerald-900/10">
                                <td class="py-2 px-4 text-xs text-gray-400"></td>
                                <td class="py-2 px-4 text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ __('hotel::modules.checkOut.paidRoomService') }}</td>
                                <td class="py-2 px-4 text-sm font-semibold text-emerald-700 dark:text-emerald-400 text-right">−{{ currency_format($paidRsTotal) }}</td>
                            </tr>
                            @endif

                            {{-- Live discount row (shows as soon as user types) --}}
                            @if($discount > 0)
                            <tr class="bg-violet-50/50 dark:bg-violet-900/10">
                                <td class="py-2 px-4 text-xs text-gray-400"></td>
                                <td class="py-2 px-4 text-sm font-medium text-violet-700 dark:text-violet-400">{{ __('hotel::modules.checkOut.discountAmount') }}</td>
                                <td class="py-2 px-4 text-sm font-semibold text-violet-700 dark:text-violet-400 text-right">−{{ currency_format($discount) }}</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200 dark:border-gray-600 {{ $displayBalance > 0 ? 'bg-amber-50 dark:bg-amber-900/10' : 'bg-emerald-50 dark:bg-emerald-900/10' }}">
                                <td colspan="2" class="py-3 px-4 text-sm font-bold {{ $displayBalance > 0 ? 'text-amber-800 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                                    {{ __('hotel::modules.checkOut.totalBalance') }}
                                </td>
                                <td class="py-3 px-4 text-base font-bold text-right {{ $displayBalance > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $displayBalance > 0 ? currency_format($displayBalance) : '✓ ' . currency_format(0) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ══ SECTION 2: Discount ══ --}}
                @if(user_can('Apply Hotel Folio Discount'))
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkOut.discountAmount') }}</span>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800">
                        <x-input type="number" wire:model.live="discountAmount" step="0.01" min="0" class="block w-full"
                            placeholder="0.00" />
                        <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">{{ __('hotel::modules.checkOut.discountAmount') }} — {{ __('hotel::modules.checkIn.balanceDue') ?? 'balance updates instantly above' }}</p>
                    </div>
                </div>
                @endif

                {{-- ══ SECTION 3: Payment ══ --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkOut.paymentMethod') }}</span>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 grid grid-cols-2 gap-4">
                        <div>
                            <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.checkOut.paymentMethod') }}</x-label>
                            <x-select wire:model="paymentMethod" class="block w-full">
                                <option value="cash">{{ __('hotel::modules.checkOut.cash') }}</option>
                                <option value="card">{{ __('hotel::modules.checkOut.card') }}</option>
                                <option value="upi">{{ __('hotel::modules.checkOut.upi') }}</option>
                                <option value="bank_transfer">{{ __('hotel::modules.checkOut.bankTransfer') }}</option>
                                <option value="other">{{ __('hotel::modules.checkOut.other') }}</option>
                            </x-select>
                        </div>
                        <div>
                            <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.checkOut.paymentAmount') }}</x-label>
                            <x-input type="number" wire:model="paymentAmount" step="0.01" min="0" class="block w-full font-semibold" />
                        </div>
                        <div class="col-span-2">
                            <x-label class="text-xs text-gray-500 mb-1.5 block">{{ __('hotel::modules.checkOut.transactionReference') }}</x-label>
                            <x-input type="text" wire:model="transactionReference" class="block w-full" placeholder="Ref / UTR / Cheque no." />
                        </div>
                    </div>
                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-secondary-button wire:click="$set('showCheckOutModal', false)">{{ __('hotel::modules.checkOut.cancel') }}</x-secondary-button>
                <x-button wire:click="processCheckOut({{ $selectedStay->id }})">{{ __('hotel::modules.checkOut.confirmCheckOut') }}</x-button>
            </div>
        </x-slot>
    </x-right-modal>
    @endif
</div>
