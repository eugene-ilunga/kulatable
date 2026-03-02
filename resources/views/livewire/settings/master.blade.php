<div>
    <div class="relative text-sm font-medium text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700">
        <!-- Compact search trigger (doesn't take layout space like an always-visible input) -->
        <button id="settingsTabSearchToggle" type="button"
            class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-skin-base/40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            aria-label="{{ __('app.search') }}" aria-expanded="false" aria-controls="settingsTabSearchPanel">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a.75.75 0 101.06-1.06l-2.249-2.249A6 6 0 009 3zm-4.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Overlay search (absolute, so it won't push the UI down) -->
        <div id="settingsTabSearchPanel"
            class="absolute right-2 top-2 z-20 hidden w-[min(20rem,calc(100vw-1rem))] rounded-xl border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800"
            role="dialog" aria-label="{{ __('app.search') }}">
            <div class="relative">
                <label for="settingsTabSearch" class="sr-only">{{ __('app.search') }}</label>

                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.03l2.249 2.249a.75.75 0 101.06-1.06l-2.249-2.249A6 6 0 009 3zm-4.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0z" clip-rule="evenodd" />
                    </svg>
                </div>

                <input id="settingsTabSearch" type="search" placeholder="{{ __('app.search') }}"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-10 pr-20 text-sm text-gray-900 shadow-sm outline-none transition focus:border-skin-base focus:ring-2 focus:ring-skin-base/40 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />

                <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-2">
                    <button id="settingsTabSearchClear" type="button"
                        class="hidden h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        aria-label="{{ __('app.clear') }}">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2.53-10.47a.75.75 0 00-1.06-1.06L10 8.94 8.53 7.47a.75.75 0 10-1.06 1.06L8.94 10l-1.47 1.47a.75.75 0 101.06 1.06L10 11.06l1.47 1.47a.75.75 0 101.06-1.06L11.06 10l1.47-1.47z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button id="settingsTabSearchClose" type="button"
                        class="h-7 w-7 items-center justify-center rounded-md text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                        aria-label="{{ __('app.close') ?? 'Close' }}">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <ul id="settingsTabsList" class="flex flex-wrap -mb-px">
            @if (user()->hasRole('Admin_'.user()->restaurant_id))

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=restaurant' }}" wire:navigate
                    @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'restaurant'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'restaurant')])>
                    @lang('modules.settings.restaurantSettings')
                </a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=app' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'app'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'app')])>@lang('modules.settings.appSettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=operationalShifts' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'operationalShifts'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'operationalShifts')])>@lang('modules.settings.operationalShifts')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=branch' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'branch'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'branch')])>@lang('modules.settings.branchSettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=currency' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'currency'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'currency')])>@lang('modules.settings.currencySettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=email' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'email'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'email')])>@lang('modules.settings.emailSettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=tax' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'tax'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'tax')])>@lang('modules.settings.taxSettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=payment' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'payment'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'payment')])>@lang('modules.settings.paymentgatewaySettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=theme' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'theme'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'theme')])>@lang('modules.settings.themeSettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=role' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'role'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'role')])>@lang('modules.settings.roleSettings')</a>
            </li>

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=billing' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'billing'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'billing')])>@lang('modules.settings.billing')</a>
            </li>

            @endif

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=reservation' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'reservation'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'reservation')])>@lang('modules.settings.reservationSettings')</a>
            </li>

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=aboutus' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'aboutus'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'aboutus')])>@lang('modules.settings.aboutUsSettings')</a>
            </li>

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=customerSite' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'customerSite'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'customerSite')])>@lang('modules.settings.customerSiteSettings')</a>
            </li>

              <li class="me-2">
                <a href="{{ route('settings.index').'?tab=receipt' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'receipt'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'receipt')])>@lang('modules.settings.receiptSetting')</a>
            </li>

             <li class="me-2">
                <a href="{{ route('settings.index').'?tab=printer' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'printer'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'printer')])>@lang('modules.settings.printerSetting')</a>
            </li>

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=deliverySettings' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'deliverySettings'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'deliverySettings')])>@lang('modules.settings.deliverySettings')</a>
            </li>

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=kotSettings' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'kotSettings'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'kotSettings')])>@lang('modules.settings.kotSettings')</a>
            </li>
            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=cancelSettings' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'cancelSettings'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'cancelSettings')])>@lang('modules.settings.cancelSettings')</a>
            </li>

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=orderSettings' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'orderSettings'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'orderSettings')])>@lang('modules.settings.orderSetting')</a>
            </li>

            <li class="me-2">
                <a href="{{ route('settings.index').'?tab=refundReasons' }}" wire:navigate
                @class(["inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300", 'border-transparent' => ($activeSetting != 'refundReasons'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'refundReasons')])>@lang('modules.settings.refundReasons')</a>
            </li>


            <!-- NAV ITEM - CUSTOM MODULES  -->
            @foreach (custom_module_plugins() as $item)
                @includeIf(strtolower($item) . '::sections.settings.restaurant.sidebar')
            @endforeach
        </ul>
    </div>

    @once
        @push('scripts')
            <script>
                (function () {
                    function initSettingsTabSearch() {
                        const input = document.getElementById('settingsTabSearch');
                        const clearBtn = document.getElementById('settingsTabSearchClear');
                        const toggleBtn = document.getElementById('settingsTabSearchToggle');
                        const panel = document.getElementById('settingsTabSearchPanel');
                        const closeBtn = document.getElementById('settingsTabSearchClose');
                        const list = document.getElementById('settingsTabsList');
                        if (!input || !list || !toggleBtn || !panel) return;
                        if (input.dataset.bound === '1') return;
                        input.dataset.bound = '1';

                        const openPanel = () => {
                            panel.classList.remove('hidden');
                            toggleBtn.setAttribute('aria-expanded', 'true');
                            setTimeout(() => input.focus(), 0);
                        };

                        const closePanel = () => {
                            panel.classList.add('hidden');
                            toggleBtn.setAttribute('aria-expanded', 'false');
                        };

                        const filter = () => {
                            const q = (input.value || '').trim().toLowerCase();
                            if (clearBtn) {
                                clearBtn.classList.toggle('hidden', q.length === 0);
                                clearBtn.classList.toggle('flex', q.length > 0);
                            }

                            Array.from(list.children).forEach((child) => {
                                if (!child || child.tagName !== 'LI') return;
                                const text = (child.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                                child.classList.toggle('hidden', q.length > 0 && !text.includes(q));
                            });
                        };

                        input.addEventListener('input', filter);
                        toggleBtn.addEventListener('click', () => {
                            if (panel.classList.contains('hidden')) openPanel();
                            else closePanel();
                        });
                        if (closeBtn) closeBtn.addEventListener('click', closePanel);
                        if (clearBtn) {
                            clearBtn.addEventListener('click', () => {
                                input.value = '';
                                input.focus();
                                filter();
                            });
                        }

                        // Close on escape
                        input.addEventListener('keydown', (e) => {
                            if (e.key === 'Escape') {
                                e.preventDefault();
                                closePanel();
                                toggleBtn.focus();
                            }
                        });

                        // Close when clicking outside of the panel/toggle
                        document.addEventListener('click', (e) => {
                            if (panel.classList.contains('hidden')) return;
                            const target = e.target;
                            if (panel.contains(target) || toggleBtn.contains(target)) return;
                            closePanel();
                        });

                        filter();
                    }

                    document.addEventListener('DOMContentLoaded', initSettingsTabSearch);
                    document.addEventListener('livewire:navigated', initSettingsTabSearch);
                })();
            </script>
        @endpush
    @endonce

    <div class="grid grid-cols-1 pt-6 dark:bg-gray-900">

        <div>
            @switch($activeSetting)
                @case('restaurant')
                @livewire('settings.generalSettings', ['settings' => $settings])
                @break

                @case('app')
                @livewire('settings.timezoneSettings', ['settings' => $settings])
                @break

                @case('email')
                @livewire('settings.notificationSettings', ['settings' => $settings])
                @break

                @case('currency')
                @livewire('settings.currencySettings')
                @break

                @case('payment')
                @livewire('settings.paymentSettings', ['settings' => $settings])
                @break

                @case('theme')
                @livewire('settings.themeSettings', ['settings' => $settings])
                @break

                @case('role')
                @livewire('settings.roleSettings', ['settings' => $settings])
                @break

                @case('tax')
                @livewire('settings.taxSettings', ['settings' => $settings])
                @break

                @case('reservation')
                @livewire('settings.reservationSettings')
                @break

                @case('branch')
                @livewire('settings.branchSettings')
                @break
                @case('billing')
                @livewire('settings.billingSettings')
                @break

                @case('aboutus')
                @livewire('settings.aboutUsSettings', ['settings' => $settings])
                @break

                @case('customerSite')
                @livewire('settings.customerSiteSettings', ['settings' => $settings])
                @break

                @case('receipt')
                @livewire('settings.ReceiptSetting', ['settings' => $settings])
                @break

                @case('printer')
                @livewire('settings.PrinterSetting', ['settings' => $settings])
                @break

                @case('deliverySettings')
                @livewire('settings.branchDeliverySettings', ['settings' => $settings])
                @break

                @case('operationalShifts')
                @livewire('settings.branchOperationalShifts', ['settings' => $settings])
                @break

                @case('kotSettings')
                @livewire('settings.kotSettings', ['settings' => $settings])
                @break

                @case('cancelSettings')
                @livewire('settings.CancellationSettings', ['settings' => $settings])
                @break

                @case('orderSettings')
                @livewire('settings.OrderSettings', ['settings' => $settings])
                @break

                @case('refundReasons')
                @livewire('settings.RefundReasonSettings', ['settings' => $settings])
                @break


                @default

            @endswitch

             <!-- NAV ITEM - CUSTOM MODULES  -->
             @foreach (custom_module_plugins() as $item)
                @if($activeSetting == strtolower($item).'Settings')
                    @livewire(strtolower($item).'::restaurant.setting', ['settings' => $settings])
                @endif
           @endforeach
        </div>

    </div>

</div>
