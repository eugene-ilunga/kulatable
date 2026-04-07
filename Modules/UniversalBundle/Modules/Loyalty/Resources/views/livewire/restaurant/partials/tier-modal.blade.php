<div x-data="{ open: false }" 
     x-show="open" 
     x-cloak
     @open-tier-modal.window="open = true"
     @close-tier-modal.window="open = false"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="open" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
             @click="open = false"></div>

        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form wire:submit.prevent="saveTier">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ $editingTierId ? __('loyalty::app.editTier') : __('loyalty::app.addTier') }}
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.tierName') }} <span class="text-red-500">*</span>
                            </label>
                            <x-input type="text" wire:model="tierForm.name" class="block w-full" />
                            @error('tierForm.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.tierColor') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="color" wire:model="tierForm.color" class="h-10 w-20 rounded border border-gray-300">
                                <x-input type="text" wire:model="tierForm.color" class="flex-1" />
                            </div>
                            @error('tierForm.color') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('loyalty::app.minPoints') }} <span class="text-red-500">*</span>
                                </label>
                                <x-input type="number" step="1" wire:model="tierForm.min_points" class="block w-full" />
                                @error('tierForm.min_points') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('loyalty::app.maxTierPoints') }}
                                </label>
                                <x-input type="number" step="1" wire:model="tierForm.max_points" class="block w-full" />
                                <p class="mt-1 text-xs text-gray-500">{{ __('loyalty::app.leaveEmptyForUnlimited') }}</p>
                                @error('tierForm.max_points') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('loyalty::app.earningMultiplier') }} <span class="text-red-500">*</span>
                                </label>
                                <x-input type="number" step="0.01" wire:model="tierForm.earning_multiplier" class="block w-full" />
                                <p class="mt-1 text-xs text-gray-500">{{ __('loyalty::app.earningMultiplierDescription') }}</p>
                                @error('tierForm.earning_multiplier') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('loyalty::app.redemptionMultiplier') }} <span class="text-red-500">*</span>
                                </label>
                                <x-input type="number" step="0.01" wire:model="tierForm.redemption_multiplier" class="block w-full" />
                                <p class="mt-1 text-xs text-gray-500">{{ __('loyalty::app.redemptionMultiplierDescription') }}</p>
                                @error('tierForm.redemption_multiplier') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.description') }}
                            </label>
                            <textarea wire:model="tierForm.description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <x-checkbox wire:model="tierForm.is_active" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('app.active') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <x-button type="submit" class="w-full sm:w-auto sm:ml-3">
                        {{ __('app.save') }}
                    </x-button>
                    <x-button type="button" @click="open = false" wire:click="$set('editingTierId', null)" class="mt-3 sm:mt-0 w-full sm:w-auto">
                        {{ __('app.cancel') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
