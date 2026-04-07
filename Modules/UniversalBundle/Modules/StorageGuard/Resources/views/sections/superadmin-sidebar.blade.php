@livewire('sidebar-menu-item', [
    'name' => __('storageguard::messages.name'),
    'icon' => 'settings',
    'customIcon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white">
        <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.352-.272-2.636-.759-3.804a.75.75 0 00-.722-.515 11.209 11.209 0 01-7.877-3.08zM12 15.75h.007v.008H12v-.008z" clip-rule="evenodd" />
        <path d="M12 7a.75.75 0 01.75.75v5.5a.75.75 0 01-1.5 0v-5.5A.75.75 0 0112 7z" />
    </svg>',
    'link' => route('storageguard.status'),
    'active' => request()->routeIs('storageguard.*')
])
