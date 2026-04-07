@extends('layouts.app')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 mb-8">
    <div class="space-y-6">
        <div class="flex items-start justify-between mb-6">
            <div class="space-y-1">
                <h2 class="text-2xl font-semibold tracking-tight leading-tight text-gray-900 dark:text-white">@lang('cashregister::app.cashRegister')</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">@lang('cashregister::app.cashRegisterSubtitle')</p>
            </div>
            <button type="button"
                onclick="window.Livewire && (window.Livewire.dispatch ? window.Livewire.dispatch('openCloseRegisterModal') : window.Livewire.emit('openCloseRegisterModal'))"
                class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white shadow-sm">
                @lang('cashregister::app.closeRegister')
            </button>
        </div>

        @livewire('cash-register.cashier-widget')
    </div>
</div>
@endsection
