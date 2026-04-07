@extends('layouts.app')

@section('content')

<div class="p-4 bg-white block dark:bg-gray-800 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white leading-tight">{{ __('hotel::modules.checkIn.newCheckIn') }}</h1>
        <a href="{{ route('hotel.check-in.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
            {{ __('hotel::modules.checkIn.backToCheckIn') }}
        </a>
    </div>
</div>

<div class="p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <p class="p-4 text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.checkIn.checkInFormComingSoon') }}</p>
    </div>
</div>

@endsection
