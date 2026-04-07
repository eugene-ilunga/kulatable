<div x-data="{ open: false }" x-on:show-delivery.window="open=true; $wire.loadDelivery($event.detail.id)">
    <div x-show="open" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">{{ __('Delivery Detail') }}</h3>
                <button type="button" class="text-gray-500 hover:text-gray-700" x-on:click="open=false">&times;</button>
            </div>
            @if($delivery)
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">{{ __('Event') }}</p>
                        <p class="font-semibold">{{ $delivery->event }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Status') }}</p>
                        <p class="font-semibold">{{ $delivery->status }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Attempts') }}</p>
                        <p class="font-semibold">{{ $delivery->attempts }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Response Code') }}</p>
                        <p class="font-semibold">{{ $delivery->response_code ?? '--' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Duration (ms)') }}</p>
                        <p class="font-semibold">{{ $delivery->duration_ms ?? '--' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Created') }}</p>
                        <p class="font-semibold">{{ $delivery->created_at }}</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-700">{{ __('Payload') }}</p>
                        <div class="bg-gray-900 text-green-100 text-xs rounded p-3 max-h-64 overflow-auto">
                            <pre>{{ json_encode($delivery->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700">{{ __('Response Body') }}</p>
                        <div class="bg-gray-900 text-green-100 text-xs rounded p-3 max-h-64 overflow-auto">
                            <pre>{{ $delivery->response_body ?? '--' }}</pre>
                        </div>
                    </div>
                    @if($delivery->error_message)
                        <div>
                            <p class="text-sm font-semibold text-red-700">{{ __('Error') }}</p>
                            <p class="text-xs text-red-600">{{ $delivery->error_message }}</p>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-600">{{ __('Loading...') }}</p>
            @endif
            <div class="flex justify-end">
                <button type="button" class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1" x-on:click="open=false">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
