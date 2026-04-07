{{-- Webhooks Module Index - Works standalone AND in settings tab --}}
<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold mb-1">{{ __('Webhooks') }}</h2>
            <p class="text-sm text-gray-600">
                {{ __('Manage outbound webhooks. Only events from enabled modules will be sent and scoped to this restaurant.') }}
            </p>
        </div>
        <button type="button" onclick="document.getElementById('webhook-modal').classList.remove('hidden')" class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
            {{ __('Add Webhook') }}
        </button>
    </div>

    @livewire('webhooks::admin.webhook-manager')
</div>

