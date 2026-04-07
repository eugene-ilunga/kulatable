<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">
            <div>
                <x-label for="event_name" value="{{ __('hotel::modules.banquet.eventNameLabel') }}" required />
                <x-input id="event_name" class="block mt-1 w-full" type="text" wire:model="event_name" required />
                <x-input-error for="event_name" class="mt-2" />
            </div>

            <div>
                <x-label for="venue_id" value="{{ __('hotel::modules.banquet.venue') }}" required />
                <x-select id="venue_id" class="block mt-1 w-full" wire:model="venue_id" required>
                    <option value="">{{ __('hotel::modules.banquet.selectVenue') }}</option>
                    @foreach($venues as $venue)
                        <option value="{{ $venue->id }}">{{ $venue->name }} ({{ __('hotel::modules.banquet.capacity') }}: {{ $venue->capacity }})</option>
                    @endforeach
                </x-select>
                <x-input-error for="venue_id" class="mt-2" />
            </div>

            <div>
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <x-label for="customer_id" value="{{ __('hotel::modules.banquet.customer') }}" />
                        <x-select id="customer_id" class="block mt-1 w-full" wire:model="customer_id">
                            <option value="">{{ __('hotel::modules.banquet.selectCustomer') }}</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email ?? $customer->phone }})</option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-secondary-button type="button" wire:click="$set('showAddCustomerModal', true)" class="mb-0">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('app.add') }}
                    </x-secondary-button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="start_time" value="{{ __('hotel::modules.banquet.startTime') }}" required />
                    <x-input id="start_time" class="block mt-1 w-full" type="datetime-local" wire:model="start_time" required />
                    <x-input-error for="start_time" class="mt-2" />
                </div>

                <div>
                    <x-label for="end_time" value="{{ __('hotel::modules.banquet.endTime') }}" required />
                    <x-input id="end_time" class="block mt-1 w-full" type="datetime-local" wire:model="end_time" required />
                    <x-input-error for="end_time" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="expected_guests" value="{{ __('hotel::modules.banquet.expectedGuests') }}" required />
                    <x-input id="expected_guests" class="block mt-1 w-full" type="number" wire:model="expected_guests" min="0" required />
                    <x-input-error for="expected_guests" class="mt-2" />
                </div>

                <div>
                    <x-label for="status" value="{{ __('hotel::modules.banquet.status') }}" required />
                    <x-select id="status" class="block mt-1 w-full" wire:model="status" required>
                        @foreach($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-label for="package_amount" value="{{ __('hotel::modules.banquet.packageAmount') }}" required />
                    <x-input id="package_amount" class="block mt-1 w-full" type="number" wire:model="package_amount" step="0.01" min="0" required />
                    <x-input-error for="package_amount" class="mt-2" />
                </div>

                <div>
                    <x-label for="advance_paid" value="{{ __('hotel::modules.banquet.advancePaid') }}" required />
                    <x-input id="advance_paid" class="block mt-1 w-full" type="number" wire:model="advance_paid" step="0.01" min="0" required />
                    <x-input-error for="advance_paid" class="mt-2" />
                </div>
            </div>

            <div>
                <x-label for="description" value="{{ __('hotel::modules.banquet.description') }}" />
                <textarea id="description" wire:model="description" rows="3" class="block w-full px-4 py-3 transition-all duration-200 border-2 border-gray-300 shadow-sm resize-none rounded-xl dark:border-gray-600 focus:ring-2 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>

            <div>
                <x-label for="special_requests" value="{{ __('hotel::modules.banquet.specialRequests') }}" />
                <textarea id="special_requests" wire:model="special_requests" rows="3" class="block w-full px-4 py-3 transition-all duration-200 border-2 border-gray-300 shadow-sm resize-none rounded-xl dark:border-gray-600 focus:ring-2 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
            </div>
        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button type="submit" wire:loading.attr="disabled">{{ __('app.update') }}</x-button>
            <x-button-cancel type="button" wire:click="$dispatch('hideEditEvent')" wire:loading.attr="disabled">{{ __('hotel::modules.banquet.cancel') }}</x-button-cancel>
        </div>
    </form>

    {{-- Add Customer Modal --}}
    <x-dialog-modal wire:model.live="showAddCustomerModal">
        <x-slot name="title">{{ __('modules.customer.addCustomer') }}</x-slot>
        <x-slot name="content">
            @if ($showAddCustomerModal)
                <livewire:forms.add-customer-form />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddCustomerModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>

