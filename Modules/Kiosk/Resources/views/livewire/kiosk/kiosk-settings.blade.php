<div>
    @if ($isEditing)
        <div class="px-4">
            <form wire:submit="saveKiosk">
                @csrf
                <div class="space-y-4">
                    <div>
                        <x-label for="kioskName" value="{{ trans('kiosk::modules.settings.kiosk_name') }}" />
                        <x-input id="kioskName" class="block mt-1 w-full" type="text" wire:model='kioskName' />
                        <x-input-error for="kioskName" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                            <x-checkbox id="isActive" wire:model="isActive" />
                            <label for="isActive" class="text-sm text-gray-700 dark:text-gray-200">{{ trans('kiosk::modules.settings.active') }}</label>
                        </div>

                        <div>
                            <x-label value="{{ trans('kiosk::modules.settings.customer_required_details') }}" class="mb-2" />
                            <div class="space-y-2">
                                <div>
                                    <x-checkbox id="require_name" wire:model="requireName" />
                                    <label for="require_name" class="ml-2 text-sm text-gray-700 dark:text-gray-200">{{ trans('kiosk::modules.settings.name') }}</label>
                                </div>
                                <div>
                                    <x-checkbox id="require_email" wire:model="requireEmail" />
                                    <label for="require_email" class="ml-2 text-sm text-gray-700 dark:text-gray-200">{{ trans('kiosk::modules.settings.email') }}</label>
                                </div>
                                <div>
                                    <x-checkbox id="require_phone" wire:model="requirePhone" />
                                    <label for="require_phone" class="ml-2 text-sm text-gray-700 dark:text-gray-200">{{ trans('kiosk::modules.settings.phone') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
                    <x-button>{{ trans('kiosk::modules.settings.save') }}</x-button>
                    <x-button-cancel wire:click="cancelEdit" wire:loading.attr="disabled">{{ trans('kiosk::modules.settings.cancel') }}</x-button-cancel>
                </div>
            </form>
        </div>
    @else
        <div class="px-4 mb-4">
            <x-button type='button' wire:click="createMode">{{ trans('kiosk::modules.settings.add_kiosk') }}</x-button>
        </div>

        <div class="flex flex-col">
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden shadow">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ trans('kiosk::modules.settings.name') }}</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                                        <div class="flex items-center gap-1">
                                            <span>{{ trans('kiosk::modules.settings.code') }}</span>
                                            <span class="group relative">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                </svg>
                                                <div class="hidden group-hover:block absolute z-50 w-48 px-2 py-1.5 text-xs font-normal normal-case text-white bg-gray-900 rounded shadow-lg top-full left-1/2 transform -translate-x-1/2 mt-1">
                                                    @lang('kiosk::modules.settings.auto_generated_unique_identifier')
                                                    <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1">
                                                        <div class="border-4 border-transparent border-b-gray-900"></div>
                                                    </div>
                                                </div>
                                            </span>
                                        </div>
                                    </th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ trans('kiosk::modules.settings.active') }}</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ trans('kiosk::modules.settings.required_triplet', ['name' => trans('kiosk::modules.settings.name'), 'email' => trans('kiosk::modules.settings.email'), 'phone' => trans('kiosk::modules.settings.phone')]) }}</th>
                                    <th class="py-2.5 px-4 text-xs font-medium text-right text-gray-500 uppercase dark:text-gray-400">{{ trans('kiosk::modules.settings.action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                @foreach ($kiosks as $item)
                                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">{{ $item->name }}</td>
                                        <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">{{ $item->code }}</td>
                                        <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                            <span class="px-2 py-1 text-xs rounded {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $item->is_active ? trans('app.yes') : trans('app.no') }}</span>
                                        </td>
                                        <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                            {{ $item->require_name ? 'Y' : 'N' }} / {{ $item->require_email ? 'Y' : 'N' }} / {{ $item->require_phone ? 'Y' : 'N' }}
                                        </td>
                                        <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                            <a
                                                href="{{ route('kiosk.kiosk', $item->code) }}"
                                                target="_blank"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-skin-base rounded hover:opacity-90 focus:outline-none gap-1">
                                                {{ trans('kiosk::modules.settings.open') }}

                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right w-3 h-3" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
                                                    <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
                                                </svg>
                                            </a>
                                            <x-secondary-button-table wire:click='showEditKiosk({{ $item->id }})'>{{ trans('kiosk::modules.settings.update') }}</x-secondary-button-table>
                                            <x-danger-button-table wire:click="showDeleteKiosk({{ $item->id }})">{{ trans('kiosk::modules.settings.delete') }}</x-danger-button-table>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-confirmation-modal wire:model="confirmDeleteKioskModal">
        <x-slot name="title">{{ trans('kiosk::modules.settings.delete_kiosk_title') }}</x-slot>
        <x-slot name="content">{{ trans('kiosk::modules.settings.delete_kiosk_text') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteKioskModal')" wire:loading.attr="disabled">{{ trans('kiosk::modules.settings.cancel') }}</x-secondary-button>
            @if ($activeKiosk)
                <x-danger-button class="ml-3" wire:click='deleteKiosk' wire:loading.attr="disabled">{{ trans('kiosk::modules.settings.delete') }}</x-danger-button>
            @endif
        </x-slot>
    </x-confirmation-modal>
</div>


