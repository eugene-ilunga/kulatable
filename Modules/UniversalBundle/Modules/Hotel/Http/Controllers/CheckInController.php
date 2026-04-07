<?php

namespace Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function index()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Check In Hotel Guest'), 403);

        return view('hotel::check-in.index');
    }

    public function create()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Check In Hotel Guest'), 403);

        return view('hotel::check-in.create');
    }
}
