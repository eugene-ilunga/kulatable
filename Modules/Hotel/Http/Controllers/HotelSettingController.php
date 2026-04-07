<?php

namespace Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;

class HotelSettingController extends Controller
{
    /**
     * Display hotel settings.
     */
    public function index()
    {
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Reservations'), 403);

        return view('hotel::settings.index');
    }
}
