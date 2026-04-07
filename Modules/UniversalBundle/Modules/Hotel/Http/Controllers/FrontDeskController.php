<?php

namespace Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FrontDeskController extends Controller
{
    public function dashboard()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Front Desk'), 403);

        return view('hotel::front-desk.dashboard');
    }

    public function arrivals()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Front Desk'), 403);

        return view('hotel::front-desk.arrivals');
    }

    public function departures()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Front Desk'), 403);

        return view('hotel::front-desk.departures');
    }

    public function inHouse()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Front Desk'), 403);

        return view('hotel::front-desk.in-house');
    }
}
