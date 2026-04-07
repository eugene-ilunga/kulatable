<div>
    @if($showModal)
        <x-dialog-modal wire:model.live="showModal" maxWidth="2xl">
        <x-slot name="title">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-house-door" viewBox="0 0 16 16">
                    <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4z"/>
                </svg>
                {{ __('hotel::modules.posHotelContext.selectHotelRoom') }}
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                @if(count($availableStays) > 0)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('hotel::modules.posHotelContext.selectRoom') }}
                        </label>
                        <div class="max-h-96 overflow-y-auto space-y-2">
                            @foreach($availableStays as $stay)
                                <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer {{ $selectedStayId == $stay->id ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '' }}">
                                    <input 
                                        type="radio" 
                                        wire:model.live="selectedStayId" 
                                        value="{{ $stay->id }}"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    >
                                    <div class="ml-3 flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ __('hotel::modules.folio.room') }} {{ $stay->room->room_number ?? __('app.notAvailable') }}
                                            @if($stay->room->roomType)
                                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                    ({{ $stay->room->roomType->name }})
                                                </span>
                                            @endif
                                        </div>
                                        @if($stay->stayGuests->isNotEmpty())
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ __('hotel::modules.posHotelContext.guest') }}: {{ $stay->stayGuests->first()->guest->full_name ?? __('app.notAvailable') }}
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @if($selectedStayId)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('hotel::modules.posHotelContext.billingOption') }}
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer {{ $billTo == 'PAY_NOW' ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700' : '' }}">
                                    <input 
                                        type="radio" 
                                        wire:model.live="billTo" 
                                        value="PAY_NOW"
                                        class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    >
                                    <div class="ml-3">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.posHotelContext.payNow') }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('hotel::modules.posHotelContext.customerPaysImmediately') }}</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer {{ $billTo == 'POST_TO_ROOM' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : '' }}">
                                    <input 
                                        type="radio" 
                                        wire:model.live="billTo" 
                                        value="POST_TO_ROOM"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    >
                                    <div class="ml-3">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ __('hotel::modules.posHotelContext.postToRoom') }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('hotel::modules.posHotelContext.chargeToRoomFolio') }}</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('hotel::modules.posHotelContext.noCheckedInRoomsAvailable') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('hotel::modules.posHotelContext.noCheckedInRoomsMessage') }}
                        </p>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end gap-2 w-full">
                <x-button-cancel wire:click="closeModal" wire:loading.attr="disabled">
                    @lang('app.cancel')
                </x-button-cancel>
                @if($selectedStayId)
                    <x-button wire:click="selectStay" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700">
                        <span wire:loading.remove wire:target="selectStay">
                            {{ __('hotel::modules.posHotelContext.selectRoomButton') }}
                        </span>
                        <span wire:loading wire:target="selectStay" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('hotel::modules.posHotelContext.selecting') }}
                        </span>
                    </x-button>
                @endif
            </div>
        </x-slot>
        </x-dialog-modal>
    @endif
</div>
