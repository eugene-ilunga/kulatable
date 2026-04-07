@php
    // Use the module name key (restapi) for tab resolution
    $tabKey = 'restapi';
    $isActive = ($activeSetting ?? '') === $tabKey;
@endphp
<li class="me-2">
    <a href="{{ route('superadmin.superadmin-settings.index') . '?tab=' . $tabKey }}" wire:navigate
       @class([
           'inline-block px-4 py-2 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-xs w-full',
           'border-transparent' => ! $isActive,
           'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => $isActive,
       ])>
        {{ __('restapi::app.api') }}
    </a>
</li>

