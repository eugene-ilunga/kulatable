<?php

namespace Modules\Hotel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoomServiceController extends Controller
{
    public function index()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Show Hotel Room Service'), 403);

        return view('hotel::room-service.index');
    }

    public function create()
    {
        abort_if(!module_enabled('Hotel'), 403);
        abort_if(!in_array('Hotel', restaurant_modules()), 403);
        abort_if(!user_can('Create Hotel Room Service Order'), 403);

        return view('hotel::room-service.create');
    }
}
