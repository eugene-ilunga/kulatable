<div>
    <div class="p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.settings.bookingTaxesDescription') }}</p>
        <div class="flex justify-between items-center mb-4">
            <x-button type="button" wire:click="showAdd">
                <svg class="w-4 h-4 mr-1 inline-flex" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ __('hotel::modules.settings.addTax') }}
            </x-button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.settings.taxName') }}</th>
                        <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.settings.taxRate') }}</th>
                        <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.settings.status') }}</th>
                        <th class="py-2.5 px-4 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400">{{ __('app.action') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    @forelse ($taxes as $tax)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="py-2.5 px-4 text-sm font-medium text-gray-900 dark:text-white">{{ $tax->name }}</td>
                        <td class="py-2.5 px-4 text-sm text-gray-600 dark:text-gray-300">{{ $tax->rate }}%</td>
                        <td class="py-2.5 px-4">
                            @if($tax->is_active)
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ __('app.active') }}</span>
                            @else
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">{{ __('app.inactive') }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-right space-x-2">
                            <x-secondary-button wire:click="showEdit({{ $tax->id }})" class="!py-1.5 !text-xs">
                                {{ __('app.update') }}
                            </x-secondary-button>
                            <x-danger-button wire:click="showDelete({{ $tax->id }})" class="!py-1.5 !text-xs">
                                {{ __('app.delete') }}
                            </x-danger-button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td class="py-6 px-4 text-center text-gray-500 dark:text-gray-400" colspan="4">{{ __('hotel::modules.settings.noTaxesFound') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <x-right-modal wire:model.live="showAddModal">
        <x-slot name="title">{{ __('hotel::modules.settings.addTax') }}</x-slot>
        <x-slot name="content">
            @if($showAddModal)
            <livewire:hotel::settings.hotel-tax-form wire:key="hotel-tax-add" />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-right-modal wire:model.live="showEditModal">
        <x-slot name="title">{{ __('hotel::modules.settings.editTax') }}</x-slot>
        <x-slot name="content">
            @if($showEditModal && $activeTax)
            <livewire:hotel::settings.hotel-tax-form :tax-id="$activeTax->id" wire:key="hotel-tax-edit-{{ $activeTax->id }}" />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditModal', false)">{{ __('app.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteModal">
        <x-slot name="title">{{ __('hotel::modules.settings.deleteTax') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.settings.deleteTaxMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('confirmDeleteModal', false)">{{ __('app.cancel') }}</x-secondary-button>
            @if($activeTax)
            <x-danger-button class="ml-3" wire:click="deleteTax({{ $activeTax->id }})">{{ __('app.delete') }}</x-danger-button>
            @endif
        </x-slot>
    </x-confirmation-modal>
</div>
