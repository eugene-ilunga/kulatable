<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.roomService.roomServiceOrders') }}</h1>
            </div>

            <div class="items-center justify-between block sm:flex">
                <div class="lg:flex items-center mb-4 sm:mb-0 flex-1">
                    <form class="sm:pr-3 flex-1" action="#" method="GET">
                        <label for="room-service-search" class="sr-only">{{ __('hotel::modules.roomService.searchPlaceholder') }}</label>
                        <div class="relative w-full mt-1 sm:w-48 xl:w-96">
                            <x-input id="room-service-search" class="block mt-1 w-full" type="text" placeholder="{{ __('hotel::modules.roomService.searchPlaceholder') }}" wire:model.live.debounce.300ms="search" />
                        </div>
                    </form>
                </div>

                <div class="lg:inline-flex items-center gap-4">
                    <a href="{{ route('pos.index') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                        {{ __('hotel::modules.roomService.createOrderViaPos') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 dark:bg-yellow-900/20 dark:border-yellow-800 mb-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                <strong>{{ __('hotel::modules.roomService.note') }}:</strong> {{ __('hotel::modules.roomService.createRoomServiceNote') }}
            </p>
        </div>
    </div>

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.roomService.orderNumber') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.roomService.roomStay') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.roomService.guest') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.roomService.amount') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.roomService.billTo') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.roomService.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($orders as $order)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white font-semibold">
                                    {{ $order->order_number }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    @if($order->context_id)
                                        @php
                                            $stay = \Modules\Hotel\Entities\Stay::find($order->context_id);
                                        @endphp
                                        @if($stay)
                                            {{ $stay->room->room_number }} ({{ $stay->stay_number }})
                                        @else
                                            {{ __('app.notAvailable') }}
                                        @endif
                                    @else
                                        {{ __('app.notAvailable') }}
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    @if($order->context_id)
                                        @php
                                            $stay = \Modules\Hotel\Entities\Stay::with(['stayGuests.guest'])->find($order->context_id);
                                            $guestName = __('hotel::modules.roomService.walkIn');
                                            if($stay && $stay->stayGuests->isNotEmpty()) {
                                                $primaryGuest = $stay->stayGuests->where('is_primary', true)->first() ?? $stay->stayGuests->first();
                                                if($primaryGuest && $primaryGuest->guest) {
                                                    $guestName = $primaryGuest->guest->full_name;
                                                }
                                            }
                                        @endphp
                                        {{ $guestName }}
                                    @else
                                        {{ __('hotel::modules.roomService.walkIn') }}
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ currency_format($order->total) }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    @if($order->bill_to === 'POST_TO_ROOM')
                                        <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full dark:bg-blue-900 dark:text-blue-200">{{ __('hotel::modules.roomService.postToRoom') }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full dark:bg-green-900 dark:text-green-200">{{ __('hotel::modules.roomService.payNow') }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    <div>
                                        <span @class(['text-sm font-medium px-2 py-1 rounded uppercase tracking-wide whitespace-nowrap ',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400 border border-gray-400' => ($order->status == 'draft'),
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-400 border border-yellow-400' => ($order->status == 'kot'),
                                        'bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-400 border border-blue-400' => ($order->status == 'billed'),
                                        'bg-green-100 text-green-800 dark:bg-gray-700 dark:text-green-400 border border-green-400' => ($order->status == 'paid'),
                                        'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-400 border border-red-400' => ($order->status == 'canceled'),
                                        ])>
                                            @lang('modules.order.' . $order->status)
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="py-8 px-4 text-center text-gray-900 dark:text-gray-400" colspan="6">
                                    <p class="text-base font-medium">{{ __('hotel::modules.roomService.noRoomServiceOrdersFound') }}</p>
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
        {{ $orders->links() }}
    </div>
</div>
