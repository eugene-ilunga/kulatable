@if (module_enabled('MultiPOS') && in_array('MultiPOS', restaurant_modules()))
    @if (user_can('Manage MultiPOS Machines'))
        <li class="me-2">
            <a href="{{ route('settings.index').'?tab='.strtolower($item).'Settings' }}" wire:navigate
                @class(["inline-block px-4 py-2 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300. text-xs w-full", 'border-transparent' => ($activeSetting != strtolower($item).'Settings'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == strtolower($item).'Settings')])>
                @lang('modules.settings.multiposSettings')
            </a>
        </li>
    @endif
@endif

