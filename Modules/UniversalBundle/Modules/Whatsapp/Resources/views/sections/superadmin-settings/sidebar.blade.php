@if(function_exists('module_enabled') && module_enabled('Whatsapp'))
<li class="me-2">
    <a href="{{ route('superadmin.superadmin-settings.index').'?tab=whatsapp' }}" wire:navigate
        @class(["inline-block px-4 py-2 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300. text-xs w-full", 'border-transparent' => ($activeSetting != 'whatsapp'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'whatsapp')])>
        @lang('whatsapp::app.whatsappSettings')
    </a>
</li>
@endif

