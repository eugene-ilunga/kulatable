{{--
    Reusable additional-guest accordion card.
    Variables:
        $gIndex      – integer index in the additionalGuests array
        $guest       – array with guest fields
        $badgeNumber – integer to display in the circular badge
        $canRemove   – bool: whether to show the remove button
--}}
<div x-data="{ open: true }" class="rounded-xl border border-blue-200 dark:border-blue-800/60 overflow-hidden">
    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 py-3 bg-blue-50/50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800/40">
        <button type="button" @click="open = !open" class="flex items-center gap-3 flex-1 min-w-0 text-left">
            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0 transition-transform duration-200"
                :class="open ? 'rotate-90' : ''"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold shrink-0">{{ $badgeNumber }}</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate leading-tight">
                    @if(!empty($guest['first_name']))
                        {{ trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? '')) }}
                    @else
                        {{ __('hotel::modules.checkIn.guestNumber', ['number' => $badgeNumber]) }}
                    @endif
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate" x-show="!open">
                    {{ $guest['phone'] ?? '—' }}
                    @if(!empty($guest['id_type'])) &nbsp;·&nbsp; {{ $guest['id_type'] }} @endif
                </p>
            </div>
        </button>
        @if($canRemove)
        <button type="button" wire:click="removeGuest({{ $gIndex }})"
            class="shrink-0 inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            {{ __('hotel::modules.checkIn.removeGuest') }}
        </button>
        @endif
    </div>
    {{-- Form body --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="p-4 bg-white dark:bg-gray-800 grid grid-cols-2 gap-x-3 gap-y-3">

        <div>
            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('hotel::modules.guest.firstName') }} <span class="text-red-500">*</span></x-label>
            <x-input wire:model="additionalGuests.{{ $gIndex }}.first_name" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.firstName') }}" />
            @error("additionalGuests.{$gIndex}.first_name")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('hotel::modules.guest.lastName') }}</x-label>
            <x-input wire:model="additionalGuests.{{ $gIndex }}.last_name" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.lastName') }}" />
        </div>
        <div>
            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('hotel::modules.guest.email') }}</x-label>
            <x-input wire:model="additionalGuests.{{ $gIndex }}.email" type="email" class="block w-full" placeholder="email@example.com" />
            @error("additionalGuests.{$gIndex}.email")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('hotel::modules.guest.phone') }} <span class="text-red-500">*</span></x-label>
            <x-input wire:model="additionalGuests.{{ $gIndex }}.phone" type="text" class="block w-full" placeholder="+91 00000 00000" />
            @error("additionalGuests.{$gIndex}.phone")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('hotel::modules.guest.idType') }} <span class="text-red-500">*</span></x-label>
            <x-select wire:model="additionalGuests.{{ $gIndex }}.id_type" class="block w-full">
                <option value="">{{ __('hotel::modules.guest.selectIdType') }}</option>
                <option value="passport">{{ __('hotel::modules.guest.passport') }}</option>
                <option value="aadhaar">{{ __('hotel::modules.guest.aadhaar') }}</option>
                <option value="driving_license">{{ __('hotel::modules.guest.drivingLicense') }}</option>
                <option value="national_id">{{ __('hotel::modules.guest.nationalId') }}</option>
                <option value="other">{{ __('hotel::modules.guest.other') }}</option>
            </x-select>
            @error("additionalGuests.{$gIndex}.id_type")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">{{ __('hotel::modules.guest.idNumber') }} <span class="text-red-500">*</span></x-label>
            <x-input wire:model="additionalGuests.{{ $gIndex }}.id_number" type="text" class="block w-full" placeholder="{{ __('hotel::modules.guest.idNumber') }}" />
            @error("additionalGuests.{$gIndex}.id_number")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

    </div>
</div>
