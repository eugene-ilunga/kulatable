@extends('layouts.app')

@section('content')

<div class="p-4 bg-white block dark:bg-gray-800 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white leading-tight">{{ __('hotel::modules.reservation.checkAvailability') }}</h1>
        <a href="{{ route('hotel.reservations.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
            {{ __('hotel::modules.reservation.backToReservations') }}
        </a>
    </div>
</div>

<div class="p-4">
    @livewire('hotel::check-availability')
</div>

@endsection
