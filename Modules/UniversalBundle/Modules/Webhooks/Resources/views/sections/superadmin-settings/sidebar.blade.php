<li class="me-2">
    @php
        // Fallback if $item is somehow not set (though it should be from the parent loop)
        $tabName = $item ?? 'Webhooks';
    @endphp
    <a href="{{ route('superadmin.superadmin-settings.index').'?tab='.$tabName }}"
    @class(["inline-block px-4 py-2 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300 text-xs w-full", 'border-transparent' => ($activeSetting != $tabName), 'active border-skin-base dark:text-skin-base dark:border-skin-base text-skin-base' => ($activeSetting == $tabName)])>
        {{ __('webhooks::webhooks.webhooks') }}
    </a>
</li>
