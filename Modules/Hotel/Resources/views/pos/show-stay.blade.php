<div id="hotel-room-modal-ajax" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40" onclick="if (event.target === this) { window.closeHotelRoomModalAjax && window.closeHotelRoomModalAjax(); }">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col" onclick="event.stopPropagation();">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                @lang('hotel::modules.roomService.selectRoom')
            </h3>
            <button type="button"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    onclick="window.closeHotelRoomModalAjax && window.closeHotelRoomModalAjax()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input id="hotel-room-search-ajax"
                       type="text"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                       placeholder="{{ __('hotel::modules.roomService.searchByRoomOrStay') }}">
            </div>
        </div>
        <div class="px-6 py-3 flex-1 overflow-y-auto">
            <div id="hotel-room-stay-list-ajax" class="grid grid-cols-2 gap-3 text-sm text-gray-800 dark:text-gray-100">
                {{-- Filled via AJAX --}}
            </div>
            <div id="hotel-room-stay-empty-ajax" class="hidden text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                @lang('messages.noRecordFound')
            </div>
        </div>
        <div class="px-6 py-3 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
            <button type="button"
                    class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                    onclick="window.closeHotelRoomModalAjax && window.closeHotelRoomModalAjax()">
                @lang('app.cancel')
            </button>
        </div>
    </div>
</div>
