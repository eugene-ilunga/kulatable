<div class="p-4 lg:p-6">
    @if($account && $settings)
        <!-- Tier Badge (if points enabled) -->
        @if($enablePoints && $currentTier)
        <div class="mb-6 p-6 bg-gradient-to-r rounded-lg shadow-sm border-2" style="background: linear-gradient(135deg, {{ $currentTier->color }}15 0%, {{ $currentTier->color }}25 100%); border-color: {{ $currentTier->color }}40;">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg" style="background-color: {{ $currentTier->color }};">
                        {{ strtoupper(substr($currentTier->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.currentTier') }}</div>
                        <div class="text-2xl font-bold" style="color: {{ $currentTier->color }};">{{ $currentTier->name }}</div>
                        @php
                            $earningBonus = max(0, ((float)($currentTier->earning_multiplier ?? 1) - 1) * 100);
                            $redemptionBonus = max(0, ((float)($currentTier->redemption_multiplier ?? 1) - 1) * 100);
                            $earningText = $earningBonus > 0 ? __('loyalty::app.earn') . ' ' . rtrim(rtrim(number_format($earningBonus, 1), '0'), '.') . '% ' . __('loyalty::app.more') . ' ' . __('loyalty::app.points') : null;
                            $redemptionText = $redemptionBonus > 0 ? __('loyalty::app.get') . ' ' . rtrim(rtrim(number_format($redemptionBonus, 1), '0'), '.') . '% ' . __('loyalty::app.more') . ' ' . __('loyalty::app.valueOnRedemption') : null;
                            $tierBenefitText = trim(implode(', ', array_filter([$earningText, $redemptionText])));
                        @endphp
                        @if($tierBenefitText !== '')
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $tierBenefitText }}</div>
                        @elseif($currentTier->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $currentTier->description }}</div>
                        @endif
                    </div>
                </div>
                @if($nextTier)
                    <div class="text-right">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.nextTier') }}: <span class="font-semibold" style="color: {{ $nextTier->color }};">{{ $nextTier->name }}</span></div>
                        @if($pointsToNextTier !== null)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('loyalty::app.pointsToNextTier') }}: <span class="font-semibold">{{ number_format($pointsToNextTier) }}</span> {{ __('loyalty::app.points') }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-right">
                        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('loyalty::app.highestTier') }}</div>
                    </div>
                @endif
            </div>

            @if($nextTier && $pointsToNextTier !== null)
            <div class="mt-4">
                <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                    <span>{{ __('loyalty::app.tierProgress') }}</span>
                    <span>{{ number_format($tierProgress, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300" style="width: {{ $tierProgress }}%; background-color: {{ $currentTier->color }};"></div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Account Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            @if($enablePoints)
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.pointsBalance') }}</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($pointsBalance) }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.pointsValue') }}</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            @if($restaurant && $restaurant->currency_id)
                                @if($pointsValue > 0)
                                    {{ currency_format($pointsValue, $restaurant->currency_id) }}
                                @else
                                    {{ currency_format(0, $restaurant->currency_id) }}
                                @endif
                            @else
                                @if($pointsValue > 0)
                                    {{ number_format($pointsValue, 2) }}
                                @else
                                    {{ number_format(0, 2) }}
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($enableStamps)
            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.totalStamps') }}</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                    @php
                        $totalStamps = 0;
                        foreach($customerStamps as $stampData) {
                            $totalStamps += $stampData['available_stamps'] ?? 0;
                        }
                    @endphp
                    {{ number_format($totalStamps) }}
                </div>
            </div>
            @endif

            <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('loyalty::app.accountCreated') }}</div>
                <div class="text-lg font-semibold text-gray-900 dark:text-white mt-1">{{ $account->created_at->format('M d, Y') }}</div>
            </div>
        </div>

        <!-- Stamps Section (if stamps enabled) -->
        @if($enableStamps && count($customerStamps) > 0)
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.myStampCards') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ __('loyalty::app.stampCardsDescription') }}</p>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-4">
                    @foreach($customerStamps as $stampData)
                        @php
                            $rule = $stampData['rule'];
                            $stampsEarned = $stampData['stamps_earned'] ?? 0;
                            $stampsRedeemed = $stampData['stamps_redeemed'] ?? 0;
                            $availableStamps = $stampData['available_stamps'];
                            $stampsRequired = $stampData['stamps_required'];
                            $progress = $stampsRequired > 0 ? min(100, ($availableStamps / $stampsRequired) * 100) : 0;
                            $canRedeem = $stampData['can_redeem'];
                        @endphp
                        <div class="p-4 border-2 rounded-lg {{ $canRedeem ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-gray-600' }}">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $rule->menuItem->item_name ?? __('loyalty::app.unknownItem') }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('loyalty::app.stampsRequired') }}: {{ $stampsRequired }}</p>
                                </div>
                            </div>

                            <div class="mb-2">
                                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                                    <div class="flex justify-between mb-1">
                                        <span>{{ __('loyalty::app.stampsEarned') }}: {{ $stampsEarned }}</span>
                                        <span>{{ number_format($progress) }}%</span>
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-500">
                                        {{ __('loyalty::app.availableStamps') }}: {{ $availableStamps }}/{{ $stampsRequired }}
                                        @if($stampsRedeemed > 0)
                                            <span class="text-gray-400">({{ __('loyalty::app.redeemed') }}: {{ $stampsRedeemed }})</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full transition-all duration-300 {{ $canRedeem ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $progress }}%;"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-center space-x-2 mt-3">
                                @for($i = 1; $i <= $stampsRequired; $i++)
                                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center {{ $i <= $availableStamps ? ($canRedeem ? 'bg-green-500 border-green-600' : 'bg-blue-500 border-blue-600') : 'bg-gray-200 dark:bg-gray-700 border-gray-300 dark:border-gray-600' }}">
                                        @if($i <= $availableStamps)
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <span class="text-xs text-gray-400">✓</span>
                                        @endif
                                    </div>
                                @endfor
                            </div>

                            @if($canRedeem)
                                <div class="mt-3 text-center">
                                    <span class="text-xs font-semibold text-green-700 dark:text-green-400">{{ __('loyalty::app.readyToRedeem') }}</span>
                                </div>
                            @else
                                <div class="mt-3 text-center">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('loyalty::app.stampsNeeded') }}: {{ $stampsRequired - $availableStamps }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Ledger Entries (Points only) -->
        @if($enablePoints)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('loyalty::app.loyaltyLedger') }}</h3>
            </div>

            @if($ledgerEntries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('loyalty::app.transactionDate') }}</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('loyalty::app.transactionType') }}</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('loyalty::app.pointsChange') }}</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('loyalty::app.orderNumber') }}</th>
                                <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ __('loyalty::app.reason') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach($ledgerEntries as $entry)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="py-3 px-4 text-sm text-gray-900 dark:text-white">
                                        {{ $entry->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td class="py-3 px-4 text-sm">
                                        @if($entry->type == 'EARN')
                                            <span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                {{ __('loyalty::app.earn') }}
                                            </span>
                                        @elseif($entry->type == 'REDEEM')
                                            <span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                {{ __('loyalty::app.redeem') }}
                                            </span>
                                        @elseif($entry->type == 'ADJUST')
                                            <span class="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ __('loyalty::app.adjust') }}
                                            </span>
                                        @elseif($entry->type == 'EXPIRE')
                                            <span class="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                {{ __('loyalty::app.expire') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-sm font-semibold {{ $entry->points > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $entry->points > 0 ? '+' : '' }}{{ number_format($entry->points) }}
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-900 dark:text-white">
                                        @if($entry->order)
                                            #{{ $entry->order->order_number ?? $entry->order->id }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $entry->reason ?? '--' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $ledgerEntries->links() }}
                </div>
            @else
                <div class="p-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('loyalty::app.noLedgerEntries') }}</p>
                </div>
            @endif
        </div>
        @endif
    @else
        <div class="p-8 text-center">
            @if(!customer())
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('Please log in to view your loyalty account.') }}</p>
                {{-- <a href="{{ route('login', [($restaurant ? $restaurant->hash : '')]) }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    {{ __('Login') }}
                </a> --}}
            @elseif(!$account || !$settings)
                <p class="text-gray-500 dark:text-gray-400">{{ __('loyalty::app.loyaltyProgramNotEnabled') }}</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                    {{ __('The loyalty program is not available for this restaurant.') }}
                </p>
            @else
                <p class="text-gray-500 dark:text-gray-400">{{ __('loyalty::app.loyaltyProgramNotEnabled') }}</p>
            @endif
        </div>
    @endif
</div>
