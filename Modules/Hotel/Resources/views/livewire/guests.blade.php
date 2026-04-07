<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.guest.guests') }}</h1>
            </div>

            <div class="items-center justify-between block sm:flex">
                <div class="lg:flex items-center mb-4 sm:mb-0">
                    <form class="ltr:pr-3 rtl:pl-3" action="#" method="GET">
                        <label for="guests-search" class="sr-only">{{ __('hotel::modules.guest.searchPlaceholder') }}</label>
                        <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                            <x-input id="guests-search" class="block mt-1 w-full" type="text"
                                placeholder="{{ __('hotel::modules.guest.searchPlaceholder') }}"
                                wire:model.live.debounce.500ms="search" />
                        </div>
                    </form>
                </div>

                @if(user_can('Create Hotel Guest'))
                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-button type='button' wire:click="$toggle('showAddGuestModal')">{{ __('hotel::modules.guest.addGuest') }}</x-button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.guest.name') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.guest.email') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.guest.phone') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.guest.idNumber') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">{{ __('hotel::modules.guest.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($guests as $guest)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 whitespace-nowrap">
                                    @php
                                        $gInitials = collect(explode(' ', $guest->full_name))
                                            ->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
                                    @endphp
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold shrink-0">
                                            {{ $gInitials }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate max-w-[160px]">
                                            {{ $guest->full_name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $guest->email ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $guest->phone ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    @if($guest->id_number)
                                        <div class="flex items-center gap-2">
                                            <span>{{ $guest->id_number }}</span>
                                            @if($guest->id_type)
                                                @php
                                                    $idTypeLabels = [
                                                        'passport' => __('hotel::modules.guest.passport'),
                                                        'aadhaar' => __('hotel::modules.guest.aadhaar'),
                                                        'driving_license' => __('hotel::modules.guest.drivingLicense'),
                                                        'national_id' => __('hotel::modules.guest.nationalId'),
                                                        'other' => __('hotel::modules.guest.other'),
                                                    ];
                                                    $idTypeLabel = $idTypeLabels[$guest->id_type] ?? ucfirst(str_replace('_', ' ', $guest->id_type));
                                                @endphp
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ $idTypeLabel }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if(user_can('Show Hotel Guests'))
                                    <x-secondary-button-table wire:click="showViewGuest({{ $guest->id }})" title="{{ __('hotel::modules.guest.viewGuest') }}">
                                        <svg class="w-4 h-4 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        @lang('hotel::modules.reservation.view')
                                    </x-secondary-button-table>
                                    @endif
                                    @if(user_can('Update Hotel Guest'))
                                    <x-secondary-button-table wire:click='showEditGuest({{ $guest->id }})'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg>
                                        @lang('app.update')
                                    </x-secondary-button-table>
                                    @endif

                                    @if(user_can('Delete Hotel Guest'))
                                    <x-danger-button-table wire:click="showDeleteGuest({{ $guest->id }})">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    </x-danger-button-table>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="py-8 px-4 text-center text-gray-900 dark:text-gray-400" colspan="5">
                                    <p class="text-base font-medium">{{ __('hotel::modules.guest.noGuestsFound') }}</p>
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
        {{ $guests->links() }}
    </div>

    <x-right-modal wire:model.live="showViewGuestModal" maxWidth="3xl">
        <x-slot name="title">
            <span class="inline-flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </span>
                {{ __('hotel::modules.guest.viewGuest') }}
            </span>
        </x-slot>
        <x-slot name="content">
            @if($viewGuest)
                @include('hotel::livewire.partials.guest-view-content', ['guest' => $viewGuest])
            @elseif($viewGuestId)
                <div class="px-1 py-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.guest.guestNotFound') }}</p>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('showViewGuestModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showAddGuestModal">
        <x-slot name="title">{{ __('hotel::modules.guest.addGuest') }}</x-slot>
        <x-slot name="content">
            @if ($showAddGuestModal)
                <livewire:hotel::forms.add-guest />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddGuestModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    @if ($activeGuest)
    <x-right-modal wire:model.live="showEditGuestModal">
        <x-slot name="title">{{ __('hotel::modules.guest.editGuest') }}</x-slot>
        <x-slot name="content">
            <livewire:hotel::forms.edit-guest :activeGuest="$activeGuest" :key="str()->random(50)" />
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditGuestModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteGuestModal">
        <x-slot name="title">{{ __('hotel::modules.guest.deleteGuest') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.guest.deleteGuestMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteGuestModal')">{{ __('app.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click='deleteGuest({{ $activeGuest->id }})'>{{ __('app.delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif
</div>
