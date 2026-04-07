<?php

namespace Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Hotel\Entities\Reservation;

class ReservationController extends Controller
{
    public function index()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Reservations'), 403);

        return view('hotel::reservations.index');
    }

    public function create()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Create Hotel Reservation'), 403);

        return view('hotel::reservations.create');
    }

    public function edit(Reservation $reservation)
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Update Hotel Reservation'), 403);
        abort_if(!in_array($reservation->status->value, ['tentative', 'confirmed']), 404);

        return view('hotel::reservations.edit', ['reservation' => $reservation]);
    }

    public function availability()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Reservations'), 403);

        return view('hotel::reservations.availability');
    }
}
