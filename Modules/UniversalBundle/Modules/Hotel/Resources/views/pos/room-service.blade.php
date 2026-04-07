@if ($orderType == 'room_service' || $orderTypeSlug == 'room_service')
    <div class="flex items-center justify-between gap-3 mb-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <div class="flex-1 min-w-0">
                        <div id="ajax-room-stay-summary-room"
                             class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            @lang('hotel::modules.roomService.selectRoom')
                        </div>
                        <div id="ajax-room-stay-summary-stay"
                             class="text-xs text-gray-500 dark:text-gray-400 truncate hidden">
                        </div>
                    </div>
                    <button type="button"
                            onclick="window.openHotelRoomModalAjax && window.openHotelRoomModalAjax()"
                            class="inline-flex items-center flex-shrink-0 h-7 px-2 text-xs border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="flex-shrink-0">
            <select class="text-xs h-8 min-w-[140px] rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-100"
                    onchange="window.setRoomServiceBillTo && window.setRoomServiceBillTo(this.value)">
                <option value="POST_TO_ROOM">@lang('hotel::modules.roomService.postToRoom')</option>
                <option value="PAY_NOW">@lang('hotel::modules.roomService.payNow')</option>
            </select>
        </div>
    </div>
@endif
