<div>
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-4 text-xl font-semibold dark:text-white">@lang('aitools::app.superadmin.title')</h3>
        <x-help-text class="mb-6">@lang('aitools::app.superadmin.description')</x-help-text>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button
                    type="button"
                    wire:click="switchTab('settings')"
                    class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'settings' ? 'border-skin-base text-skin-base' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    @lang('aitools::app.superadmin.aiSettings')
                </button>
                <button
                    type="button"
                    wire:click="switchTab('tokens')"
                    class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'tokens' ? 'border-skin-base text-skin-base' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    @lang('aitools::app.superadmin.tokenConsumed')
                </button>
            </nav>
        </div>

        @if($activeTab === 'settings')
        <form wire:submit="save">
            <div class="space-y-6">
                <!-- OpenAI API Key -->
                <div>
                    <x-label for="openaiApiKey" :value="__('aitools::app.superadmin.openaiApiKey')" />
                    <div class="flex items-center gap-3 mt-2 w-full">
                        <div class="flex-1 min-w-0">
                            <x-input-password
                                id="openaiApiKey"
                                class="w-full"
                                type="password"
                                wire:model.defer="openaiApiKey"
                                placeholder="sk-..."
                                autocomplete="off"
                            />
                        </div>
                        <x-button
                            type="button"
                            wire:click="testApiKey"
                            wire:loading.attr="disabled"
                            class="flex-shrink-0 whitespace-nowrap"
                            :disabled="$testStatus === 'testing'"
                        >
                            <span wire:loading.remove wire:target="testApiKey">
                                @lang('aitools::app.superadmin.testApiKey')
                            </span>
                            <span wire:loading wire:target="testApiKey">
                                @lang('aitools::app.superadmin.testingApiKey')
                            </span>
                        </x-button>
                    </div>
                    <x-input-error for="openaiApiKey" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @lang('aitools::app.superadmin.openaiApiKeyDescription')
                        <a href="https://platform.openai.com/api-keys" target="_blank" class="text-skin-base hover:underline">@lang('aitools::app.superadmin.openaiApiKeyLink')</a>.
                    </p>
                    @if($testStatus === 'success')
                    <p class="mt-2 text-sm text-green-600 dark:text-green-400">
                        ✓ {{ $testMessage }}
                    </p>
                    @elseif($testStatus === 'error')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                        ✗ {{ $testMessage }}
                    </p>
                    @elseif($openaiApiKey)
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        @lang('aitools::app.superadmin.apiKeyConfigured')
                    </p>
                    @else
                    <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                        ⚠ @lang('aitools::app.superadmin.apiKeyNotConfigured')
                    </p>
                    @endif
                </div>

                <!-- OpenAI Organization ID (Optional) -->
                <div>
                    <x-label for="openaiOrganizationId" :value="__('aitools::app.superadmin.openaiOrganizationId')" />
                    <x-input
                        id="openaiOrganizationId"
                        class="block mt-2 w-full"
                        type="text"
                        wire:model.defer="openaiOrganizationId"
                        placeholder="org-..."
                        autocomplete="off"
                    />
                    <x-input-error for="openaiOrganizationId" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @lang('aitools::app.superadmin.openaiOrganizationIdDescription')
                        <a href="https://platform.openai.com/account/org-settings" target="_blank" class="text-skin-base hover:underline">@lang('aitools::app.superadmin.openaiOrganizationIdLink')</a>.
                    </p>
                </div>

                <!-- API Key Details -->
                @if($openaiApiKey && ($apiKeyDetails || $loadingDetails))
                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold dark:text-white">@lang('aitools::app.superadmin.apiKeyDetails')</h4>
                        <x-button
                            type="button"
                            wire:click="fetchApiKeyDetails"
                            wire:loading.attr="disabled"
                            class="flex-shrink-0"
                            size="sm"
                        >
                            <span wire:loading.remove wire:target="fetchApiKeyDetails">
                                @lang('aitools::app.superadmin.refreshDetails')
                            </span>
                            <span wire:loading wire:target="fetchApiKeyDetails">
                                @lang('aitools::app.superadmin.refreshing')
                            </span>
                        </x-button>
                    </div>

                    @if($loadingDetails)
                    <div class="flex items-center justify-center py-4">
                        <svg class="animate-spin h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.loadingDetails')</span>
                    </div>
                    @elseif($apiKeyDetails)
                    <div class="space-y-4">
                        <!-- Organization Information -->
                        @if(isset($apiKeyDetails['organization']))
                        <div class="p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                            <h5 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">@lang('aitools::app.superadmin.organization')</h5>
                            <div class="space-y-1 text-sm">
                                @if(isset($apiKeyDetails['organization']['name']))
                                <p><span class="text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.name'):</span> <span class="font-medium dark:text-white">{{ $apiKeyDetails['organization']['name'] }}</span></p>
                                @endif
                                @if(isset($apiKeyDetails['organization']['id']))
                                <p><span class="text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.organizationId'):</span> <span class="font-mono text-xs dark:text-white">{{ $apiKeyDetails['organization']['id'] }}</span></p>
                                @endif
                                @if(isset($apiKeyDetails['organization']['is_default']))
                                <p><span class="text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.isDefault'):</span> <span class="dark:text-white">{{ $apiKeyDetails['organization']['is_default'] ? __('app.yes') : __('app.no') }}</span></p>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Account Information -->
                        @if(isset($apiKeyDetails['account']))
                        <div class="p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">
                            <h5 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">@lang('aitools::app.superadmin.account')</h5>
                            <div class="space-y-1 text-sm">
                                @if(isset($apiKeyDetails['account']['id']))
                                <p><span class="text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.accountId'):</span> <span class="font-mono text-xs dark:text-white">{{ $apiKeyDetails['account']['id'] }}</span></p>
                                @endif
                                @if(isset($apiKeyDetails['account']['email']))
                                <p><span class="text-gray-600 dark:text-gray-400">@lang('app.email'):</span> <span class="dark:text-white">{{ $apiKeyDetails['account']['email'] }}</span></p>
                                @endif
                                @if(isset($apiKeyDetails['account']['name']))
                                <p><span class="text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.name'):</span> <span class="dark:text-white">{{ $apiKeyDetails['account']['name'] }}</span></p>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Token Statistics Across All Restaurants -->
                        <div
                            class="p-4 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700"
                            x-data="{ loaded: false }"
                            x-init="
                                if (!loaded) {
                                    loaded = true;
                                    setTimeout(() => {
                                        @this.loadTokenStatistics();
                                    }, 100);
                                }
                            "
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="font-semibold text-sm text-gray-700 dark:text-gray-300">@lang('aitools::app.superadmin.totalTokenUsage')</h5>
                                <button
                                    type="button"
                                    wire:click="loadTokenStatistics"
                                    wire:loading.attr="disabled"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                    title="@lang('aitools::app.superadmin.refresh')"
                                >
                                    <svg wire:loading.remove wire:target="loadTokenStatistics" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <svg wire:loading wire:target="loadTokenStatistics" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>

                            @if($loadingTokenStatistics && $totalTokensConsumed == 0 && $totalTokensLimit == 0)
                            <div class="flex items-center justify-center py-8">
                                <svg class="animate-spin h-5 w-5 text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.loadingTokenStatistics')</span>
                            </div>
                            @else
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mb-2">@lang('aitools::app.superadmin.tokensConsumed')</p>
                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-300">
                                        {{ number_format($totalTokensConsumed) }}
                                        <span class="text-sm font-normal text-blue-700 dark:text-blue-400">tokens</span>
                                    </p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                        @lang('aitools::app.superadmin.acrossAllRestaurants')
                                    </p>
                                </div>

                                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-700">
                                    <p class="text-xs text-green-600 dark:text-green-400 mb-2">@lang('aitools::app.superadmin.tokensRemaining')</p>
                                    @if($totalTokensLimit > 0)
                                        @php
                                            $remaining = max(0, $totalTokensLimit - $totalTokensConsumed);
                                        @endphp
                                        <p class="text-2xl font-bold text-green-900 dark:text-green-300">
                                            {{ number_format($remaining) }}
                                            <span class="text-sm font-normal text-green-700 dark:text-green-400">tokens</span>
                                        </p>
                                        <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                                            @lang('aitools::app.superadmin.ofTotalLimit', ['total' => number_format($totalTokensLimit)])
                                        </p>
                                    @else
                                        <p class="text-2xl font-bold text-green-900 dark:text-green-300">
                                            @lang('modules.billing.unlimited')
                                        </p>
                                        <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                                            @lang('aitools::app.superadmin.unlimitedPackages')
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($totalTokensLimit > 0)
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs text-gray-600 dark:text-gray-400">@lang('aitools::app.superadmin.usageProgress')</p>
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ number_format(($totalTokensLimit > 0 ? ($totalTokensConsumed / $totalTokensLimit) * 100 : 0), 1) }}%
                                    </p>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                    <div class="bg-blue-600 dark:bg-blue-400 h-3 rounded-full transition-all"
                                         style="width: {{ min(100, ($totalTokensLimit > 0 ? ($totalTokensConsumed / $totalTokensLimit) * 100 : 0)) }}%"></div>
                                </div>
                            </div>
                            @endif
                            @endif
                        </div>

                        <!-- Error Messages -->
                        @if(isset($apiKeyDetails['errors']) && count($apiKeyDetails['errors']) > 0)
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                            <h5 class="font-semibold text-sm text-red-700 dark:text-red-400 mb-2">@lang('aitools::app.superadmin.errors')</h5>
                            <div class="space-y-1 text-sm">
                                @foreach($apiKeyDetails['errors'] as $key => $error)
                                <p class="text-red-600 dark:text-red-400">
                                    <span class="font-medium">{{ ucfirst($key) }}:</span> {{ $error }}
                                </p>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(isset($apiKeyDetails['error']))
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                            <p class="text-sm text-red-600 dark:text-red-400">@lang('aitools::app.superadmin.errorFetchingDetails'): {{ $apiKeyDetails['error'] }}</p>
                        </div>
                        @endif

                        <!-- Debug Information (only show if there are errors or no data) -->
                        @if(isset($apiKeyDetails['debug']) && (count($apiKeyDetails['errors'] ?? []) > 0 || (!isset($apiKeyDetails['tokenStats']) && !isset($apiKeyDetails['models']))))
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded border border-yellow-200 dark:border-yellow-800">
                            <h5 class="font-semibold text-xs text-yellow-700 dark:text-yellow-400 mb-2">@lang('aitools::app.superadmin.debugInfo')</h5>
                            <div class="space-y-1 text-xs text-yellow-600 dark:text-yellow-400">
                                @foreach($apiKeyDetails['debug'] as $key => $value)
                                <p><span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span> {{ $value }}</p>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Note about balance -->
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800">
                            <p class="text-xs text-blue-600 dark:text-blue-400">
                                <strong>@lang('aitools::app.superadmin.note'):</strong> @lang('aitools::app.superadmin.balanceNote')
                                <a href="https://platform.openai.com/usage" target="_blank" class="underline">@lang('aitools::app.superadmin.openaiDashboard')</a>.
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <div class="mt-6">
                    <x-button type="submit">@lang('aitools::app.superadmin.saveSettings')</x-button>
                </div>
            </div>
        </form>
        @elseif($activeTab === 'tokens')
        <!-- Token Consumption Tab -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                        @lang('aitools::app.superadmin.currentMonthData')
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ \Carbon\Carbon::now()->format('F Y') }}
                    </p>
                </div>
                <x-button
                    type="button"
                    wire:click="loadRestaurants"
                    wire:loading.attr="disabled"
                    size="sm"
                >
                    <span wire:loading.remove wire:target="loadRestaurants">
                        @lang('aitools::app.superadmin.refresh')
                    </span>
                    <span wire:loading wire:target="loadRestaurants">
                        @lang('aitools::app.superadmin.refreshing')
                    </span>
                </x-button>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                @lang('aitools::app.superadmin.tokenConsumptionDescription')
            </p>

            @if(count($restaurants) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('aitools::app.superadmin.restaurant')
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('aitools::app.superadmin.package')
                                    </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('aitools::app.superadmin.currentMonthTokens')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('aitools::app.superadmin.tokenLimit')
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                @lang('aitools::app.superadmin.remaining')
                            </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('aitools::app.superadmin.actions')
                                    </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($restaurants as $restaurant)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $restaurant['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $restaurant['package_name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <span class="font-semibold">{{ number_format($restaurant['tokens_used']) }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-xs">tokens</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($restaurant['unlimited'])
                                    <span class="text-green-600 dark:text-green-400">@lang('modules.billing.unlimited')</span>
                                @else
                                    {{ number_format($restaurant['token_limit']) }} <span class="text-gray-500 dark:text-gray-400 text-xs">tokens</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($restaurant['unlimited'])
                                    <span class="text-green-600 dark:text-green-400">@lang('modules.billing.unlimited')</span>
                                @else
                                    <span class="font-semibold">{{ number_format($restaurant['remaining']) }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 text-xs">tokens</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button
                                    type="button"
                                    wire:click="showHistory({{ $restaurant['id'] }})"
                                    class="text-skin-base hover:text-skin-base/[.8] font-medium"
                                >
                                    @lang('aitools::app.superadmin.viewHistory')
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">@lang('aitools::app.superadmin.noRestaurantsWithAI')</p>
            </div>
            @endif
        </div>
        @endif
    </div>

    <!-- Token History Modal -->
    @if($showHistoryModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeHistoryModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                                @lang('aitools::app.superadmin.tokenUsageHistory')
                            </h3>
                            @if($selectedRestaurantId)
                                @php
                                    $restaurant = \App\Models\Restaurant::find($selectedRestaurantId);
                                @endphp
                                @if($restaurant)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $restaurant->name }}
                                    </p>
                                @endif
                            @endif
                        </div>
                        <button
                            type="button"
                            wire:click="closeHistoryModal"
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    @if(count($tokenHistory) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('aitools::app.superadmin.month')
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('aitools::app.superadmin.tokensConsumed')
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('aitools::app.superadmin.tokenLimit')
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        @lang('aitools::app.superadmin.usage')
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($tokenHistory as $history)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::createFromFormat('Y-m', $history['month'])->format('F Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        <span class="font-semibold">{{ number_format($history['tokens_used']) }}</span>
                                        <span class="text-gray-500 dark:text-gray-400 text-xs">tokens</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        @if($history['unlimited'])
                                            <span class="text-green-600 dark:text-green-400">@lang('modules.billing.unlimited')</span>
                                        @else
                                            {{ number_format($history['token_limit']) }} <span class="text-gray-500 dark:text-gray-400 text-xs">tokens</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if(!$history['unlimited'] && $history['token_limit'] > 0)
                                            @php
                                                $percentage = ($history['tokens_used'] / $history['token_limit']) * 100;
                                            @endphp
                                            <div class="flex items-center">
                                                <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                                    <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full" style="width: {{ min(100, $percentage) }}%"></div>
                                                </div>
                                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">@lang('aitools::app.superadmin.noTokenHistory')</p>
                    </div>
                    @endif
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button
                        type="button"
                        wire:click="closeHistoryModal"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-skin-base text-base font-medium text-white hover:bg-skin-base/[.8] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-skin-base sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        @lang('aitools::app.superadmin.close')
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

