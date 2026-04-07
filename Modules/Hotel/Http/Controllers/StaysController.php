<?php

namespace Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;

class StaysController extends Controller
{
    public function index()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Stays'), 403);

        return view('hotel::stays.index');
    }
}
