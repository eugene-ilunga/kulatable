<div>
    @if(!$folio)
    <div class="p-4">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 dark:bg-yellow-900/20 dark:border-yellow-800">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ __('hotel::modules.folio.noFolioFound') }}</p>
        </div>
    </div>
    @else
    <div class="p-4 bg-white block dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.folio.folio') }}: {{ $folio->folio_number }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('hotel::modules.folio.stay') }}: {{ $stay->stay_number }} | {{ __('hotel::modules.folio.room') }}: {{ $stay->room->room_number }} | {{ __('hotel::modules.folio.guest') }}: {{ $stay->stayGuests->first()?->guest->full_name ?? __('app.notAvailable') }}
                </p>
            </div>
            <div class="flex gap-2">
                @if(user_can('Post To Hotel Folio') && $folio->status->value === 'open')
                <x-button wire:click="$toggle('showAddChargeModal')">{{ __('hotel::modules.folio.addCharge') }}</x-button>
                @endif
                @if($folio->status->value === 'open')
                <x-button wire:click="$toggle('showPaymentModal')">{{ __('hotel::modules.folio.addPayment') }}</x-button>
                @endif
            </div>
        </div>
    </div>

    <div class="p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-4">
            <div class="p-6 grid grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.folio.totalCharges') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ currency_format($folio->total_charges) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.folio.totalPayments') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ currency_format($folio->total_payments) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.folio.balance') }}</p>
                    <p class="text-2xl font-semibold {{ $folio->balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ currency_format($folio->balance) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.folio.folioDetails') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.folio.date') }}</th>
                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.folio.type') }}</th>
                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.folio.description') }}</th>
                            <th class="py-2 px-4 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.folio.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @foreach($folio->folioLines as $line)
                        <tr>
                            <td class="py-2 px-4 text-sm text-gray-900 dark:text-white">{{ $line->posting_date->format('M d, Y') }}</td>
                            <td class="py-2 px-4 text-sm text-gray-900 dark:text-white">{{ $line->type->label() }}</td>
                            <td class="py-2 px-4 text-sm text-gray-900 dark:text-white">{{ $line->description }}</td>
                            <td class="py-2 px-4 text-sm text-gray-900 dark:text-white text-right">{{ currency_format($line->net_amount) }}</td>
                        </tr>
                        @endforeach
                        @if($folio->folioPayments->count() > 0)
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <td colspan="3" class="py-2 px-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.folio.payments') }}</td>
                            <td class="py-2 px-4"></td>
                        </tr>
                        @foreach($folio->folioPayments as $payment)
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <td class="py-2 px-4 text-sm text-gray-900 dark:text-white">{{ $payment->created_at->format('M d, Y') }}</td>
                            <td class="py-2 px-4 text-sm text-gray-900 dark:text-white">{{ __('hotel::modules.folio.payment') }}</td>
                            <td class="py-2 px-4 text-sm text-gray-900 dark:text-white">{{ ucfirst($payment->payment_method) }} @if($payment->transaction_reference)({{ $payment->transaction_reference }})@endif</td>
                            <td class="py-2 px-4 text-sm text-red-600 dark:text-red-400 text-right">-{{ currency_format($payment->amount) }}</td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <td colspan="3" class="py-2 px-4 text-sm font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.folio.balance') }}</td>
                            <td class="py-2 px-4 text-sm font-semibold {{ $folio->balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} text-right">
                                {{ currency_format($folio->balance) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <x-modal wire:model.live="showAddChargeModal">
        <x-slot name="title">{{ __('hotel::modules.folio.addCharge') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="chargeType" value="{{ __('hotel::modules.folio.chargeType') }} *" />
                    <x-select id="chargeType" wire:model="chargeType" class="block w-full" required>
                        @foreach($chargeTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <x-label for="chargeDescription" value="{{ __('hotel::modules.folio.chargeDescription') }} *" />
                    <x-input id="chargeDescription" wire:model="chargeDescription" type="text" class="block w-full" required />
                </div>

                <div>
                    <x-label for="chargeAmount" value="{{ __('hotel::modules.folio.chargeAmount') }} *" />
                    <x-input id="chargeAmount" wire:model="chargeAmount" type="number" step="0.01" min="0" class="block w-full" required />
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddChargeModal', false)">{{ __('hotel::modules.folio.cancel') }}</x-secondary-button>
            <x-button wire:click="addCharge">{{ __('hotel::modules.folio.addCharge') }}</x-button>
        </x-slot>
    </x-modal>

    <x-modal wire:model.live="showPaymentModal">
        <x-slot name="title">{{ __('hotel::modules.folio.addPayment') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="paymentMethod" value="{{ __('hotel::modules.folio.paymentMethod') }} *" />
                    <x-select id="paymentMethod" wire:model="paymentMethod" class="block w-full" required>
                        <option value="cash">{{ __('hotel::modules.folio.cash') }}</option>
                        <option value="card">{{ __('hotel::modules.folio.card') }}</option>
                        <option value="upi">{{ __('hotel::modules.folio.upi') }}</option>
                        <option value="bank_transfer">{{ __('hotel::modules.folio.bankTransfer') }}</option>
                        <option value="other">{{ __('hotel::modules.folio.other') }}</option>
                    </x-select>
                </div>

                <div>
                    <x-label for="paymentAmount" value="{{ __('hotel::modules.folio.paymentAmount') }} *" />
                    <x-input id="paymentAmount" wire:model="paymentAmount" type="number" step="0.01" min="0" max="{{ $folio->balance }}" class="block w-full" required />
                </div>

                <div>
                    <x-label for="transactionReference" value="{{ __('hotel::modules.folio.transactionReference') }}" />
                    <x-input id="transactionReference" wire:model="transactionReference" type="text" class="block w-full" />
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showPaymentModal', false)">{{ __('hotel::modules.folio.cancel') }}</x-secondary-button>
            <x-button wire:click="addPayment">{{ __('hotel::modules.folio.addPayment') }}</x-button>
        </x-slot>
    </x-modal>
    @endif
</div>
