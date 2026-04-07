<div class="p-4 mx-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
    <h3 class="mb-4 text-xl font-semibold dark:text-white">{{ __('loyalty::app.loyaltyProgramSettings') }}</h3>
    <x-help-text class="mb-6">{{ __('loyalty::app.loyaltyProgramDescription') }}</x-help-text>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex -mb-px space-x-8" aria-label="Tabs">
            @if($enable_points)
            <button wire:click="switchTab('tiers')" 
                    type="button"
                    class="@if($activeTab === 'tiers') border-skin-base text-skin-base @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('loyalty::app.tiers') }}
            </button>
            @endif
            @if($enable_points)
            <button wire:click="switchTab('points')" 
                    type="button"
                    class="@if($activeTab === 'points') border-skin-base text-skin-base @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('loyalty::app.points') }}
            </button>
            @endif
            @if($enable_stamps)
            <button wire:click="switchTab('stamps')" 
                    type="button"
                    class="@if($activeTab === 'stamps') border-skin-base text-skin-base @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                {{ __('loyalty::app.stamps') }}
            </button>
            @endif
        </nav>
    </div>

    <!-- Tiers Tab -->
    @if($activeTab === 'tiers')
        <div>
            <div class="mb-4 flex justify-between items-center">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.manageTiers') }}</h4>
                <x-button wire:click="openTierModal()" type="button">
                    {{ __('loyalty::app.addTier') }}
                </x-button>
            </div>
            
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.tiersDescription') }}</p>
            
            @if(count($tiers) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.tierName') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.minPoints') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.maxTierPoints') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.earningMultiplier') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.redemptionMultiplier') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($tiers as $tier)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-4 h-4 rounded-full mr-2" style="background-color: {{ $tier['color'] }}"></div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $tier['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ number_format($tier['min_points']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $tier['max_points'] ? number_format($tier['max_points']) : __('loyalty::app.unlimited') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ number_format($tier['earning_multiplier'], 2) }}x</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ number_format($tier['redemption_multiplier'], 2) }}x</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($tier['is_active'])
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.active') }}</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button wire:click="openTierModal({{ $tier['id'] }})" class="text-skin-base hover:text-skin-base mr-3">{{ __('app.edit') }}</button>
                                        <button wire:click="deleteTier({{ $tier['id'] }})" 
                                                wire:confirm="{{ __('loyalty::app.confirmDeleteTier') }}"
                                                class="text-red-600 hover:text-red-900">{{ __('app.delete') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('loyalty::app.noTiersFound') }}</p>
                </div>
            @endif
        </div>
    @endif

    <!-- Points Tab -->
    @if($activeTab === 'points')
        <form wire:submit.prevent="save">
            <!-- Enable/Disable -->
            <div class="mb-6">
                <label class="flex items-center">
                    <x-checkbox name="enabled" id="enabled" wire:model="enabled" />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableLoyaltyProgram') }}</span>
                </label>
            </div>

            <!-- Loyalty Type Selection -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.loyaltyType') }}</h4>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.loyaltyTypeDescription') }}</p>
                
                <div class="space-y-3">
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $loyalty_type === 'points' ? 'border-skin-base bg-skin-base/10' : 'border-gray-300 dark:border-gray-600' }}">
                        <input type="radio" wire:model.live="loyalty_type" value="points" class="text-skin-base focus:ring-skin-base" />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('loyalty::app.pointsOnly') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.pointsOnlyDescription') }}</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $loyalty_type === 'stamps' ? 'border-skin-base bg-skin-base/10' : 'border-gray-300 dark:border-gray-600' }}">
                        <input type="radio" wire:model.live="loyalty_type" value="stamps" class="text-skin-base focus:ring-skin-base" />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('loyalty::app.stampsOnly') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.stampsOnlyDescription') }}</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $loyalty_type === 'both' ? 'border-skin-base bg-skin-base/10' : 'border-gray-300 dark:border-gray-600' }}">
                        <input type="radio" wire:model.live="loyalty_type" value="both" class="text-skin-base focus:ring-skin-base" />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('loyalty::app.bothPointsAndStamps') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.bothPointsAndStampsDescription') }}</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Platform Enable/Disable for Points -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.enableForPlatforms') }} ({{ __('loyalty::app.points') }})</h4>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.enableForPlatformsDescription') }}</p>
                
                <div class="flex justify-between items-center">
                    <label class="flex items-center">
                        <x-checkbox name="enable_points_for_pos" id="enable_points_for_pos" wire:model="enable_points_for_pos" />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableForPos') }}</span>
                    </label>
                    
                    <label class="flex items-center">
                        <x-checkbox name="enable_points_for_customer_site" id="enable_points_for_customer_site" wire:model="enable_points_for_customer_site" />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableForCustomerSite') }}</span>
                    </label>
                    
                    @if($isKioskModuleEnabled)
                    <label class="flex items-center">
                        <x-checkbox name="enable_points_for_kiosk" id="enable_points_for_kiosk" wire:model="enable_points_for_kiosk" />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableForKiosk') }}</span>
                    </label>
                    @endif
                </div>
            </div>

            <!-- Earning Rules -->
            @if($enable_points)
            <div class="mb-6">
                <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.earningRules') }}</h4>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.earningRulesDescription') }}</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('loyalty::app.spendAmount', ['currency' => currency()]) }}
                        </label>
                        <x-input type="number" step="0.01" wire:model="earn_rate_rupees" class="block w-full" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.spendAmountDescription') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('loyalty::app.pointsEarned') }}
                        </label>
                        <x-input type="number" step="1" wire:model="earn_rate_points" class="block w-full" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.pointsEarnedDescription') }}</p>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>{{ __('loyalty::app.example') }}</strong> 
                        {{ __('loyalty::app.earningExample', ['currency' => currency(), 'earn_rate_rupees' => $earn_rate_rupees, 'earn_rate_points' => $earn_rate_points]) }}
                    </p>
                </div>
            </div>
            @endif

            <!-- Redemption Rules -->
            @if($enable_points)
            <div class="mb-6">
                <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.redemptionRules') }}</h4>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.redemptionRulesDescription') }}</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('loyalty::app.valuePerPoint', ['currency' => currency()]) }}
                        </label>
                        <x-input type="number" step="0.01" wire:model="value_per_point" class="block w-full" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.valuePerPointDescription') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('loyalty::app.minRedeemPoints') }}
                        </label>
                        <x-input type="number" step="1" wire:model="min_redeem_points" class="block w-full" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.minRedeemPointsDescription') }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('loyalty::app.maxDiscountPercent') }}
                    </label>
                    <x-input type="number" step="0.01" wire:model="max_discount_percent" class="block w-full" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.maxDiscountPercentDescription') }}</p>
                </div>
            </div>
            @endif

            <!-- Info Note -->
            @if($enable_points)
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>{{ __('loyalty::app.note') }}:</strong> {{ __('loyalty::app.pointsCannotBeCombined') }}
                </p>
            </div>
            @endif

            <!-- Submit Button -->
            <div class="flex justify-start">
                <x-button type="submit">
                    @lang('app.save')
                </x-button>
            </div>
        </form>
    @endif

    <!-- Stamps Tab -->
    @if($activeTab === 'stamps')
        <form wire:submit.prevent="save">
            <!-- Enable/Disable -->
            <div class="mb-6">
                <label class="flex items-center">
                    <x-checkbox name="enabled" id="enabled_stamps" wire:model="enabled" />
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableLoyaltyProgram') }}</span>
                </label>
            </div>

            <!-- Loyalty Type Selection -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.loyaltyType') }}</h4>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.loyaltyTypeDescription') }}</p>
                
                <div class="space-y-3">
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $loyalty_type === 'points' ? 'border-skin-base bg-skin-base/10' : 'border-gray-300 dark:border-gray-600' }}">
                        <input type="radio" wire:model.live="loyalty_type" value="points" class="text-skin-base focus:ring-skin-base" />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('loyalty::app.pointsOnly') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.pointsOnlyDescription') }}</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $loyalty_type === 'stamps' ? 'border-skin-base bg-skin-base/10' : 'border-gray-300 dark:border-gray-600' }}">
                        <input type="radio" wire:model.live="loyalty_type" value="stamps" class="text-skin-base focus:ring-skin-base" />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('loyalty::app.stampsOnly') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.stampsOnlyDescription') }}</p>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors {{ $loyalty_type === 'both' ? 'border-skin-base bg-skin-base/10' : 'border-gray-300 dark:border-gray-600' }}">
                        <input type="radio" wire:model.live="loyalty_type" value="both" class="text-skin-base focus:ring-skin-base" />
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('loyalty::app.bothPointsAndStamps') }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('loyalty::app.bothPointsAndStampsDescription') }}</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Platform Enable/Disable for Stamps -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <h4 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.enableForPlatforms') }} ({{ __('loyalty::app.stamps') }})</h4>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.enableForPlatformsDescription') }}</p>
                
                <div class="flex justify-between items-center">
                    <label class="flex items-center">
                        <x-checkbox name="enable_stamps_for_pos" id="enable_stamps_for_pos" wire:model="enable_stamps_for_pos" />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableForPos') }}</span>
                    </label>
                    
                    <label class="flex items-center">
                        <x-checkbox name="enable_stamps_for_customer_site" id="enable_stamps_for_customer_site" wire:model="enable_stamps_for_customer_site" />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableForCustomerSite') }}</span>
                    </label>
                    
                    @if($isKioskModuleEnabled)
                    <label class="flex items-center">
                        <x-checkbox name="enable_stamps_for_kiosk" id="enable_stamps_for_kiosk" wire:model="enable_stamps_for_kiosk" />
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('loyalty::app.enableForKiosk') }}</span>
                    </label>
                    @endif
                </div>
            </div>

            <!-- Stamp Rules Management -->
            <div class="mb-6">
                <div class="mb-4 flex justify-between items-center">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.manageStampRules') }}</h4>
                    <x-button wire:click="openStampRuleModal()" type="button">
                        {{ __('loyalty::app.addStampRule') }}
                    </x-button>
                </div>
                
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.stampsDescription') }}</p>
            
            @if(count($stampRules) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.menuItem') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.stampsRequired') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.rewardType') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('loyalty::app.rewardValue') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('app.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($stampRules as $rule)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $rule['menu_item']['item_name'] ?? __('loyalty::app.unknownItem') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $rule['stamps_required'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('loyalty::app.rewardType' . ucfirst($rule['reward_type'])) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if($rule['reward_type'] === 'free_item')
                                            @php
                                                $itemName = $rule['reward_menu_item']['item_name'] ?? __('loyalty::app.unknownItem');
                                                $variationName = null;
                                                if (isset($rule['reward_menu_item_variation']) && $rule['reward_menu_item_variation']) {
                                                    $variationName = $rule['reward_menu_item_variation']['variation'] ?? null;
                                                }
                                            @endphp
                                            {{ $itemName }}
                                            @if($variationName)
                                                <span class="text-gray-400 dark:text-gray-500"> - {{ $variationName }}</span>
                                            @endif
                                        @elseif($rule['reward_type'] === 'discount_percent')
                                            {{ $rule['reward_value'] }}%
                                        @else
                                            {{ currency_format($rule['reward_value'], restaurant()->currency_id) }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($rule['is_active'])
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">{{ __('app.active') }}</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ __('app.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button type="button" wire:click="openStampRuleModal({{ $rule['id'] }})" class="text-skin-base hover:text-skin-base mr-3">{{ __('app.edit') }}</button>
                                        <button type="button" wire:click="deleteStampRule({{ $rule['id'] }})" 
                                                wire:confirm="{{ __('loyalty::app.confirmDeleteStampRule') }}"
                                                class="text-red-600 hover:text-red-900">{{ __('app.delete') }}</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('loyalty::app.noStampRulesFound') }}</p>
                </div>
            @endif
            </div>

            <!-- Submit Button -->
            <div class="flex justify-start">
                <x-button type="submit">
                    @lang('app.save')
                </x-button>
            </div>
        </form>
    @endif

    <!-- Tier Modal -->
    @include('loyalty::livewire.restaurant.partials.tier-modal')

    <!-- Stamp Rule Modal -->
    @include('loyalty::livewire.restaurant.partials.stamp-rule-modal')
</div>
