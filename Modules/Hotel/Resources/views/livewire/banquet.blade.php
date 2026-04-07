<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.banquet.banquetEvents') }}</h1>
            </div>
        </div>
    </div>

    {{-- Venues section --}}
    <div class="p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('hotel::modules.banquet.venues') }}</h2>
            <div class="items-center justify-between block sm:flex gap-2">
                <div class="flex flex-col sm:flex-row items-center gap-2 flex-1">
                    <x-input type="text" wire:model.live.debounce.500ms="venueSearch" class="block w-full sm:w-64" placeholder="{{ __('hotel::modules.banquet.searchVenuePlaceholder') }}" />
                </div>
                @if(user_can('Create Hotel Venue'))
                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-button type='button' wire:click="$toggle('showAddVenueModal')">{{ __('hotel::modules.banquet.addVenue') }}</x-button>
                </div>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.name') }}</th>
                        <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.capacity') }}</th>
                        <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.baseRate') }}</th>
                        <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.status') }}</th>
                        <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">{{ __('hotel::modules.banquet.action') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse ($venuesList as $venue)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="py-2.5 px-4 text-sm font-medium text-gray-900 dark:text-white">{{ $venue->name }}</td>
                        <td class="py-2.5 px-4 text-sm text-gray-600 dark:text-gray-300">{{ $venue->capacity ?? __('app.notAvailable') }}</td>
                        <td class="py-2.5 px-4 text-sm text-gray-600 dark:text-gray-300">{{ $venue->base_rate !== null ? currency_format($venue->base_rate) : __('app.notAvailable') }}</td>
                        <td class="py-2.5 px-4 text-sm">
                            @if($venue->is_active)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('hotel::modules.banquet.active') }}</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('hotel::modules.banquet.inactive') }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                            @if(user_can('Update Hotel Venue'))
                            <x-secondary-button-table wire:click='showEditVenue({{ $venue->id }})'>
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg>
                                {{ __('hotel::modules.banquet.editVenue') }}
                            </x-secondary-button-table>
                            @endif

                            @if(user_can('Delete Hotel Venue'))
                            <x-danger-button-table wire:click="showDeleteVenue({{ $venue->id }})">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            </x-danger-button-table>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td class="py-6 px-4 text-center text-gray-500 dark:text-gray-400" colspan="5">{{ __('messages.noRecordFound') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Events section --}}
    <div class="p-4 bg-white dark:bg-gray-800 dark:border-gray-700">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('hotel::modules.banquet.events') }}</h2>
            <div class="items-center justify-between block sm:flex gap-2">
                <div class="flex flex-col sm:flex-row items-center gap-2 flex-1">
                    <x-input type="text" wire:model.live.debounce.500ms="search" class="block w-full sm:w-96" placeholder="{{ __('hotel::modules.banquet.searchPlaceholder') }}" />
                    <x-select wire:model.live="filterVenue" class="block w-full sm:w-48">
                        <option value="">{{ __('hotel::modules.banquet.allVenues') }}</option>
                        @foreach($venues as $venue)
                            <option value="{{ $venue->id }}">{{ $venue->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model.live="filterStatus" class="block w-full sm:w-48">
                        <option value="">{{ __('hotel::modules.banquet.allStatuses') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-select>
                </div>
                @if(user_can('Create Hotel Event'))
                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-button type='button' wire:click="$toggle('showAddEventModal')">{{ __('hotel::modules.banquet.newEvent') }}</x-button>
                </div>
                @endif
            </div>
        </div>
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.eventNumber') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.eventName') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.venue') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.customer') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.dateTime') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.guests') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.banquet.status') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">{{ __('hotel::modules.banquet.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($events as $event)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white font-semibold">
                                    {{ $event->event_number }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $event->event_name }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $event->venue?->name ?? __('app.notAvailable') }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $event->customer?->name ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $event->start_time->format('M d, Y g:i A') }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $event->expected_guests }}
                                </td>
                                <td class="py-2.5 px-4 text-base whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'tentative' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        ];
                                        $color = $statusColors[$event->status->value] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $color }}">
                                        {{ $event->status->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if(user_can('Update Hotel Event'))
                                    <x-secondary-button-table wire:click='showViewEvent({{ $event->id }})'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path></svg>
                                        {{ __('hotel::modules.banquet.view') }}
                                    </x-secondary-button-table>
                                    @endif

                                    @if($event->status->value === 'tentative' && user_can('Update Hotel Event'))
                                    <x-secondary-button-table wire:click='showEditEvent({{ $event->id }})'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg>
                                        {{ __('hotel::modules.banquet.editEvent') }}
                                    </x-secondary-button-table>
                                    @endif

                                    @if(user_can('Delete Hotel Event'))
                                    <x-danger-button-table wire:click="showDeleteEvent({{ $event->id }})">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    </x-danger-button-table>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="py-6 px-4 text-center text-gray-500 dark:text-gray-400" colspan="8">{{ __('messages.noRecordFound') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4">
        {{ $events->links() }}
    </div>
    </div>

    <x-right-modal wire:model.live="showAddVenueModal">
        <x-slot name="title">{{ __('hotel::modules.banquet.addVenue') }}</x-slot>
        <x-slot name="content">
            @if ($showAddVenueModal)
                <livewire:hotel::forms.add-venue />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddVenueModal', false)">{{ __('hotel::modules.banquet.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showAddEventModal" maxWidth="3xl">
        <x-slot name="title">{{ __('hotel::modules.banquet.newEvent') }}</x-slot>
        <x-slot name="content">
            @if ($showAddEventModal)
                <livewire:hotel::forms.add-event />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddEventModal', false)">{{ __('hotel::modules.banquet.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    @if ($activeVenue)
    <x-right-modal wire:model.live="showEditVenueModal">
        <x-slot name="title">{{ __('hotel::modules.banquet.editVenue') }}</x-slot>
        <x-slot name="content">
            <livewire:hotel::forms.edit-venue :activeVenue="$activeVenue" :key="str()->random(50)" />
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditVenueModal', false)">{{ __('hotel::modules.banquet.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteVenueModal">
        <x-slot name="title">{{ __('hotel::modules.banquet.deleteVenue') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.banquet.deleteVenueMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteVenueModal')">{{ __('hotel::modules.banquet.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click='deleteVenue({{ $activeVenue->id }})'>{{ __('hotel::modules.banquet.delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif

    <x-right-modal wire:model.live="showViewEventModal" maxWidth="2xl">
        <x-slot name="title">{{ __('hotel::modules.banquet.eventDetails') }}: {{ $activeEvent->event_number ?? '' }}</x-slot>
        <x-slot name="content">
            @if ($showViewEventModal && $activeEvent)
                <div class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.eventName') }}</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $activeEvent->event_name }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.venue') }}</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $activeEvent->venue?->name ?? __('app.notAvailable') }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.start') }}</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $activeEvent->start_time?->format('M d, Y g:i A') ?? __('app.notAvailable') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.end') }}</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $activeEvent->end_time?->format('M d, Y g:i A') ?? __('app.notAvailable') }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.expectedGuestsLabel') }}</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $activeEvent->expected_guests ?? __('app.notAvailable') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.status') }}</span>
                            <p>
                                @php
                                    $colors = ['tentative' => 'bg-yellow-100 text-yellow-800', 'confirmed' => 'bg-blue-100 text-blue-800', 'cancelled' => 'bg-red-100 text-red-800', 'completed' => 'bg-green-100 text-green-800'];
                                    $c = $colors[$activeEvent->status->value] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $c }}">{{ $activeEvent->status->label() }}</span>
                            </p>
                        </div>
                    </div>
                    @if($activeEvent->customer)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.customer') }}</span>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $activeEvent->customer->name }}</p>
                    </div>
                    @endif
                    <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-200 dark:border-gray-600">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.packageAmountLabel') }}</span>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $activeEvent->package_amount !== null ? currency_format($activeEvent->package_amount) : __('app.notAvailable') }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('hotel::modules.banquet.advancePaidLabel') }}</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $activeEvent->advance_paid ? currency_format($activeEvent->advance_paid) : __('app.notAvailable') }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="closeViewEventModal">{{ __('hotel::modules.banquet.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    @if ($activeEvent)
    <x-right-modal wire:model.live="showEditEventModal" maxWidth="3xl">
        <x-slot name="title">{{ __('hotel::modules.banquet.editEvent') }}</x-slot>
        <x-slot name="content">
            <livewire:hotel::forms.edit-event :activeEvent="$activeEvent" :key="str()->random(50)" />
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditEventModal', false)">{{ __('hotel::modules.banquet.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>
    @endif

    @if ($activeEvent)
    <x-confirmation-modal wire:model="confirmDeleteEventModal">
        <x-slot name="title">{{ __('hotel::modules.banquet.deleteEvent') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.banquet.deleteEventMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteEventModal')">{{ __('hotel::modules.banquet.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click='deleteEvent({{ $activeEvent->id }})'>{{ __('hotel::modules.banquet.delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif
</div>
