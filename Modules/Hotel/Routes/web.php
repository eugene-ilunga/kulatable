<?php

use Illuminate\Support\Facades\Route;
use Modules\Hotel\Http\Controllers\FrontDeskController;
use Modules\Hotel\Http\Controllers\HotelSettingController;
use Modules\Hotel\Http\Controllers\RoomController;
use Modules\Hotel\Http\Controllers\ReservationController;
use Modules\Hotel\Http\Controllers\CheckInController;
use Modules\Hotel\Http\Controllers\CheckOutController;
use Modules\Hotel\Http\Controllers\FolioController;
use Modules\Hotel\Http\Controllers\HousekeepingController;
use Modules\Hotel\Http\Controllers\BanquetController;
use Modules\Hotel\Http\Controllers\RoomServiceController;
use Modules\Hotel\Http\Controllers\RoomTypeController;
use Modules\Hotel\Http\Controllers\RatePlanController;
use Modules\Hotel\Http\Controllers\GuestController;
use Modules\Hotel\Http\Controllers\StaysController;

Route::middleware(['auth', 'verified'])->prefix('hotel')->name('hotel.')->group(function () {
    
    // Front Desk
    Route::get('front-desk/dashboard', [FrontDeskController::class, 'dashboard'])->name('front-desk.dashboard');
    Route::get('front-desk/arrivals', [FrontDeskController::class, 'arrivals'])->name('front-desk.arrivals');
    Route::get('front-desk/departures', [FrontDeskController::class, 'departures'])->name('front-desk.departures');
    Route::get('front-desk/in-house', [FrontDeskController::class, 'inHouse'])->name('front-desk.in-house');

    // Rooms
    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('rooms/status-board', [RoomController::class, 'statusBoard'])->name('rooms.status-board');

    // Reservations
    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::get('reservations/availability', [ReservationController::class, 'availability'])->name('reservations.availability');
    Route::get('reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');

    // Check-in
    Route::get('check-in', [CheckInController::class, 'index'])->name('check-in.index');
    Route::get('check-in/create', [CheckInController::class, 'create'])->name('check-in.create');

    // Check-out
    Route::get('check-out', [CheckOutController::class, 'index'])->name('check-out.index');
    Route::get('check-out/{stayId}', [CheckOutController::class, 'show'])->name('check-out.show');

    // Folios
    Route::get('folios/{stayId}', [FolioController::class, 'show'])->name('folios.show');

    // Housekeeping
    Route::get('housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
    Route::get('housekeeping/tasks', [HousekeepingController::class, 'tasks'])->name('housekeeping.tasks');

    // Banquet
    Route::get('banquet', [BanquetController::class, 'index'])->name('banquet.index');
    Route::get('banquet/venues', [BanquetController::class, 'venues'])->name('banquet.venues');
    Route::get('banquet/events', [BanquetController::class, 'events'])->name('banquet.events');

    // Room Service
    Route::get('room-service', [RoomServiceController::class, 'index'])->name('room-service.index');
    Route::get('room-service/create', [RoomServiceController::class, 'create'])->name('room-service.create');

    // Room Types
    Route::get('room-types', [RoomTypeController::class, 'index'])->name('room-types.index');

    // Rate Plans
    Route::get('rate-plans', [RatePlanController::class, 'index'])->name('rate-plans.index');

    // Guests
    Route::get('guests', [GuestController::class, 'index'])->name('guests.index');

    // Stays history
    Route::get('stays', [StaysController::class, 'index'])->name('stays.index');

    // Hotel settings
    Route::get('settings', [HotelSettingController::class, 'index'])->name('settings.index');
});
