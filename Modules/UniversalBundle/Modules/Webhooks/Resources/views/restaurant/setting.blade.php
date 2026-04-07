<div class="p-4 space-y-4">
    <h2 class="text-lg font-semibold">{{ __('Webhooks') }}</h2>
    <p class="text-sm text-gray-600">{{ __('Manage outbound webhooks for this restaurant.') }}</p>
    @livewire('webhooks::admin.webhook-manager')
</div>
