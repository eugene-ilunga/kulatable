@if(function_exists('module_enabled') && module_enabled('Whatsapp') && function_exists('restaurant_modules') && in_array('Whatsapp', restaurant_modules()) && \Modules\Whatsapp\Entities\WhatsAppSetting::whereNull('restaurant_id')->where('is_enabled', true)->exists())
<li class="me-2">
    <a href="{{ route('settings.index').'?tab=whatsappSettings' }}" wire:navigate
        @class(["inline-block px-4 py-2 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300. text-xs w-full", 'border-transparent' => ($activeSetting != 'whatsappSettings'), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == 'whatsappSettings')])>
        @lang('whatsapp::app.whatsappNotifications')
    </a>
</li>
@endif

