<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.ratePlan.ratePlans') }}</h1>
            </div>

            <div class="items-center justify-between block sm:flex">
                <div class="lg:flex items-center mb-4 sm:mb-0">
                    <form class="sm:pr-3" action="#" method="GET">
                        <label for="rate-plans-search" class="sr-only">{{ __('hotel::modules.ratePlan.searchPlaceholder') }}</label>
                        <div class="relative w-48 mt-1 sm:w-64 xl:w-96">
                            <x-input id="rate-plans-search" class="block mt-1 w-full" type="text" placeholder="{{ __('hotel::modules.ratePlan.searchPlaceholder') }}" wire:model.live.debounce.500ms="search" />
                        </div>
                    </form>
                </div>

                <div class="lg:inline-flex items-center gap-4">
                    @if(user_can('Create Hotel Rate Plan'))
                    <x-button type='button' wire:click="$toggle('showAddRatePlanModal')">{{ __('hotel::modules.ratePlan.addRatePlan') }}</x-button>
                    @endif
                </div>
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
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.ratePlan.name') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.ratePlan.planType') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.ratePlan.cancellation') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.ratePlan.status') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">{{ __('hotel::modules.ratePlan.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($ratePlans as $plan)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $plan->name }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $plan->type->label() }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    @if($plan->cancellation_hours)
                                        {{ __('hotel::modules.ratePlan.hoursBefore', ['hours' => $plan->cancellation_hours, 'percent' => $plan->cancellation_charge_percent]) }}
                                    @else
                                        {{ __('hotel::modules.ratePlan.noFreeCancellation') }}
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 text-base whitespace-nowrap">
                                    @if($plan->is_active)
                                        <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">{{ __('hotel::modules.ratePlan.active') }}</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">{{ __('hotel::modules.ratePlan.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if(user_can('Update Hotel Rate Plan'))
                                    <x-secondary-button-table wire:click='showEditRatePlan({{ $plan->id }})'>
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg>
                                        {{ __('hotel::modules.ratePlan.update') }}
                                    </x-secondary-button-table>
                                    @endif

                                    @if(user_can('Delete Hotel Rate Plan'))
                                    <x-danger-button-table wire:click="showDeleteRatePlan({{ $plan->id }})">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    </x-danger-button-table>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="py-8 px-4 text-center text-gray-900 dark:text-gray-400" colspan="5">
                                    <p class="text-base font-medium">{{ __('hotel::modules.ratePlan.noRatePlansAdded') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-right-modal wire:model.live="showAddRatePlanModal">
        <x-slot name="title">{{ __('hotel::modules.ratePlan.addRatePlan') }}</x-slot>
        <x-slot name="content">
            @if ($showAddRatePlanModal)
                <livewire:hotel::forms.add-rate-plan />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddRatePlanModal', false)">{{ __('hotel::modules.ratePlan.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    @if ($activeRatePlan)
    <x-right-modal wire:model.live="showEditRatePlanModal">
        <x-slot name="title">{{ __('hotel::modules.ratePlan.editRatePlan') }}</x-slot>
        <x-slot name="content">
            <livewire:hotel::forms.edit-rate-plan :activeRatePlan="$activeRatePlan" :key="str()->random(50)" />
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditRatePlanModal', false)">{{ __('hotel::modules.ratePlan.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteRatePlanModal">
        <x-slot name="title">{{ __('hotel::modules.ratePlan.deleteRatePlan') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.ratePlan.deleteRatePlanMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteRatePlanModal')">{{ __('hotel::modules.ratePlan.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click='deleteRatePlan({{ $activeRatePlan->id }})'>{{ __('app.delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>
    @endif
</div>
