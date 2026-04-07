<div x-data="{ open: false }" 
     x-show="open" 
     x-cloak
     @open-stamp-rule-modal.window="open = true"
     @close-stamp-rule-modal.window="open = false"
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
            <form wire:submit.prevent="saveStampRule">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ $editingStampRuleId ? __('loyalty::app.editStampRule') : __('loyalty::app.addStampRule') }}
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.menuItem') }} <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="stampRuleForm.menu_item_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">{{ __('loyalty::app.selectMenuItem') }}</option>
                                @foreach($menuItems as $item)
                                    <option value="{{ $item['id'] }}">{{ $item['item_name'] }}</option>
                                @endforeach
                            </select>
                            @error('stampRuleForm.menu_item_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.stampsRequired') }} <span class="text-red-500">*</span>
                            </label>
                            <x-input type="number" step="1" wire:model="stampRuleForm.stamps_required" class="block w-full" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('loyalty::app.stampsRequiredDescription') }}</p>
                            @error('stampRuleForm.stamps_required') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.rewardType') }} <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="stampRuleForm.reward_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="free_item">{{ __('loyalty::app.rewardTypeFree_item') }}</option>
                                <option value="discount_percent">{{ __('loyalty::app.rewardTypeDiscount_percent') }}</option>
                                <option value="discount_amount">{{ __('loyalty::app.rewardTypeDiscount_amount') }}</option>
                            </select>
                            @error('stampRuleForm.reward_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        @if($stampRuleForm['reward_type'] === 'free_item')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('loyalty::app.rewardMenuItem') }} <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="stampRuleForm.reward_menu_item_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">{{ __('loyalty::app.selectMenuItem') }}</option>
                                    @foreach($menuItems as $item)
                                        <option value="{{ $item['id'] }}">{{ $item['item_name'] }}</option>
                                    @endforeach
                                </select>
                                @error('stampRuleForm.reward_menu_item_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            
                            @if($stampRuleForm['reward_menu_item_id'] && count($rewardMenuItemVariations) > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('modules.menu.variationName') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="stampRuleForm.reward_menu_item_variation_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="">{{ __('loyalty::app.selectVariation') }}</option>
                                        @foreach($rewardMenuItemVariations as $variation)
                                            <option value="{{ $variation['id'] }}">
                                                {{ $variation['variation_name'] }} 
                                                @if($variation['price'] > 0)
                                                    ({{ currency_format($variation['price'], restaurant()->currency_id) }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('stampRuleForm.reward_menu_item_variation_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        @elseif(in_array($stampRuleForm['reward_type'], ['discount_percent', 'discount_amount']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('loyalty::app.rewardValue') }} <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center">
                                    <x-input type="number" step="0.01" wire:model="stampRuleForm.reward_value" class="block w-full" />
                                    @if($stampRuleForm['reward_type'] === 'discount_percent')
                                        <span class="ml-2 text-sm text-gray-500">%</span>
                                    @else
                                        <span class="ml-2 text-sm text-gray-500">{{ currency() }}</span>
                                    @endif
                                </div>
                                @error('stampRuleForm.reward_value') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('loyalty::app.description') }}
                            </label>
                            <textarea wire:model="stampRuleForm.description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-skin-base focus:ring-skin-base sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <x-checkbox wire:model="stampRuleForm.is_active" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('app.active') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <x-button type="submit" class="w-full sm:w-auto sm:ml-3">
                        {{ __('app.save') }}
                    </x-button>
                    <x-button type="button" @click="open = false" wire:click="$set('editingStampRuleId', null)" class="mt-3 sm:mt-0 w-full sm:w-auto">
                        {{ __('app.cancel') }}
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</div>
