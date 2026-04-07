<div>
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 sm:p-6 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ __('hotel::modules.checkAvailability.selectDates') }}</h2>
        <div class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="flex-1 min-w-0">
                <x-label for="check_in_date" value="{{ __('hotel::modules.reservation.checkInDate') }}" />
                <x-input id="check_in_date" class="block mt-1 w-full" type="date" wire:model='check_in_date' />
                <x-input-error for="check_in_date" class="mt-2" />
            </div>
            <div class="flex-1 min-w-0">
                <x-label for="check_out_date" value="{{ __('hotel::modules.reservation.checkOutDate') }}" />
                <x-input id="check_out_date" class="block mt-1 w-full" type="date" wire:model='check_out_date' />
                <x-input-error for="check_out_date" class="mt-2" />
            </div>
            <x-button type="button" wire:click="checkAvailability" wire:loading.attr="disabled">
                {{ __('hotel::modules.checkAvailability.checkAvailability') }}
            </x-button>
        </div>
    </div>

    @if(count($availability) > 0)
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('hotel::modules.checkAvailability.availabilityByRoomType') }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($check_in_date)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($check_out_date)->format('M d, Y') }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="py-3 px-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('hotel::modules.checkAvailability.roomType') }}</th>
                            <th class="py-3 px-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('hotel::modules.checkAvailability.total') }}</th>
                            <th class="py-3 px-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('hotel::modules.checkAvailability.available') }}</th>
                            <th class="py-3 px-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('hotel::modules.checkAvailability.occupied') }}</th>
                            <th class="py-3 px-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">{{ __('hotel::modules.checkAvailability.rate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                        @foreach($availability as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="py-3 px-4">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $row['room_type']->name }}</span>
                                </td>
                                <td class="py-3 px-4 text-right text-gray-700 dark:text-gray-300">{{ $row['total'] }}</td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-semibold {{ $row['available'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $row['available'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right text-gray-600 dark:text-gray-400">{{ $row['occupied'] }}</td>
                                <td class="py-3 px-4 text-right font-medium text-gray-900 dark:text-white">{{ currency_format($row['room_type']->base_rate) }} <span class="text-gray-500 dark:text-gray-400 text-xs">/ {{ __('hotel::modules.reservation.night') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkAvailability.selectDatesMessage') }}</p>
        </div>
    @endif
</div>
