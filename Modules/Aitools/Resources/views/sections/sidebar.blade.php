@if(restaurant() && restaurant()->ai_enabled)
@php
    $policy = new \Modules\Aitools\Services\Ai\AiPolicy();
    $accessCheck = $policy->canAccess(user(), restaurant());
@endphp
@if($accessCheck['allowed'])
    @livewire('sidebar-menu-item', ['name' => 'AI Assistant', 'icon' => 'ai', 'link' => route('ai.chat'), 'active' => request()->routeIs('ai.*'), 'isAddon' => true])
@endif
@endif
