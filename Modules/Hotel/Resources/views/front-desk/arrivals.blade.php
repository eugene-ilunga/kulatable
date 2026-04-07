@extends('layouts.app')

@section('content')

<div class="p-4 bg-white block dark:bg-gray-800 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white leading-tight">{{ __('hotel::modules.frontDesk.todaysArrivals') }}</h1>
    </div>
</div>

<div class="p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <p class="p-4 text-sm text-gray-500 dark:text-gray-400">{{ __('hotel::modules.frontDesk.noArrivalsScheduled') }}</p>
    </div>
</div>

@endsection
