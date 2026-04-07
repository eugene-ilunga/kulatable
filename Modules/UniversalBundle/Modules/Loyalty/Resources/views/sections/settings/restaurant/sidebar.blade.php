@if (module_enabled('Loyalty') && in_array('Loyalty', restaurant_modules()))

<li class="me-2">
    <a href="{{ route('settings.index').'?tab=loyaltySettings' }}" wire:navigate
        @class(["inline-block px-4 py-2 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300. text-xs w-full", 'border-transparent' => ($activeSetting != 'loyaltySettings'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'loyaltySettings')])>
        {{ __('loyalty::app.loyaltyProgram') }}
    </a>
</li>
@endif
