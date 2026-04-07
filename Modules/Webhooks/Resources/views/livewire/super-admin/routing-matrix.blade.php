<div class="space-y-6">
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-lg">{{ __('webhooks::webhooks.routing_matrix_title') }}</h3>
                <p class="text-xs text-gray-500">{{ __('webhooks::webhooks.routing_matrix_desc') }}</p>
            </div>
            <button type="button" wire:click="save" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-colors">
                <span wire:loading.remove wire:target="save">{{ __('webhooks::webhooks.save') }}</span>
                <span wire:loading wire:target="save">{{ __('webhooks::webhooks.saving') }}</span>
            </button>
        </div>
        <div class="p-4 space-y-4">
            @forelse ($matrix as $module => $entry)
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 font-semibold text-sm text-gray-700 uppercase tracking-wider flex items-center">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 ltr:mr-2 rtl:ml-2"></span>
                        {{ $module }}
                    </div>
                    <div class="divide-y divide-gray-100 bg-white">
                        @foreach ($entry['events'] as $event)
                            <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                <div>
                                    <p class="font-medium text-gray-900 font-mono text-sm">{{ $event['event_key'] }}</p>
                                    @if($event['description'])
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $event['description'] }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ __('webhooks::webhooks.schema_version') }}{{ $event['schema_version'] }}
                                    </span>
                                    <button type="button"
                                            wire:click="toggle('{{ $module }}','{{ $event['event_key'] }}')"
                                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1
                                            {{ $event['allowed'] 
                                                ? 'bg-green-100 text-green-700 hover:bg-green-200 border border-green-200 focus:ring-green-500' 
                                                : 'bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 focus:ring-red-500' 
                                            }}">
                                        @if($event['allowed'])
                                            <svg class="w-3 h-3 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            {{ __('webhooks::webhooks.allowed') }}
                                        @else
                                            <svg class="w-3 h-3 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            {{ __('webhooks::webhooks.blocked') }}
                                        @endif
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">{{ __('webhooks::webhooks.no_events_found') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
