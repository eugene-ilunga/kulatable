<?php

namespace Modules\Sms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SmsSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(!in_array('Sms', restaurant_modules()), 403);
        abort_if(!user_can('Update Sms Setting'), 403);

        return view('sms::settings.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sms::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sms::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sms::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
