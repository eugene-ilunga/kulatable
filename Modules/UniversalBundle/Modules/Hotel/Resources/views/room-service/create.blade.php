@extends('layouts.app')

@section('content')

<div class="p-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('hotel::modules.roomService.createRoomServiceOrder') }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            {{ __('hotel::modules.roomService.createRoomServiceDescription') }}
        </p>
        <a href="{{ route('pos.index') }}" class="inline-block px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
            {{ __('hotel::modules.roomService.goToPos') }}
        </a>
    </div>
</div>

@endsection
