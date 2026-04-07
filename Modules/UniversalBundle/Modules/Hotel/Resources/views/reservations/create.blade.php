@extends('layouts.app')

@section('content')

<div class="p-4 bg-white block dark:bg-gray-800 dark:border-gray-700 border-b border-gray-200 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-3">
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white leading-tight">{{ __('hotel::modules.reservation.newReservation') }}</h1>
        <a href="{{ route('hotel.reservations.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ __('hotel::modules.reservation.backToReservations') }}
        </a>
    </div>
</div>

<div class="p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        @livewire('hotel::forms.add-reservation' , key('add-reservation-form - ' . microtime()))
    </div>
</div>

@endsection
