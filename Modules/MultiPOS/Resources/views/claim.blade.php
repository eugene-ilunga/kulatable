@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="max-w-2xl mx-auto bg-white shadow rounded-lg">
        <div class="p-6">
            <div class="text-center mb-6">
                <svg class="mx-auto h-16 w-16 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                <h2 class="mt-4 text-2xl font-bold text-gray-900">@lang('multipos::messages.claim.title')</h2>
                <p class="mt-2 text-sm text-gray-600">@lang('multipos::messages.claim.description')</p>
            </div>

            <form action="{{ route('pos.claim.store') }}" method="POST" id="registrationForm">
                @csrf

                <div class="mb-6">
                    <label for="branch_id" class="block text-sm font-medium text-gray-700">
                        @lang('multipos::messages.claim.branch_label') <span class="text-red-500">*</span>
                    </label>
                    <select name="branch_id" id="branch_id" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">@lang('multipos::messages.claim.select_branch')</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label for="alias" class="block text-sm font-medium text-gray-700">
                        @lang('multipos::messages.claim.device_alias_label')
                    </label>
                    <input type="text" name="alias" id="alias"
                        placeholder="@lang('multipos::messages.claim.device_alias_placeholder')"
                        maxlength="255"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">@lang('multipos::messages.claim.device_alias_hint')</p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-blue-700">
                            <p class="font-medium">@lang('multipos::messages.claim.what_happens_next_title')</p>
                            <p class="mt-1">@lang('multipos::messages.claim.what_happens_next_message')</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('pos.index') }}"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        @lang('multipos::messages.claim.cancel')
                    </a>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        @lang('multipos::messages.claim.register_device')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const branchId = document.getElementById('branch_id').value;
        if (!branchId) {
            e.preventDefault();
            alert({!! json_encode(__('multipos::messages.claim.select_branch_alert')) !!});
        }
    });
</script>
@endsection

