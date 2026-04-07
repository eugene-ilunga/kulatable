<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Enums\ReservationStatus;
use Modules\Hotel\Enums\StayStatus;
use Livewire\Component;
use Carbon\Carbon;

class FrontDeskDashboard extends Component
{
    public function render()
    {
        $today = now()->format('Y-m-d');
        
        $arrivals = Reservation::where('check_in_date', $today)
            ->whereIn('status', [ReservationStatus::CONFIRMED, ReservationStatus::TENTATIVE])
            ->with('primaryGuest')
            ->orderBy('check_in_time')
            ->get();

        $departures = Reservation::where('check_out_date', $today)
            ->where('status', ReservationStatus::CHECKED_IN)
            ->with('primaryGuest')
            ->orderBy('check_out_time')
            ->get();

        $inHouse = Stay::where('status', StayStatus::CHECKED_IN)
            ->with(['room.roomType', 'stayGuests.guest'])
            ->get();

        $totalRooms = \Modules\Hotel\Entities\Room::where('is_active', true)->count();
        $occupiedRooms = $inHouse->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        return view('hotel::livewire.front-desk-dashboard', [
            'arrivals' => $arrivals,
            'departures' => $departures,
            'inHouse' => $inHouse,
            'arrivalsCount' => $arrivals->count(),
            'departuresCount' => $departures->count(),
            'inHouseCount' => $inHouse->count(),
            'occupancyRate' => $occupancyRate,
        ]);
    }
}
