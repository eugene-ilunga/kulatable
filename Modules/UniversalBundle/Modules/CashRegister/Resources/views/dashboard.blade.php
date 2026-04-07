@extends('layouts.app')

@section('content')
<div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">@lang('cashregister::app.registerDashboard')</h2>

    @livewire('cash-register.dashboard.register-dashboard')
</div>
@endsection


