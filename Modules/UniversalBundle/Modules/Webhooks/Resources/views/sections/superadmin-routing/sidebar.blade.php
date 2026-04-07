@php
    $isActive = request()->routeIs('superadmin.webhooks.routing-matrix') || request()->routeIs('superadmin.webhooks.package-defaults');
@endphp
<li>
    <a href="{{ route('superadmin.webhooks.routing-matrix') }}"
       class="flex items-center px-4 py-2 text-sm font-medium rounded {{ $isActive ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
        {{ __('Webhook Routing') }}
    </a>
</li>
