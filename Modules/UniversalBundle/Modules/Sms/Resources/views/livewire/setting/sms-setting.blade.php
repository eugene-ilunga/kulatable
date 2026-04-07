<div class="p-3 sm:p-4 bg-white block dark:bg-gray-800 dark:border-gray-700">
    <div class="mb-4">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('sms::modules.menu.smsSettings') }}</h3>
    </div>

    <!-- SMS Usage Widgets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">

        <!-- Widget 1: SMS Status -->
        <div class="items-center justify-between p-3 bg-white border border-gray-200 rounded-md shadow-sm sm:flex dark:border-gray-700 sm:p-4 dark:bg-gray-800">
            <div class="w-full">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">@lang('sms::modules.package.packageLimit')</h3>
                    @if($packageSmsCount == -1)
                        <span class="bg-green-100 uppercase text-green-800 text-[10px] font-medium px-2 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                            @lang('sms::modules.package.unlimited')
                        </span>
                    @else
                        @if($isSmsLimitReached)
                            <span class="bg-red-100 uppercase text-red-800 text-[10px] font-medium px-2 py-0.5 rounded dark:bg-red-900 dark:text-red-300">
                                @lang('sms::modules.package.exhausted')
                            </span>
                        @else
                            <span class="bg-blue-100 uppercase text-blue-800 text-[10px] font-medium px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                @lang('sms::modules.package.active')
                            </span>
                        @endif
                    @endif
                </div>
                @if($packageSmsCount == -1)
                    <span class="text-xl font-bold leading-tight text-gray-900 sm:text-2xl dark:text-white">∞</span>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        @lang('sms::modules.package.unlimitedMessagesAllowed')
                    </p>
                @else
                    <span class="text-xl font-bold leading-tight text-gray-900 sm:text-2xl dark:text-white">{{ $packageSmsCount }}</span>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        @lang('sms::modules.package.totalSmsInPackage')
                    </p>
                @endif
            </div>
        </div>

        <!-- Widget 2: Used SMS Count -->
        <div class="items-center justify-between p-3 bg-white border border-gray-200 rounded-md shadow-sm sm:flex dark:border-gray-700 sm:p-4 dark:bg-gray-800">
            <div class="w-full">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('sms::modules.package.usedSmsCount') }}</h3>
                <span class="text-xl font-bold leading-tight text-gray-900 sm:text-2xl dark:text-white">{{ $usedSmsCount }}</span>
                <p class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    @if($packageSmsCount != -1)
                        @php
                            $usagePercent = $packageSmsCount > 0 ? round(($usedSmsCount / $packageSmsCount) * 100, 1) : 0;
                        @endphp
                        <span class="inline-flex items-center gap-0.5 {{ $usagePercent > 80 ? 'text-red-500 dark:text-red-400' : 'text-blue-500 dark:text-blue-400' }}">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                @if($usagePercent > 80)
                                    <path clip-rule="evenodd" fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z"></path>
                                @else
                                    <path clip-rule="evenodd" fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0110 17z"></path>
                                @endif
                            </svg>
                            {{ $usagePercent }}%
                        </span>
                        <span>{{ __('sms::modules.package.ofPackageUsed') }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Important Information Widget -->
    <div class="mb-4">
        <x-alert type="info" class="mb-0 py-2 px-3 text-sm [&_p]:text-sm">
            {{ __('sms::modules.alerts.mobileNumberFormat') }}
        </x-alert>
    </div>

    <!-- SMS Settings Form -->
    <div class="grid grid-cols-1">
        <form wire:submit="submitForm" class="space-y-4">
            @if(!empty($restaurantAndroidGatewayEnabled) && $restaurantAndroidGatewayEnabled)
            <div class="p-3 sm:p-4 mb-3 bg-white border border-gray-200 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('sms::modules.form.restaurantAndroidSmsGatewayTitle') }}</h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 mb-3 leading-snug">{{ __('sms::modules.form.restaurantAndroidSmsGatewayIntro') }}</p>
                <div class="space-y-3">
                    <div>
                        <x-label for="restaurant_android_sms_gateway_base_url" value="{{ __('sms::modules.form.androidSmsGatewayMessageUrl') }}" />
                        <x-input id="restaurant_android_sms_gateway_base_url" wire:model="restaurant_android_sms_gateway_base_url" class="block mt-1 w-full text-sm" type="url" placeholder="{{ __('sms::placeholders.androidSmsGatewayMessageUrl') }}" />
                        <p class="mt-0.5 text-[11px] leading-snug text-gray-500 dark:text-gray-400">{{ __('sms::modules.form.androidSmsGatewayMessageUrlHelp') }}</p>
                        <x-input-error for="restaurant_android_sms_gateway_base_url" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-label for="restaurant_android_sms_gateway_username" value="{{ __('sms::modules.form.androidSmsGatewayUsername') }}" />
                            <x-input id="restaurant_android_sms_gateway_username" wire:model="restaurant_android_sms_gateway_username" class="block mt-1 w-full text-sm" type="text" autocomplete="off" />
                            <x-input-error for="restaurant_android_sms_gateway_username" class="mt-1" />
                        </div>
                        <div>
                            <x-label for="restaurant_android_sms_gateway_password" value="{{ __('sms::modules.form.androidSmsGatewayPassword') }}" />
                            <x-input-password id="restaurant_android_sms_gateway_password" wire:model="restaurant_android_sms_gateway_password" class="block mt-1 w-full text-sm" placeholder="{{ __('sms::modules.form.leaveBlankToKeepPassword') }}" />
                            <x-input-error for="restaurant_android_sms_gateway_password" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="p-3 sm:p-4 mb-0 bg-white border border-gray-200 rounded-md shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flow-root">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($notificationSettings as $key => $item)
                        <div class="flex items-center justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                            <div class="flex flex-col flex-grow min-w-0">
                                <div class="flex items-center flex-wrap gap-1.5">
                                    <div class="text-base font-semibold text-gray-900 dark:text-white">@lang('sms::modules.notifications.' . $item->type)</div>

                                    @if(sms_setting()->vonage_status || sms_setting()->msg91_status || sms_setting()->android_sms_gateway_status)
                                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-semibold text-white bg-skin-base rounded">
                                            {{ $smsCounts[$item->type] ?? 0 }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">
                                    @lang('sms::modules.notifications.' . $item->type.'_info')
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if(sms_setting()->vonage_status || sms_setting()->msg91_status || sms_setting()->android_sms_gateway_status)
                                    <button type="button"
                                        class="px-2 py-0.5 text-xs font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-800/50"
                                        wire:click="openViewModal('{{ $item->type }}')">
                                        {{ __('app.view') }}
                                    </button>
                                @endif

                                <label for="checkbox_{{ $item->type }}" class="relative flex items-center cursor-pointer"
                                    wire:key='send_email_{{ microtime() }}'>
                                    <input type="checkbox" id="checkbox_{{ $item->type }}" @checked($sendEmail[$key])
                                        wire:model.live='sendEmail.{{ $key }}' class="sr-only">
                                    <span
                                        class="h-5 bg-gray-200 border border-gray-200 rounded-full w-9 toggle-bg dark:bg-gray-700 dark:border-gray-600"></span>
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-end gap-2">
                        <x-secondary-button type="button" wire:click="$set('showTestMessageModal', true)"
                            :disabled="! ($canTestRestaurantSms ?? false)">
                            {{ __('sms::modules.form.sendTestMessage') }}
                        </x-secondary-button>
                        <x-button type="submit">@lang('app.save')</x-button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <x-dialog-modal wire:model.live="showTestMessageModal" maxHeight="none">
        <x-slot name="title">
            {{ __('sms::modules.form.sendTestMessage') }}
        </x-slot>
        <x-slot name="content">
            <div>
                <x-label for="restaurantTestPhone" value="{{ __('modules.customer.phone') }}" />
                <div class="flex gap-2 mt-1.5">
                    <div x-data="{ isOpen: @entangle('phoneCodeIsOpen').live }" @click.away="isOpen = false" class="relative z-40 w-28 shrink-0">
                        <div @click="isOpen = !isOpen"
                            class="py-1.5 px-2 bg-gray-100 border rounded cursor-pointer text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-gray-600 dark:focus:ring-gray-600">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-xs truncate">
                                    @if($phoneCode)
                                        +{{ $phoneCode }}
                                    @else
                                        {{ __('modules.settings.select') }}
                                    @endif
                                </span>
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        <ul x-show="isOpen" x-transition
                            class="absolute left-0 right-0 z-[200] mt-1 max-h-52 min-w-[11rem] overflow-auto bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <li class="sticky top-0 px-2 py-1.5 bg-white dark:bg-gray-900 z-10">
                                <x-input wire:model.live.debounce.300ms="phoneCodeSearch" class="block w-full text-sm py-1" type="text" placeholder="{{ __('placeholders.search') }}" />
                            </li>
                            @forelse ($phonecodes ?? [] as $phonecode)
                                <li @click="$wire.selectPhoneCode('{{ $phonecode }}')"
                                    wire:key="restaurant-phone-code-{{ $phonecode }}"
                                    class="relative py-1.5 pl-2.5 text-gray-900 cursor-pointer select-none pr-6 hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-300"
                                    :class="{ 'bg-gray-100 dark:bg-gray-800': '{{ $phonecode }}' === '{{ $phoneCode }}' }" role="option">
                                    <span class="block text-xs whitespace-nowrap">+{{ $phonecode }}</span>
                                </li>
                            @empty
                                <li class="relative py-1.5 pl-2.5 text-gray-500 cursor-default text-xs dark:text-gray-400">
                                    {{ __('modules.settings.noPhoneCodesFound') }}
                                </li>
                            @endforelse
                        </ul>
                    </div>
                    <x-input id="restaurantTestPhone" class="block w-full text-sm py-1.5" type="tel" wire:model="phone" required />
                </div>
                <x-input-error for="phoneCode" class="mt-1" />
                <x-input-error for="phone" class="mt-1" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showTestMessageModal', false)">
                {{ __('app.cancel') }}
            </x-secondary-button>
            <x-button class="ml-2" wire:click="sendTestMessage" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('sms::modules.form.sendTestMessage') }}</span>
                <span wire:loading>{{ __('sms::modules.messages.sending') }}</span>
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- View Modal -->
    <x-dialog-modal wire:model.live="showViewModal" maxWidth="md">
        <x-slot name="title">
            @if(!empty($notificationDetails) && isset($notificationDetails['title']))
                {{ $notificationDetails['title'] }}
            @endif
        </x-slot>

        <x-slot name="content">
            @if(!empty($notificationDetails) && isset($notificationDetails['sms_message']))
                <div class="flex items-start gap-2">
                    <div class="flex-1 bg-white dark:bg-gray-800 p-3 rounded-md border border-blue-200 dark:border-blue-700 min-w-0">
                        <p class="text-xs text-gray-800 dark:text-gray-200 font-mono leading-relaxed break-words">
                            {{ $notificationDetails['sms_message'] }}
                        </p>
                    </div>
                    <button type="button"
                            onclick="copyToClipboard('{{ addslashes($notificationDetails['sms_message']) }}')"
                            class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-800/30 rounded-md shrink-0"
                            title="Copy to clipboard">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </button>
                </div>
            @else
                <div class="text-center py-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('app.noMessageAvailable') }}</p>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-end">
                <x-button wire:click="$toggle('showViewModal')" class="bg-gray-500 hover:bg-gray-600 text-white text-sm py-1.5 px-3">
                    {{ __('app.close') }}
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>

<script>
function copyToClipboard(text) {
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;

    navigator.clipboard.writeText(text).then(function() {
        button.innerHTML = `Copied`;

        setTimeout(() => {
            button.innerHTML = originalHTML;
        }, 2000);
    }).catch(function(err) {
        console.error('Could not copy text: ', err);

        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            textArea.remove();
            button.innerHTML = `Copied`;
            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text:', err);
            button.innerHTML = `Failed`;
            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        }
    });
}
</script>
