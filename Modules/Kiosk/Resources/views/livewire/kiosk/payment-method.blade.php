<div>
    @php
        // Check if Loyalty module is available before accessing loyalty methods
        // Use method_exists() instead of class_exists() since we're using the trait
        $isLoyaltyModuleAvailable = function_exists('module_enabled') && 
                                    module_enabled('Loyalty') && 
                                    method_exists($this, 'isLoyaltyEnabled');
        
        // Get loyalty properties for view - must be at top before any usage
        // Only call methods if module is available and methods exist
        $isLoyaltyEnabledForKiosk = false;
        $isPointsEnabledForKiosk = false;
        $isStampsEnabledForKiosk = false;
        
        if ($isLoyaltyModuleAvailable) {
            try {
                $isLoyaltyEnabledForKiosk = $this->isLoyaltyEnabled();
                if (method_exists($this, 'isPointsEnabledForKiosk')) {
                    $isPointsEnabledForKiosk = $this->isPointsEnabledForKiosk();
                }
                if (method_exists($this, 'isStampsEnabledForKiosk')) {
                    $isStampsEnabledForKiosk = $this->isStampsEnabledForKiosk();
                }
            } catch (\Exception $e) {
                // Silently fail if methods don't exist
            }
        }
        
        // Get component properties for view
        $viewCustomerId = $this->customerId ?? null;
        $viewAvailableLoyaltyPoints = $this->availableLoyaltyPoints ?? 0;
        $viewCustomerStamps = $this->customerStamps ?? [];
        $viewSelectedStampRuleId = $this->selectedStampRuleId ?? null;
        $viewSelectedStampRuleIds = $this->selectedStampRuleIds ?? [];
        $viewStampDiscountAmount = $this->stampDiscountAmount ?? 0;
        $viewStampDiscountBreakdown = $this->stampDiscountBreakdown ?? [];
        $viewServiceTotal = $serviceTotal ?? 0;
        $viewChargeBreakdown = $chargeBreakdown ?? [];
        $viewLoyaltyDiscountAmount = $this->loyaltyDiscountAmount ?? 0;
        $viewLoyaltyPointsRedeemed = $this->loyaltyPointsRedeemed ?? 0;
        $viewLoyaltyPointsValue = $this->loyaltyPointsValue ?? 0;
        $viewMaxLoyaltyDiscount = $this->maxLoyaltyDiscount ?? 0;
        $viewMinRedeemPoints = $this->minRedeemPoints ?? 0;
        $viewPointsToRedeem = $this->pointsToRedeem ?? 0;
        
        // Debug: Check if we should show loyalty section at all
        $shouldShowLoyaltySection = $isLoyaltyEnabledForKiosk && ($isPointsEnabledForKiosk || $isStampsEnabledForKiosk);
    @endphp
    <!-- 🟡 6. Payment Method Selection -->
    <div x-show="currentScreen === 'payment'" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-full"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-x-0"
            x-transition:leave-end="opacity-0 transform -translate-x-full"
     
            x-init="() => {
                window.addEventListener('proceedToPayment', () => {
                    currentScreen = 'payment';
                    $wire.dispatch('refreshPaymentMethod');
                })
            }"
            class="min-h-screen flex items-center justify-center bg-white">
        <div class="w-full max-w-6xl px-6">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ __('kiosk::modules.payment.heading') }}</h1>
                <p class="text-xl text-gray-600">{{ __('kiosk::modules.payment.subheading') }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Order Summary -->
                <div class="bg-white border border-gray-200 rounded-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('kiosk::modules.payment.order_summary') }}</h2>
                    <div class="space-y-3 mb-6">
                        @forelse ($cartItemList['items'] as $item)
                            @php
                                $displayItemAmount = $item['amount'];
                                $originalItemAmount = $item['amount'];
                                $hasStampDiscount = false;
                                $stampDiscountLabel = null;

                                if ($viewStampDiscountAmount > 0 && !empty($viewSelectedStampRuleIds)) {
                                    if (module_enabled('Loyalty')) {
                                        foreach ($viewSelectedStampRuleIds as $ruleId) {
                                            $stampRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($ruleId);
                                            if (!$stampRule || $stampRule->menu_item_id != $item['menu_item']['id']) {
                                                continue;
                                            }
                                            $ruleDiscount = $viewStampDiscountBreakdown[$ruleId] ?? 0;
                                            if ($ruleDiscount <= 0) {
                                                continue;
                                            }
                                            $matchingItems = collect($cartItemList['items'])->filter(function($cartItem) use ($stampRule) {
                                                return $cartItem['menu_item']['id'] == $stampRule->menu_item_id;
                                            });
                                            $totalMatchingQuantity = $matchingItems->sum('quantity');
                                            if ($totalMatchingQuantity > 0) {
                                                $discountPerUnit = $ruleDiscount / $totalMatchingQuantity;
                                                $originalItemAmount = $item['amount'] + ($discountPerUnit * $item['quantity']);
                                                $hasStampDiscount = true;
                                                if ($stampRule->reward_type === 'discount_percent') {
                                                    $stampDiscountLabel = __('loyalty::app.discount') . ' ' . number_format($stampRule->reward_value, 2) . '%';
                                                } elseif ($stampRule->reward_type === 'discount_amount') {
                                                    $stampDiscountLabel = __('loyalty::app.discount');
                                                }
                                            }
                                        }
                                    }
                                }
                            @endphp
                            <div class="flex justify-between text-lg" wire:key="payment-cart-item-{{ $item['id'] }}">
                                <span class="flex items-center gap-2">
                                    {{ $item['quantity'] }}x {{ $item['menu_item']['name'] }}
                                    @if($hasStampDiscount)
                                        <span class="px-2 py-0.5 text-[10px] rounded bg-green-100 text-green-700">
                                            {{ $stampDiscountLabel ?? __('loyalty::app.stampDiscount') }}
                                        </span>
                                    @endif
                                </span>
                                <span>
                                    @if($hasStampDiscount && $originalItemAmount > $displayItemAmount)
                                        <span class="line-through text-gray-400 text-sm mr-2">
                                            {{ currency_format($originalItemAmount, $restaurant->currency_id) }}
                                        </span>
                                    @endif
                                    {{ currency_format($displayItemAmount, $restaurant->currency_id) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-gray-500">
                                <span>{{ __('kiosk::modules.payment.empty') }}</span>
                            </div>
                        @endforelse
                        @if(!empty($viewSelectedStampRuleIds) && module_enabled('Loyalty'))
                            @foreach($viewSelectedStampRuleIds as $ruleId)
                                @php
                                    $freeRewardQty = 0;
                                    $freeRewardName = null;
                                    $selectedRule = \Modules\Loyalty\Entities\LoyaltyStampRule::find($ruleId);
                                    if ($selectedRule && $selectedRule->reward_type === 'free_item') {
                                        $stampData = collect($viewCustomerStamps)->first(function ($data) use ($ruleId) {
                                            return isset($data['rule']) && ($data['rule']->id ?? null) == $ruleId;
                                        });
                                        $availableStamps = (int)($stampData['available_stamps'] ?? 0);
                                        $stampsRequired = (int)($stampData['stamps_required'] ?? 0);
                                        $maxItemsByStamps = ($stampsRequired > 0) ? intdiv($availableStamps, $stampsRequired) : 0;
                                        $eligibleQty = collect($cartItemList['items'])->where('menu_item.id', $selectedRule->menu_item_id)->sum('quantity');
                                        $freeRewardQty = max(0, min($eligibleQty, $maxItemsByStamps));
                                        $freeRewardName = $selectedRule->rewardMenuItem->item_name ?? null;
                                    }
                                @endphp
                                @if($freeRewardQty > 0 && $freeRewardName)
                                    <div class="flex justify-between text-lg text-green-700">
                                        <span>{{ $freeRewardQty }}x {{ $freeRewardName }} ({{ __('loyalty::app.freeItem') }})</span>
                                        <span>{{ currency_format(0, $restaurant->currency_id) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                    <div class="border-t border-gray-200 pt-6 space-y-3">
                        @if($viewLoyaltyDiscountAmount > 0 && $viewLoyaltyPointsRedeemed > 0)
                            <div class="flex justify-between text-lg text-green-600">
                                <span>{{ __('loyalty::app.loyaltyDiscount') }}</span>
                                <span class="font-semibold">-{{ currency_format($viewLoyaltyDiscountAmount, $restaurant->currency_id) }}</span>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ __('loyalty::app.pointsRedeemed') }}: {{ number_format($viewLoyaltyPointsRedeemed) }} @lang('loyalty::app.points')
                            </div>
                        @endif
                        <div class="flex justify-between text-lg">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600">{{ __('kiosk::modules.payment.subtotal') }}</span>
                                @if($viewStampDiscountAmount > 0)
                                    <span class="px-1.5 py-0.5 text-xs rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        @lang('loyalty::app.stampDiscount')
                                        (-{{ currency_format($viewStampDiscountAmount, $restaurant->currency_id) }})
                                    </span>
                                @endif
                            </div>
                            <span class="font-semibold">{{ currency_format($discountedSubtotal ?? $subtotal, $restaurant->currency_id) }}</span>
                        </div>
                        @if(!empty($viewChargeBreakdown))
                            @foreach($viewChargeBreakdown as $charge)
                                <div class="flex justify-between text-lg">
                                    <span class="text-gray-600">{{ $charge['name'] ?? __('charges.charge') }}</span>
                                    <span class="font-semibold">{{ currency_format($charge['amount'] ?? 0, $restaurant->currency_id) }}</span>
                                </div>
                            @endforeach
                        @endif
                        
                        @if($totalTaxAmount > 0)
                            @if($taxMode === 'order' && !empty($taxBreakdown))
                                @foreach($taxBreakdown as $taxName => $taxInfo)
                                    <div class="flex justify-between text-lg">
                                        <span class="text-gray-600">{{ $taxName }} ({{ number_format($taxInfo['percent'], 2) }}%)</span>
                                        <span class="font-semibold">{{ currency_format($taxInfo['amount'], $restaurant->currency_id) }}</span>
                                    </div>
                                @endforeach
                            @else
                                @if(!empty($taxBreakdown))
                                    @foreach($taxBreakdown as $taxName => $taxInfo)
                                        <div class="flex justify-between text-lg">
                                            <span class="text-gray-600">{{ $taxName }} ({{ number_format($taxInfo['percent'], 2) }}%)</span>
                                            <span class="font-semibold">{{ currency_format($taxInfo['amount'], $restaurant->currency_id) }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="flex justify-between text-lg">
                                        <span class="text-gray-600">{{ __('kiosk::modules.payment.tax') }}</span>
                                        <span class="font-semibold">{{ currency_format($totalTaxAmount, $restaurant->currency_id) }}</span>
                                    </div>
                                @endif
                            @endif
                        @endif
                        <div class="flex justify-between text-2xl font-bold text-gray-900 border-t border-gray-200 pt-3">
                            <span>{{ __('kiosk::modules.payment.total') }}</span>
                            <span>{{ currency_format($total, $restaurant->currency_id) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="bg-white border border-gray-200 rounded-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('kiosk::modules.payment.heading') }}</h2>
                    <!-- Loyalty Points Redemption -->
                    @if($isPointsEnabledForKiosk)
                        @if($viewCustomerId && $viewAvailableLoyaltyPoints > 0)
                        <div class="mb-6 border-2 border-gray-200 rounded-lg p-6 hover:bg-gray-50 transition-all duration-200">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 bg-skin-base rounded-lg flex items-center justify-center mr-6">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="font-bold text-gray-900 text-lg">{{ __('loyalty::app.loyaltyProgram') }}</div>
                                        <div class="text-gray-600 text-sm">{{ __('loyalty::app.redeemLoyaltyPoints') }}</div>
                                    </div>
                                </div>
                                @if($viewLoyaltyDiscountAmount > 0)
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            </div>
                            
                            <!-- Loyalty Details -->
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ __('loyalty::app.availablePoints') }}:</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ number_format($viewAvailableLoyaltyPoints) }} @lang('loyalty::app.points')</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ __('loyalty::app.pointsValue') }}:</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ currency_format($viewLoyaltyPointsValue * $viewAvailableLoyaltyPoints, $restaurant->currency_id) }}</span>
                                </div>
                                @if($viewMaxLoyaltyDiscount > 0)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">{{ __('loyalty::app.maxDiscountToday') }}:</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ currency_format($viewMaxLoyaltyDiscount, $restaurant->currency_id) }}</span>
                                    </div>
                                @endif
                                @if($viewMinRedeemPoints > 0)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">{{ __('loyalty::app.minRedeemPoints') }}:</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ number_format($viewMinRedeemPoints) }} @lang('loyalty::app.points')</span>
                                    </div>
                                @endif
                                @if($viewPointsToRedeem > 0 && $viewLoyaltyPointsRedeemed == 0)
                                    <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                                        <span class="text-sm font-medium text-blue-600">{{ __('loyalty::app.pointsToRedeem') }}:</span>
                                        <span class="text-sm font-bold text-blue-600">{{ number_format($viewPointsToRedeem) }} @lang('loyalty::app.points')</span>
                                    </div>
                                    @php
                                        // Calculate preview discount amount for display only (not applied)
                                        $previewDiscount = 0;
                                        if ($viewPointsToRedeem > 0 && $viewLoyaltyPointsValue > 0) {
                                            $previewDiscountAmount = $viewPointsToRedeem * $viewLoyaltyPointsValue;
                                            if ($viewMaxLoyaltyDiscount > 0) {
                                                $previewDiscount = min($previewDiscountAmount, $viewMaxLoyaltyDiscount);
                                            } else {
                                                $previewDiscount = $previewDiscountAmount;
                                            }
                                        }
                                    @endphp
                                    @if($previewDiscount > 0)
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-blue-600">{{ __('loyalty::app.discountAmount') }}:</span>
                                            <span class="text-sm font-bold text-blue-600">{{ currency_format($previewDiscount, $restaurant->currency_id) }}</span>
                                        </div>
                                    @endif
                                @endif
                                @if($viewLoyaltyDiscountAmount > 0 && $viewLoyaltyPointsRedeemed > 0)
                                    <div class="flex justify-between items-center pt-2 border-t border-green-200">
                                        <span class="text-sm font-medium text-green-600">{{ __('loyalty::app.discountApplied') }}:</span>
                                        <span class="text-sm font-bold text-green-600">{{ currency_format($viewLoyaltyDiscountAmount, $restaurant->currency_id) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-500">{{ __('loyalty::app.pointsRedeemed') }}:</span>
                                        <span class="text-xs text-gray-500">{{ number_format($viewLoyaltyPointsRedeemed) }} @lang('loyalty::app.points')</span>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Action Button -->
                            <div>
                                @if($viewLoyaltyDiscountAmount > 0 && $viewLoyaltyPointsRedeemed > 0)
                                    <button wire:click="removeLoyaltyRedemption" 
                                            class="w-full bg-red-100 text-red-700 py-3 rounded-lg font-medium text-base hover:bg-red-200 transition-colors duration-200">
                                        {{ __('loyalty::app.removeDiscount') }}
                                    </button>
                                @elseif($viewAvailableLoyaltyPoints > 0 && $viewLoyaltyPointsRedeemed == 0)
                                    <button wire:click="redeemLoyaltyPoints" 
                                            class="w-full bg-skin-base text-white py-3 rounded-lg font-medium text-base hover:bg-skin-base transition-all duration-200">
                                        {{ __('loyalty::app.redeemLoyaltyPoints') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        @elseif($isLoyaltyEnabledForKiosk && $viewCustomerId && $viewAvailableLoyaltyPoints == 0)
                            <div class="mb-6 border-2 border-gray-200 rounded-lg p-6 bg-gray-50">
                                <div class="text-center text-gray-600">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm">{{ __('loyalty::app.noPointsAvailable') }}</p>
                                </div>
                            </div>
                        @elseif($isLoyaltyEnabledForKiosk && !$viewCustomerId)
                            <div class="mb-6 border-2 border-gray-200 rounded-lg p-6 bg-gray-50">
                                <div class="text-center text-gray-600">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <p class="text-sm">{{ __('Please provide customer information to redeem loyalty points.') }}</p>
                                </div>
                            </div>
                        @endif
                    @endif
                    
                    <!-- Stamp Redemption -->
                    @if($isStampsEnabledForKiosk)
                        @php
                            // Calculate stamp redemption status (outside conditionals so it's available everywhere)
                            $hasRedeemedStamps = (!empty($viewSelectedStampRuleIds) || $viewSelectedStampRuleId !== null) || $viewStampDiscountAmount > 0;
                            $redeemableStamps = collect($viewCustomerStamps)->filter(function ($stampData) {
                                return ($stampData['can_redeem'] ?? false) === true;
                            });
                            $hasRedeemableStamps = $redeemableStamps->isNotEmpty();
                        @endphp
                        @if($viewCustomerId && !empty($viewCustomerStamps))
                            @if($hasRedeemableStamps)
                            <div class="mb-6 border-2 border-gray-200 rounded-lg p-6 hover:bg-gray-50 transition-all duration-200">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-16 h-16 bg-green-500 rounded-lg flex items-center justify-center mr-6">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="text-left">
                                            <div class="font-bold text-gray-900 text-lg">{{ __('loyalty::app.redeemStamps') }}</div>
                                            <div class="text-gray-600 text-sm">{{ __('loyalty::app.redeemStampsDescription') }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Available Stamps -->
                                <div class="space-y-3 mb-4">
                                    @foreach($redeemableStamps as $stampData)
                                        @php
                                            $rule = $stampData['rule'];
                                            $availableStamps = $stampData['available_stamps'] ?? 0;
                                            $stampsRequired = $stampData['stamps_required'] ?? 0;
                                            $canRedeem = $stampData['can_redeem'] ?? false;
                                        @endphp
                                        @if($canRedeem)
                                            @php
                                                $isSelected = in_array($rule->id, $viewSelectedStampRuleIds, true) || $viewSelectedStampRuleId == $rule->id;
                                            @endphp
                                            <div class="border border-green-200 rounded-lg p-4 {{ $isSelected ? 'bg-green-50 border-green-400' : '' }}">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div>
                                                        <div class="font-semibold text-gray-900">{{ $rule->menuItem->item_name ?? __('loyalty::app.unknownItem') }}</div>
                                                        <div class="text-sm text-gray-600">
                                                            {{ __('loyalty::app.availableStamps') }}: {{ $availableStamps }}/{{ $stampsRequired }}
                                                        </div>
                                                    </div>
                                                    @if($isSelected)
                                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                @if($rule->reward_type === 'free_item')
                                                    <div class="text-sm text-green-600 font-medium">
                                                        {{ __('loyalty::app.reward') }}: {{ __('app.freeItem') }} - {{ $rule->rewardMenuItem->item_name ?? __('loyalty::app.unknownItem') }}
                                                    </div>
                                                @elseif($rule->reward_type === 'discount_percent')
                                                    <div class="text-sm text-green-600 font-medium">
                                                        {{ __('loyalty::app.reward') }}: {{ number_format($rule->reward_value, 2) }}% {{ __('loyalty::app.discount') }}
                                                    </div>
                                                @elseif($rule->reward_type === 'discount_amount')
                                                    <div class="text-sm text-green-600 font-medium">
                                                        {{ __('loyalty::app.reward') }}: {{ currency_format($rule->reward_value, $restaurant->currency_id) }} {{ __('loyalty::app.discount') }}
                                                    </div>
                                                @endif
                                                @if(!$isSelected)
                                                    <button wire:click="redeemStamps({{ $rule->id }})" 
                                                            class="mt-2 w-full bg-green-500 text-white py-2 rounded-lg font-medium text-sm hover:bg-green-600 transition-all duration-200">
                                                        {{ __('loyalty::app.redeem') }}
                                                    </button>
                                                @else
                                                    <button wire:click="removeStampRedemption({{ $rule->id }})" 
                                                            class="mt-2 w-full bg-red-100 text-red-700 py-2 rounded-lg font-medium text-sm hover:bg-red-200 transition-colors duration-200">
                                                        {{ __('loyalty::app.removeStampRedemption') }}
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        @elseif($isLoyaltyEnabledForKiosk && $viewCustomerId && empty($viewCustomerStamps))
                            <div class="mb-6 border-2 border-gray-200 rounded-lg p-6 bg-gray-50">
                                <div class="text-center text-gray-600">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm">{{ __('loyalty::app.noStampsAvailable') }}</p>
                                </div>
                            </div>
                        @elseif($isLoyaltyEnabledForKiosk && !$viewCustomerId)
                            <div class="mb-6 border-2 border-gray-200 rounded-lg p-6 bg-gray-50">
                                <div class="text-center text-gray-600">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <p class="text-sm">{{ __('Please provide customer information to redeem stamps.') }}</p>
                                </div>
                            </div>
                        @endif
                    @endif
                    
                    @if($isLoyaltyEnabledForKiosk && !$isPointsEnabledForKiosk && !$isStampsEnabledForKiosk)
                        <!-- Loyalty is enabled but points/stamps are not enabled for kiosk -->
                        <div class="mb-6 border-2 border-gray-200 rounded-lg p-6 bg-gray-50">
                            <div class="text-center text-gray-600">
                                <p class="text-sm">{{ __('Loyalty program is enabled but points and stamps are not enabled for kiosk platform.') }}</p>
                            </div>
                        </div>
                    @endif
                    
                    <div class="space-y-4">
                  
                        <!-- Cash -->
                        <button @click="selectPaymentMethod('due'); $wire.selectPaymentMethod('due')" 
                                :class="{'border-skin-base': paymentMethod === 'due'}"
                                class="w-full border-2 border-gray-200 rounded-lg p-6 flex items-center justify-between hover:bg-gray-50 transition-all duration-200">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-skin-base rounded-lg flex items-center justify-center mr-6">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <div class="font-bold text-gray-900 text-lg">{{ __('kiosk::modules.payment.cash') }}</div>
                                    <div class="text-gray-600">{{ __('kiosk::modules.payment.cash_desc') }}</div>
                                </div>
                            </div>
                            <svg x-show="paymentMethod === 'due'" class="w-8 h-8 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>

                        <!-- Offline Payment (cash/bank transfer/custom offline methods) -->
                        @if(!empty($offlinePaymentMethods) && count($offlinePaymentMethods) > 0)
                            <button @click="paymentMethod = 'offline'; $wire.openOfflinePaymentModal()" 
                                    :class="{'border-skin-base': paymentMethod === 'offline'}"
                                    class="w-full border-2 border-gray-200 rounded-lg p-6 flex items-center justify-between hover:bg-gray-50 transition-all duration-200">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 bg-amber-500 rounded-lg flex items-center justify-center mr-6">
                                        <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18v10H3V7zm3 3h6m-6 4h3m9-7V5a2 2 0 00-2-2H5a2 2 0 00-2 2v2" />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="font-bold text-gray-900 text-lg">{{ __('kiosk::modules.payment.offlinePayment') }}</div>
                                        <div class="text-gray-600">{{ __('kiosk::modules.payment.offlinePaymentDescription') }}</div>
                                    </div>
                                </div>
                                <svg x-show="paymentMethod === 'offline'" class="w-8 h-8 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        @endif

                        <!-- Online Payment -->
                        @if($paymentGateway && ($paymentGateway->stripe_status || $paymentGateway->razorpay_status || $paymentGateway->flutterwave_status || $paymentGateway->paypal_status || $paymentGateway->payfast_status || $paymentGateway->paystack_status || $paymentGateway->xendit_status || $paymentGateway->epay_status || $paymentGateway->mollie_status || $paymentGateway->tap_status))
                            <button @click="paymentMethod = 'online'; $wire.openPaymentModal()" 
                                    :class="{'border-skin-base': paymentMethod === 'online' || ($wire.paymentMethod && $wire.paymentMethod.startsWith('online_'))}"
                                    class="w-full border-2 border-gray-200 rounded-lg p-6 flex items-center justify-between hover:bg-gray-50 transition-all duration-200">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 bg-blue-500 rounded-lg flex items-center justify-center mr-6">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="font-bold text-gray-900 text-lg">{{ __('kiosk::modules.payment.online_payment') }}</div>
                                        <div class="text-gray-600">{{ __('kiosk::modules.payment.online_payment_desc') }}</div>
                                    </div>
                                </div>
                                <svg x-show="paymentMethod === 'online' || ($wire.paymentMethod && $wire.paymentMethod.startsWith('online_'))" class="w-8 h-8 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        @endif

                        @if($paymentGateway && $paymentGateway->is_qr_payment_enabled)
                            <button @click="selectPaymentMethod('upi'); $wire.selectPaymentMethod('upi')" 
                                :class="{'border-skin-base': paymentMethod === 'upi'}"
                                class="w-full border-2 border-gray-200 rounded-lg p-6 flex items-center justify-between hover:bg-gray-50 transition-all duration-200">
                                <div class="flex items-center">
                                    <div class="w-16 h-16 bg-skin-base rounded-lg flex items-center justify-center mr-6">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-qr-code-scan w-8 h-8 text-white" viewBox="0 0 16 16">
                                            <path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z"/>
                                            <path d="M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z"/>
                                            <path d="M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z"/>
                                            <path d="M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1H8v1z"/>
                                            <path d="M12 9h2V8h-2z"/>
                                          </svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="font-bold text-gray-900 text-lg">{{ __('kiosk::modules.payment.upi') }}</div>
                                        <div class="text-gray-600">{{ __('kiosk::modules.payment.upi_desc') }}</div>
                                    </div>
                                </div>
                                <svg x-show="paymentMethod === 'upi'" class="w-8 h-8 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            @if($showQrCode && $paymentGateway && $paymentGateway->qr_code_image_url)
                            <div class="mt-4">
                                <div class="text-center">
                                    <img src="{{ $paymentGateway->qr_code_image_url }}" alt="UPI QR Code" class=" max-w-48 mx-auto h-48">
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm mt-2 text-center">{{ __('kiosk::modules.payment.upi_qr_code_desc') }}</p>
                            @endif
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 space-y-4">
                        @if($isRestaurantOpenForOrders)
                            <button @click="processPayment"
                                    wire:click="processPayment"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75 cursor-not-allowed pointer-events-none"
                                    wire:target="processPayment"
                                    :disabled="!paymentMethod 
                                        || (paymentMethod === 'online' && !$wire.selectedOnlineGateway) 
                                        || ($wire.paymentMethod && $wire.paymentMethod.startsWith('online_') && !$wire.selectedOnlineGateway)
                                        || (paymentMethod === 'offline' && !$wire.selectedOfflineMethodId)"
                                    :class="{
                                        'opacity-50 cursor-not-allowed': !paymentMethod 
                                            || (paymentMethod === 'online' && !$wire.selectedOnlineGateway) 
                                            || ($wire.paymentMethod && $wire.paymentMethod.startsWith('online_') && !$wire.selectedOnlineGateway)
                                            || (paymentMethod === 'offline' && !$wire.selectedOfflineMethodId)
                                    }"
                                    class="w-full bg-skin-base text-white py-6 rounded-lg font-bold text-xl transition-all duration-200 hover:bg-skin-base">
                                <span class="inline-flex items-center justify-center">
                                    <svg wire:loading wire:target="processPayment"
                                         class="w-5 h-5 mr-2 text-white animate-spin"
                                         xmlns="http://www.w3.org/2000/svg"
                                         fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor"
                                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                                    </svg>
                                    <span>{{ __('kiosk::modules.payment.place_order') }}</span>
                                </span>
                            </button>
                        @else
                            <div class="w-full px-4 py-4 text-lg font-semibold text-center text-red-700 bg-red-50 border border-red-200 rounded-lg">
                                {{ $restaurantClosedMessage }}
                            </div>
                        @endif
                        <button @click="currentScreen = 'customer-info'" 
                                class="w-full border-2 border-gray-300 text-gray-700 py-4 rounded-lg font-medium text-lg hover:bg-gray-50 transition-colors duration-200">
                            {{ __('kiosk::modules.payment.back') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Gateway Modal -->
    <x-dialog-modal wire:model.live="showPaymentModal" maxWidth="md">
        <x-slot name="title">
            @lang('modules.order.chooseGateway')
        </x-slot>

        <x-slot name="content">
            <div class="flex items-center justify-between p-2 mb-4 rounded-md cursor-pointer bg-gray-50 dark:bg-gray-800">
                <div class="flex items-center min-w-0">
                    <div>
                        <div class="font-medium text-gray-700 truncate dark:text-white">
                            {{ __('kiosk::modules.payment.order_total') }}
                        </div>
                    </div>
                </div>
                <div class="inline-flex flex-col text-base font-semibold text-right text-gray-900 dark:text-white">
                    <div>{{ currency_format($total, $restaurant->currency_id) }}</div>
                </div>
            </div>

            <div class="grid items-center w-full grid-cols-1 gap-4 mt-4 md:grid-cols-2">
                @if ($paymentGateway->stripe_status)
                    <x-secondary-button wire:click="selectOnlineGateway('stripe')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'stripe'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg height="21" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 468 222.5" xml:space="preserve"><path d="M414 113.4c0-25.6-12.4-45.8-36.1-45.8-23.8 0-38.2 20.2-38.2 45.6 0 30.1 17 45.3 41.4 45.3 11.9 0 20.9-2.7 27.7-6.5v-20c-6.8 3.4-14.6 5.5-24.5 5.5-9.7 0-18.3-3.4-19.4-15.2h48.9c0-1.3.2-6.5.2-8.9m-49.4-9.5c0-11.3 6.9-16 13.2-16 6.1 0 12.6 4.7 12.6 16zm-63.5-36.3c-9.8 0-16.1 4.6-19.6 7.8l-1.3-6.2h-22v116.6l25-5.3.1-28.3c3.6 2.6 8.9 6.3 17.7 6.3 17.9 0 34.2-14.4 34.2-46.1-.1-29-16.6-44.8-34.1-44.8m-6 68.9c-5.9 0-9.4-2.1-11.8-4.7l-.1-37.1c2.6-2.9 6.2-4.9 11.9-4.9 9.1 0 15.4 10.2 15.4 23.3 0 13.4-6.2 23.4-15.4 23.4m-71.3-74.8 25.1-5.4V36l-25.1 5.3zm0 7.6h25.1v87.5h-25.1zm-26.9 7.4-1.6-7.4h-21.6v87.5h25V97.5c5.9-7.7 15.9-6.3 19-5.2v-23c-3.2-1.2-14.9-3.4-20.8 7.4m-50-29.1-24.4 5.2-.1 80.1c0 14.8 11.1 25.7 25.9 25.7 8.2 0 14.2-1.5 17.5-3.3V135c-3.2 1.3-19 5.9-19-8.9V90.6h19V69.3h-19zM79.3 94.7c0-3.9 3.2-5.4 8.5-5.4 7.6 0 17.2 2.3 24.8 6.4V72.2c-8.3-3.3-16.5-4.6-24.8-4.6C67.5 67.6 54 78.2 54 95.9c0 27.6 38 23.2 38 35.1 0 4.6-4 6.1-9.6 6.1-8.3 0-18.9-3.4-27.3-8v23.8c9.3 4 18.7 5.7 27.3 5.7 20.8 0 35.1-10.3 35.1-28.2-.1-29.8-38.2-24.5-38.2-35.7" style="fill-rule:evenodd;clip-rule:evenodd;fill:#635bff"/></svg>
                        </span>
                    </x-secondary-button>
                @endif

                @if ($paymentGateway->razorpay_status)
                    <x-secondary-button wire:click="selectOnlineGateway('razorpay')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'razorpay'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg height="21" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.88 26.53" xml:space="preserve">
                                <path d="M11.19 9.03 7.94 21.47H0l1.61-6.12zm16.9-3.95c1.86.01 3.17.42 3.91 1.25s.92 2.01.51 3.56a6.1 6.1 0 0 1-1.59 2.8c-.8.8-1.78 1.38-2.87 1.68.83.19 1.34.78 1.5 1.79l.03.22.6 5.09h-3.7l-.62-5.48a1.2 1.2 0 0 0-.15-.52c-.09-.16-.22-.29-.37-.39a2.3 2.3 0 0 0-1-.25h-2.49l-1.74 6.63h-3.46l4.3-16.38zm94.79 4.29-4.4 6.34-5.19 7.52-.04.04-1.16 1.68-.04.06-.05.08-1 1.44h-3.44l4.02-5.67-1.82-11.09h3.57l.9 7.23 4.36-6.19.06-.09.07-.1.07-.09.54-1.15h3.55zm-30.48.88a3.68 3.68 0 0 1 1.24 2.19c.18 1.07.1 2.18-.21 3.22a9.5 9.5 0 0 1-1.46 3.19 7.15 7.15 0 0 1-2.35 2.13c-.88.48-1.85.73-2.85.73a3.67 3.67 0 0 1-2.02-.51c-.47-.28-.83-.71-1.03-1.22l-.06-.2-1.77 6.75h-3.43l3.51-13.4.02-.06.01-.06.86-3.25h3.35l-.57 1.88-.01.08c.49-.7 1.15-1.27 1.91-1.64.76-.4 1.6-.6 2.45-.6.85-.05 1.71.23 2.41.77m-4.14 1.86a3 3 0 0 0-2.18.88c-.68.7-1.15 1.59-1.36 2.54-.3 1.11-.28 1.95.02 2.53s.87.88 1.72.88c.81.02 1.59-.29 2.18-.86.66-.69 1.12-1.55 1.33-2.49.29-1.09.27-1.96-.03-2.57s-.86-.91-1.68-.91m15.4-2.12c.46.29.82.72 1.02 1.23l.07.19.44-1.66h3.36l-3.08 11.7h-3.37l.45-1.73c-.51.61-1.15 1.09-1.87 1.42-.7.32-1.45.49-2.21.49-.88.04-1.76-.21-2.48-.74-.66-.52-1.1-1.28-1.24-2.11a6.94 6.94 0 0 1 .19-3.17 9.8 9.8 0 0 1 1.49-3.21c.63-.89 1.44-1.64 2.38-2.18.86-.5 1.84-.77 2.83-.77.72-.02 1.42.16 2.02.54m-1.74 2.15c-.41 0-.82.08-1.19.24-.38.16-.72.39-1.01.68-.67.71-1.15 1.59-1.36 2.55-.3 1.08-.28 1.9.04 2.49.31.59.89.87 1.75.87.4.01.8-.07 1.18-.22s.71-.38 1-.66a5.4 5.4 0 0 0 1.26-2.22l.08-.31c.3-1.11.29-1.96-.03-2.53-.31-.59-.88-.89-1.72-.89M81.13 9.63l.22.09-.86 3.19c-.49-.26-1.03-.39-1.57-.39-.82-.03-1.62.24-2.27.75-.56.48-.97 1.12-1.18 1.82l-.07.27-1.6 6.11h-3.42l3.1-11.7h3.37l-.44 1.72c.42-.58.96-1.05 1.57-1.4.68-.39 1.44-.59 2.22-.59.31-.02.63.02.93.13m-12.63.56c.76.48 1.31 1.24 1.52 2.12.25 1.06.21 2.18-.11 3.22-.3 1.18-.83 2.28-1.58 3.22-.71.91-1.61 1.63-2.64 2.12a7.75 7.75 0 0 1-3.35.73c-1.22 0-2.22-.24-3-.73a3.5 3.5 0 0 1-1.54-2.12 6.4 6.4 0 0 1 .11-3.22c.3-1.17.83-2.27 1.58-3.22.71-.9 1.62-1.63 2.66-2.12a7.8 7.8 0 0 1 3.39-.73 5.4 5.4 0 0 1 2.96.73m-3.66 1.91c-.81-.01-1.59.3-2.18.86-.61.58-1.07 1.43-1.36 2.57-.6 2.29-.02 3.43 1.74 3.43.8.02 1.57-.29 2.15-.85.6-.57 1.04-1.43 1.34-2.58.3-1.13.31-1.98.01-2.57-.29-.59-.86-.86-1.7-.86m-6.95-2.34-.6 2.32-7.55 6.67h6.06l-.72 2.73H45.05l.63-2.41 7.43-6.57h-5.65l.72-2.73h9.71zm-16.93.23c.46.29.82.72 1.02 1.23l.07.19.44-1.66h3.37l-3.07 11.7h-3.37l.45-1.73c-.51.6-1.14 1.08-1.85 1.41s-1.48.5-2.27.5a3.84 3.84 0 0 1-2.45-.74c-.66-.52-1.1-1.28-1.24-2.11a6.94 6.94 0 0 1 .19-3.17 9.6 9.6 0 0 1 1.49-3.21c.63-.89 1.44-1.64 2.37-2.18.86-.5 1.84-.76 2.83-.76.72-.02 1.42.16 2.02.53m-1.73 2.15c-.41 0-.81.08-1.19.24s-.72.39-1.01.68a5.33 5.33 0 0 0-1.36 2.55c-.28 1.08-.27 1.9.04 2.49s.89.87 1.75.87a3 3 0 0 0 2.18-.88 5.2 5.2 0 0 0 1.26-2.22l.08-.31c.29-1.11.26-1.94-.03-2.53-.31-.59-.89-.89-1.72-.89M26.85 7.81h-3.21l-1.13 4.28h3.21c1.01 0 1.81-.17 2.35-.52.57-.37.98-.95 1.13-1.63.2-.72.11-1.27-.27-1.62-.38-.33-1.07-.51-2.08-.51" style="fill:#072654"/>
                                <path style="fill:#3395ff" d="m18.4 0-5.64 21.47H8.89L12.7 6.93l-5.84 3.85L7.9 6.95z"/>
                            </svg>
                        </span>
                    </x-secondary-button>
                @endif

                @if ($paymentGateway->flutterwave_status)
                    <x-secondary-button wire:click="selectOnlineGateway('flutterwave')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'flutterwave'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg class="h-5 dark:mix-blend-plus-lighter" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 916.7 144.7"><path d="M280.5 33.8h16.1v82.9h-16.1zM359 87.3c0 11.4-7.4 16.6-17.2 16.6s-16.4-5.1-16.4-16V58.3h-16.1v33.3c0 16.6 10.4 26.3 27.7 26.3 10.9 0 16.9-4 21-8.5h.9l1.4 7.4h14.8V58.3H359zm158 17.9c-11.8 0-18.4-5.4-19.5-13.2h51.1c.2-1.6.4-3.3.3-4.9-.1-21-16-29.9-33-29.9-19.7 0-34.6 11.8-34.6 30.8 0 18.1 14.2 29.9 35.6 29.9 17.9 0 29.8-7.9 32.2-20.1h-15.9c-1.8 4.8-7.5 7.4-16.2 7.4m-1-35.3c10.3 0 16.2 4.6 17.2 11h-35.3c1.5-6.2 7.5-11 18.1-11m60.4-3.2h-1l-1.5-8.4h-14.6v58.4h16.1V91.6c0-11.3 6.5-17.6 18.7-17.6q3.3 0 6.6.6V58.3h-2.2c-10.9 0-17.5 2.3-22.1 8.4m103.3 31.8h-.9L665 62h-16.6l-13.5 36.4h-1.1L621 58.3h-16l19.7 58.4h17.5l14-37.2h1l13.8 37.2h17.6l19.7-58.4h-16zm92.7 1.2V80.2c0-15.9-13.4-23-30.1-23-17.7 0-28.8 8.4-30.3 21h16.1c1.2-5.5 5.8-8.5 14.2-8.5s14 3.2 14 9.6v1.5l-26.3 2c-12.1.9-21 6.3-21 17.8 0 11.8 10.2 17.4 25.1 17.4 12.1 0 19.4-3.4 23.9-8.4h.8c2.5 5.7 7.7 7.3 13.2 7.3h6.8V105h-1.5c-3.3-.2-4.9-1.8-4.9-5.3m-16.1-6.2c0 9.2-11 12.3-20.4 12.3-6.4 0-10.6-1.6-10.6-6.1 0-4 3.6-5.9 9-6.4l22.1-1.6zM832 58.3l-18.8 42.3h-1l-19.1-42.3h-17.4l27.2 58.4h19.3l27.1-58.4zm68.8 39.5c-2 4.8-7.7 7.4-16.3 7.4-11.8 0-18.4-5.4-19.5-13.2h51.1c.2-1.6.4-3.3.3-4.9-.1-21-16-29.9-33-29.9-19.7 0-34.5 11.8-34.5 30.8 0 18.1 14.2 29.9 35.6 29.9 17.9 0 29.8-7.9 32.2-20.1zm-17.4-27.9c10.3 0 16.2 4.6 17.2 11h-35.3c1.5-6.2 7.4-11 18.1-11M254.4 54c0-5.1 3.6-7.3 8.3-7.3 2.2 0 4.3.3 6.4.9l2.7-11.7c-3.9-1.4-8-2.1-12.1-2.1-11.9 0-21.5 6.3-21.5 19.4v5.1h-13.9v12.8h13.9v45.6h16.2V71.1h18.2V58.3h-18.2zm156.4-12.1h-15l-.8 16.5h-12.7v12.8h12.4V100c0 9.8 5 18 20 18 3.9 0 7.8-.4 11.6-1.3v-12.3c-2.2.5-4.4.8-6.7.8-8 0-8.8-4.6-8.8-8.1v-26h16V58.3h-16zm50.6 0h-14.9l-.8 16.5H433v12.8h12.4V100c0 9.8 5 18 20 18 3.9 0 7.7-.5 11.5-1.3v-12.3c-2.2.5-4.4.8-6.7.8-8 0-8.8-4.6-8.8-8.1v-26h16V58.3h-16.1V41.9zM0 31.6c0-9.4 2.7-17.4 8.5-23.1l10 10C7.4 29.6 17.1 64.1 48.8 95.8s66.2 41.4 77.3 30.3l10 10c-18.8 18.8-61.5 5.4-97.3-30.3C14 80.9 0 52.8 0 31.6" style="fill:#009a46"/><path d="M63.1 144.7c-9.4 0-17.4-2.7-23.1-8.5l10-10c11.1 11.1 45.6 1.4 77.3-30.3s41.4-66.2 30.3-77.3l10-10c18.8 18.8 5.4 61.5-30.3 97.3-24.9 24.8-53.1 38.8-74.2 38.8" style="fill:#ff5805"/><path d="M140.5 91.6C134.4 74.1 122 55.4 105.6 39 69.8 3.2 27.1-10.1 8.3 8.6 7 10 8.2 13.3 10.9 16s6.1 3.9 7.4 2.6c11.1-11.1 45.6-1.4 77.3 30.3 15 15 26.2 31.8 31.6 47.3 4.7 13.6 4.3 24.6-1.2 30.1-1.3 1.3-.2 4.6 2.6 7.4s6.1 3.9 7.4 2.6c9.6-9.7 11.2-25.6 4.5-44.7" style="fill:#f5afcb"/><path d="M167.5 8.6C157.9-1 142-2.6 122.9 4c-17.5 6.1-36.2 18.5-52.6 34.9-35.8 35.8-49.1 78.5-30.3 97.3 1.3 1.3 4.7.2 7.4-2.6s3.9-6.1 2.6-7.4c-11.1-11.1-1.4-45.6 30.3-77.3 15-15 31.8-26.2 47.2-31.6 13.6-4.7 24.6-4.3 30.1 1.2 1.3 1.3 4.6.2 7.4-2.6s3.9-5.9 2.5-7.3" style="fill:#ff9b00"/></svg>
                        </span>
                    </x-secondary-button>
                @endif

                @if ($paymentGateway->paypal_status)
                    <x-secondary-button wire:click="selectOnlineGateway('paypal')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'paypal'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg height="21" viewBox="0 0 916.7 144.7" class="h-6 w-22" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                  <style>
                                    .text { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 80px; font-weight: bold; }
                                    .dark-blue { fill: #002E6D; }
                                    .blue { fill: #009CDE; }
                                  </style>
                                </defs>
                                <path class="dark-blue" d="M60,30 h50 a30,30 0 0 1 0,60 h-35 l-10,60 h-30z"/>
                                <path class="blue" d="M75,40 h25 a20,20 0 0 1 0,40 h-20 l-8,40 h-20z"/>
                                <text x="140" y="95" class="text">
                                  <tspan class="dark-blue">Pay</tspan><tspan class="blue">Pal</tspan>
                                </text>
                              </svg>
                        </span>
                    </x-secondary-button>
                @endif

                @if($paymentGateway->payfast_status)
                    <x-secondary-button wire:click="selectOnlineGateway('payfast')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'payfast'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg width="24" height="24" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" fill="none"><g fill="#E63946"><ellipse cx="32" cy="12" rx="20" ry="8"/><path d="M12 12v4c0 4.42 8.95 8 20 8s20-3.58 20-8v-4c0 4.42-8.95 8-20 8s-20-3.58-20-8m0 12v4c0 4.42 8.95 8 20 8s20-3.58 20-8v-4c0 4.42-8.95 8-20 8s-20-3.58-20-8m0 12v4c0 4.42 8.95 8 20 8s20-3.58 20-8v-4c0 4.42-8.95 8-20 8s-20-3.58-20-8"/></g></svg>
                            @lang('modules.billing.payfast')
                        </span>
                    </x-secondary-button>
                @endif

                @if($paymentGateway->paystack_status)
                    <x-secondary-button wire:click="selectOnlineGateway('paystack')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'paystack'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#0AA5FF">
                                <path d="M2 3.6c0-.331.269-.6.6-.6H21.4c.331 0 .6.269.6.6v1.8a.6.6 0 0 1-.6.6H2.6a.6.6 0 0 1-.6-.6V3.6Zm0 4.8c0-.331.269-.6.6-.6H15.4c.331 0 .6.269.6.6v1.8a.6.6 0 0 1-.6.6H2.6a.6.6 0 0 1-.6-.6V8.4Zm0 4.8c0-.331.269-.6.6-.6H21.4c.331 0 .6.269.6.6v1.8a.6.6 0 0 1-.6.6H2.6a.6.6 0 0 1-.6-.6v-1.8Zm0 4.8c0-.331.269-.6.6-.6H15.4c.331 0 .6.269.6.6v1.8a.6.6 0 0 1-.6.6H2.6a.6.6 0 0 1-.6-.6v-1.8Z" fill-rule="evenodd"/>
                            </svg>
                            @lang('modules.billing.paystack')
                        </span>
                    </x-secondary-button>
                @endif

                @if($paymentGateway->xendit_status)
                    <x-secondary-button wire:click="selectOnlineGateway('xendit')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'xendit'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" id="Xendit--Streamline-Simple-Icons" height="24" width="24">
                                <path d="M11.781 2.743H7.965l-5.341 9.264 5.341 9.263 -1.312 2.266L0 12.007 6.653 0.464h6.454l-1.326 2.279Zm-5.128 2.28 1.312 -2.28L9.873 6.03 8.561 8.296 6.653 5.023Zm9.382 -2.28 1.312 2.28L7.965 21.27l-1.312 -2.279 9.382 -16.248Zm-5.128 20.793 1.298 -2.279h3.83L14.1 17.931l1.312 -2.267 1.926 3.337 4.038 -6.994 -5.341 -9.264L17.347 0.464 24 12.007l-6.653 11.529h-6.44Z" fill="#000000" stroke-width="1"></path>
                            </svg>
                            @lang('modules.billing.xendit')
                        </span>
                    </x-secondary-button>
                @endif

                @if($paymentGateway->epay_status)
                    <x-secondary-button wire:click="selectOnlineGateway('epay')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'epay'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="1.5" y="1.5" width="21" height="21" rx="3" ry="3" stroke="currentColor" stroke-width="2"/>
                                <path d="M9.3 7.8L6.1 11C5.7 11.4 5.7 12 6.1 12.4L9.3 15.6C9.7 16 10.3 16 10.7 15.6L13.9 12.4C14.3 12 14.3 11.4 13.9 11L10.7 7.8C10.3 7.4 9.7 7.4 9.3 7.8Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14.7 7.8L11.5 11C11.1 11.4 11.1 12 11.5 12.4L14.7 15.6C15.1 16 15.7 16 16.1 15.6L19.3 12.4C19.7 12 19.7 11.4 19.3 11L16.1 7.8C15.7 7.4 15.1 7.4 14.7 7.8Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            @lang('modules.billing.epay')
                        </span>
                    </x-secondary-button>
                @endif

                @if ($paymentGateway->mollie_status)
                    <x-secondary-button wire:click="selectOnlineGateway('mollie')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'mollie'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="#C6D300">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 1.5c5.799 0 10.5 4.701 10.5 10.5S17.799 22.5 12 22.5 1.5 17.799 1.5 12 6.201 1.5 12 1.5z"/>
                                <path d="M12 3C7.029 3 3 7.029 3 12s4.029 9 9 9 9-4.029 9-9-4.029-9-9-9zm0 1.5c4.136 0 7.5 3.364 7.5 7.5S16.136 21 12 21 4.5 17.636 4.5 13.5 7.864 4.5 12 4.5z"/>
                                <path d="M12 6c-3.314 0-6 2.686-6 6s2.686 6 6 6 6-2.686 6-6-2.686-6-6-6zm0 1.5c2.485 0 4.5 2.015 4.5 4.5S14.485 16.5 12 16.5 7.5 14.485 7.5 12 9.515 7.5 12 7.5z"/>
                            </svg>
                            @lang('modules.billing.mollie')
                        </span>
                    </x-secondary-button>
                @endif

                @if($paymentGateway->tap_status)
                    <x-secondary-button wire:click="selectOnlineGateway('tap')" 
                                        :class="{'border-skin-base bg-skin-base/10': $wire.selectedOnlineGateway === 'tap'}"
                                        class="w-full">
                        <span class="inline-flex items-center">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none">
                                <g fill="#E63946">
                                    <ellipse cx="32" cy="12" rx="20" ry="8"/>
                                    <path d="M12 12v4c0 4.42 8.95 8 20 8s20-3.58 20-8v-4c0 4.42-8.95 8-20 8s-20-3.58-20-8m0 12v4c0 4.42 8.95 8 20 8s20-3.58 20-8v-4c0 4.42-8.95 8-20 8s-20-3.58-20-8m0 12v4c0 4.42 8.95 8 20 8s20-3.58 20-8v-4c0 4.42-8.95 8-20 8s-20-3.58-20-8"/>
                                </g>
                            </svg>
                            @lang('modules.billing.tap')
                        </span>
                    </x-secondary-button>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="hidePaymentModal" wire:loading.attr="disabled" />
            @if($selectedOnlineGateway)
                <x-button class="ml-3" wire:click.once.prevent="processOnlinePayment" wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-not-allowed pointer-events-none" wire:target="processOnlinePayment">
                    <span class="inline-flex items-center">
                        <svg wire:loading wire:target="processOnlinePayment" class="w-4 h-4 mr-2 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        <span>
                            @lang('modules.order.payNow')
                        </span>
                    </span>
                </x-button>
            @endif
        </x-slot>
    </x-dialog-modal>

    {{-- Online payment gateway scripts support for kiosk (similar to shop cart) --}}
    @if($paymentGateway)
        {{-- Razorpay checkout script --}}
        @if($paymentGateway->razorpay_status)
            <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        @endif

        {{-- Epay checkout script --}}
        @if($paymentGateway->epay_status)
            @php
                $isSandbox = $paymentGateway->epay_mode === 'sandbox';
                $epayJsUrl = $isSandbox
                    ? 'https://test-epay.epayment.kz/payform/payment-api.js'
                    : 'https://epay.homebank.kz/payform/payment-api.js';
            @endphp
            <script src="{{ $epayJsUrl }}"></script>
        @endif

        {{-- Stripe checkout support for kiosk (similar to shop cart) --}}
        @if($paymentGateway->stripe_status)
            <script src="https://js.stripe.com/v3/"></script>

            <form action="{{ route('stripe.order_payment') }}" method="POST" id="order-payment-form" class="hidden">
                @csrf

                <input type="hidden" id="order_payment" name="order_payment">

                <div class="form-row">
                    <label for="card-element">
                        Credit or debit card
                    </label>
                    <div id="card-element">
                        <!-- A Stripe Element will be inserted here. -->
                    </div>

                    <!-- Used to display Element errors. -->
                    <div id="card-errors" role="alert"></div>
                </div>

                <button type="submit">Submit Payment</button>
            </form>

            @script
            <script>
                const kioskStripe = Stripe('{{ $paymentGateway->stripe_key }}');
                const kioskStripeElements = kioskStripe.elements({
                    currency: '{{ strtolower($restaurant->currency->currency_code) }}',
                });
                // Elements mounting is handled in the StripeController checkout page.
            </script>
            @endscript
        @endif
    @endif

    <!-- Offline Payment Methods Modal -->
    <x-dialog-modal wire:model.live="showOfflineModal" maxWidth="md">
        <x-slot name="title">
            {{ __('modules.billing.offlinePaymentMethod') }}
        </x-slot>

        <x-slot name="content">
            @if(!empty($offlinePaymentMethods) && count($offlinePaymentMethods) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($offlinePaymentMethods as $offlineMethod)
                        @php
                            // Livewire can hydrate ids as string, so avoid strict comparison issues
                            $isSelected = (string)($selectedOfflineMethodId ?? '') === (string)($offlineMethod->id ?? '');
                        @endphp
                        <button type="button"
                                wire:click="selectOfflineMethod({{ $offlineMethod->id }})"
                                class="w-full border-2 rounded-lg p-4 flex items-center justify-between transition-all duration-200
                                       hover:bg-gray-50 dark:hover:bg-gray-800
                                       {{ $isSelected ? 'border-skin-base bg-skin-base/10 dark:border-skin-base dark:bg-skin-base/20' : 'border-gray-200 dark:border-gray-600' }}">
                            <div class="flex items-center">
                                @if($isSelected)
                                    <div class="flex items-center justify-center w-8 h-8 mr-3 rounded-full bg-skin-base text-white">
                                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                             viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7" />
                                        </svg>
                
                                    </div>
                                @endif

                                <div class="text-left">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ $offlineMethod->name === 'cash' ? __('modules.order.payViaCash') : ($offlineMethod->name === 'bank_transfer' ? __('modules.billing.payOffline') : ucwords(str_replace('_', ' ', $offlineMethod->name)))}}
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-600">
                    {{ __('messages.noOfflinePaymentMethodFound') }}
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button-cancel wire:click="hideOfflineModal" wire:loading.attr="disabled" />
        </x-slot>
    </x-dialog-modal>

    @script
    <script>
        $wire.on('onlineGatewaySelected', (data) => {
            // Update Alpine.js paymentMethod state
            if (window.Alpine && window.Alpine.store) {
                // If using Alpine store, update it
            }
            // Also update the local paymentMethod if available in scope
            if (typeof paymentMethod !== 'undefined') {
                paymentMethod = 'online_' + data.gateway;
            }
        });

        $wire.on('kioskPaymentInitiated', (data) => {
            payViaRazorpay(data.payment, data.order);
        });

        $wire.on('kioskStripePaymentInitiated', (data) => {
            // Handle Stripe payment - similar to OrderDetail
            document.getElementById('order_payment').value = data.payment.id;
            document.getElementById('order-payment-form').submit();
        });

        $wire.on('kioskEpayPaymentInitiated', (data) => {
            payViaEpay(data.payment, data.order);
        });

        function payViaRazorpay(payment, order) {
            var options = {
                "key": "{{ $paymentGateway->razorpay_key ?? '' }}",
                "amount": (parseFloat(payment.amount) * 100),
                "currency": "{{ $restaurant->currency->currency_code }}",
                "description": "Order Payment",
                "image": "{{ $restaurant->logoUrl }}",
                "order_id": payment.razorpay_order_id,
                "handler": function(response) {
                    // Redirect to order confirmation after successful payment
                    window.location.href = "{{ route('kiosk.order-confirmation', ':uuid') }}".replace(':uuid', order.uuid);
                },
                "modal": {
                    "ondismiss": function() {
                        if (confirm("Are you sure, you want to close the form?")) {
                            console.log("Checkout form closed by the user");
                        }
                    }
                }
            };
            var rzp1 = new Razorpay(options);
            rzp1.on('payment.failed', function(response) {
                console.log(response);
                alert('Payment failed. Please try again.');
            });
            rzp1.open();
        }

        function payViaEpay(payment, order) {
            if (typeof halyk === 'undefined') {
                console.error('Epay library not loaded');
                alert('Payment gateway failed to load. Please refresh and try again.');
                return;
            }

            try {
                var paymentData = payment;
                var orderNumber = order.formatted_order_number || order.id || '';
                var descriptionText = "Order Payment #" + orderNumber;

                function getByteLength(str) {
                    return new TextEncoder().encode(str).length;
                }

                function truncateToBytes(str, maxBytes) {
                    var encoder = new TextEncoder();
                    var decoder = new TextDecoder();
                    var bytes = encoder.encode(str);
                    if (bytes.length <= maxBytes) {
                        return str;
                    }
                    var truncated = bytes.slice(0, maxBytes - 3);
                    return decoder.decode(truncated) + '...';
                }

                if (getByteLength(descriptionText) > 125) {
                    descriptionText = truncateToBytes(descriptionText, 125);
                }

                var locale = "{{ app()->getLocale() }}";
                var language = 'eng';
                if (locale === 'kaz' || locale === 'kz') {
                    language = 'kaz';
                } else if (locale === 'rus' || locale === 'ru') {
                    language = 'rus';
                }

                var authToken = null;
                try {
                    if (typeof paymentData.epay_access_token === 'string') {
                        authToken = JSON.parse(paymentData.epay_access_token);
                    } else {
                        authToken = paymentData.epay_access_token;
                    }
                } catch (e) {
                    console.error('Failed to parse auth token:', e);
                    alert('Payment token error. Please try again.');
                    return;
                }

                if (!authToken || !authToken.access_token) {
                    console.error('Invalid auth token:', authToken);
                    alert('Payment token is invalid. Please try again.');
                    return;
                }

                var amount = parseFloat(paymentData.amount);
                var formattedAmount = parseFloat(amount.toFixed(2));

                var paymentObject = {
                    invoiceId: paymentData.epay_invoice_id,
                    backLink: "{{ route('epay.success') }}",
                    failureBackLink: "{{ route('epay.cancel') }}",
                    postLink: "{{ route('epay.webhook', ['hash' => $restaurant->hash]) }}",
                    failurePostLink: "{{ route('epay.webhook', ['hash' => $restaurant->hash]) }}",
                    language: language,
                    description: descriptionText,
                    terminal: "{{ $paymentGateway->epay_mode === 'sandbox' ? $paymentGateway->test_epay_terminal_id : $paymentGateway->epay_terminal_id }}",
                    amount: formattedAmount,
                    currency: "{{ strtoupper($restaurant->currency->currency_code) }}",
                    auth: authToken,
                };

                if (order.customer) {
                    var customer = order.customer;
                    if (customer.phone) paymentObject.phone = customer.phone;
                    if (customer.name) paymentObject.name = customer.name;
                    if (customer.email) paymentObject.email = customer.email;
                }

                console.log('Calling halyk.pay() with:', paymentObject);
                halyk.pay(paymentObject);
            } catch (error) {
                console.error('Epay payment error:', error);
                alert('Payment initialization failed: ' + error.message);
            }
        }
    </script>
    @endscript
</div>
