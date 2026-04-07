<div>
    <div class="space-y-4">
        <div>
            <x-label value="{{ __('hotel::modules.posHotelContext.selectStayRoom') }} *" />
            <x-select wire:model="selectedStayId" class="block w-full">
                <option value="">{{ __('hotel::modules.posHotelContext.selectStay') }}</option>
                @foreach($stays as $stay)
                    <option value="{{ $stay->id }}">
                        {{ __('hotel::modules.folio.room') }} {{ $stay->room->room_number }} - {{ $stay->stayGuests->first()?->guest->full_name ?? __('app.notAvailable') }} ({{ $stay->stay_number }})
                    </option>
                @endforeach
            </x-select>
        </div>

        <div>
            <x-label value="{{ __('hotel::modules.posHotelContext.paymentOption') }} *" />
            <x-select wire:model="billTo" class="block w-full">
                <option value="PAY_NOW">{{ __('hotel::modules.posHotelContext.payNow') }}</option>
                <option value="POST_TO_ROOM">{{ __('hotel::modules.posHotelContext.postToRoom') }}</option>
            </x-select>
            @if($billTo === 'POST_TO_ROOM')
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('hotel::modules.posHotelContext.orderChargedToFolio') }}
            </p>
            @endif
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <x-secondary-button wire:click="$dispatch('closeModal')">{{ __('hotel::modules.folio.cancel') }}</x-secondary-button>
            <x-button wire:click="confirmSelection" wire:loading.attr="disabled" :disabled="!$selectedStayId">{{ __('hotel::modules.posHotelContext.confirm') }}</x-button>
        </div>
    </div>
</div>
