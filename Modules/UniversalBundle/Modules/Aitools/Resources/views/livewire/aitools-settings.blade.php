<div>
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-4 text-xl font-semibold dark:text-white">@lang('aitools::app.settings.title')</h3>
        <x-help-text class="mb-6">@lang('aitools::app.settings.description')</x-help-text>

        <form wire:submit="save">
            <div class="space-y-6">
                <!-- Enable AI -->
                <div class="flex items-center">
                    <x-checkbox name="aiEnabled" id="aiEnabled" wire:model="aiEnabled" />
                    <x-label for="aiEnabled" class="ml-2">
                        @lang('aitools::app.settings.enableAi')
                    </x-label>
                </div>

                @if($aiEnabled)
                <!-- Package Monthly Limit Information -->
                @if($packageInfo)
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3">@lang('aitools::app.settings.packageLimit')</h4>
                    <div class="space-y-2">
                        <p class="text-sm text-blue-800 dark:text-blue-400">
                            <span class="font-medium">@lang('aitools::app.settings.package'):</span> {{ $packageInfo['package_name'] }}
                        </p>

                        <!-- Tokens Consumed Summary -->
                        <div class="mt-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-blue-300 dark:border-blue-700">
                            <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">@lang('aitools::app.settings.tokensConsumed')</p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-300">
                                {{ number_format($packageInfo['used']) }} <span class="text-sm font-normal text-blue-700 dark:text-blue-400">tokens</span>
                            </p>
                        </div>

                        @if($packageInfo['unlimited'])
                        <p class="text-sm text-blue-800 dark:text-blue-400 mt-3">
                            <span class="font-medium">@lang('aitools::app.settings.monthlyLimit'):</span> @lang('modules.billing.unlimited')
                        </p>
                        @else
                        <div class="grid grid-cols-3 gap-4 mt-3">
                            <div class="bg-white dark:bg-gray-800 rounded p-3 border border-blue-200 dark:border-blue-700">
                                <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">@lang('aitools::app.settings.monthlyLimit')</p>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-300">{{ number_format($packageInfo['monthly_limit']) }} <span class="text-xs font-normal">tokens</span></p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-3 border border-blue-200 dark:border-blue-700">
                                <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">@lang('aitools::app.settings.used')</p>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-300">{{ number_format($packageInfo['used']) }} <span class="text-xs font-normal">tokens</span></p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded p-3 border border-blue-200 dark:border-blue-700">
                                <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">@lang('aitools::app.settings.remaining')</p>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-300">{{ number_format($packageInfo['remaining']) }} <span class="text-xs font-normal">tokens</span></p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                            <div class="w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
                                <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full" style="width: {{ $packageInfo['monthly_limit'] > 0 ? min(100, ($packageInfo['used'] / $packageInfo['monthly_limit']) * 100) : 0 }}%"></div>
                            </div>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                {{ number_format(($packageInfo['monthly_limit'] > 0 ? ($packageInfo['used'] / $packageInfo['monthly_limit']) * 100 : 0), 1) }}% @lang('aitools::app.settings.usedThisMonth')
                            </p>
                        </div>
                        @endif
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                            @lang('aitools::app.settings.packageLimitNote')
                        </p>
                    </div>
                </div>
                @endif

                <!-- Allowed Roles -->
                <div>
                    <x-label :value="__('aitools::app.settings.allowedRoles')" />
                    <p class="mt-1 mb-3 text-sm text-gray-500">@lang('aitools::app.settings.allowedRolesDescription')</p>
                    <div class="space-y-2">
                        @foreach($availableRoles as $role)
                        <div class="flex items-center">
                            <x-checkbox
                                id="role_{{ $role }}"
                                wire:click="toggleRole('{{ $role }}')"
                                :checked="in_array($role, $aiAllowedRoles)"
                            />
                            <x-label for="role_{{ $role }}" class="ml-2 capitalize">
                                {{ $role }}
                            </x-label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="mt-6">
                    <x-button type="submit">@lang('aitools::app.settings.saveSettings')</x-button>
                </div>
            </div>
        </form>
    </div>
</div>

