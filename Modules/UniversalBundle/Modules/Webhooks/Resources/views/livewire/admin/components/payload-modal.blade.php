<div x-data="{ open: false, payload: null }"
     x-on:show-payload.window="open=true; payload = await fetchPayload($event.detail.id)">
    <div x-show="open" class="fixed inset-0 bg-black/50 z-40 flex items-center justify-center" x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">{{ __('Delivery Payload') }}</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" x-on:click="open=false">&times;</button>
            </div>
            <div class="bg-gray-900 text-green-100 p-3 rounded text-xs overflow-auto max-h-96" x-show="payload">
                <pre x-text="payload"></pre>
            </div>
            <div class="text-center text-sm text-gray-500" x-show="!payload">{{ __('Loading...') }}</div>
            <div class="flex justify-end">
                <button type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1" x-on:click="open=false">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
    <script>
        async function fetchPayload(id) {
            try {
                const res = await fetch(`/webhooks/deliveries/${id}`, { headers: { 'Accept': 'application/json' }});
                if (!res.ok) return 'Unable to load payload';
                const data = await res.json();
                return JSON.stringify(data, null, 2);
            } catch (e) {
                return 'Error loading payload';
            }
        }
    </script>
</div>
