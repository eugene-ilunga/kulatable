<?php

namespace App\Http\Controllers;

use App\Livewire\LandingSite\FooterSetting;
use App\Models\CustomMenu;
use App\Models\GlobalSetting;

class LandingSiteController extends Controller
{

    public function index()
    {
        abort_if((!user_can('Show Landing Site')), 403);
        $settings = GlobalSetting::first();
        $customMenu = CustomMenu::all();
        return view('landing-sites.index', compact('settings', 'customMenu'));
    }

    public function showMenu()
    {
        $customMenu = CustomMenu::orderBy('sort_order')->get();
        $footerSetting = FooterSetting::where('language_id', request()->get('language_id'))->first();
        return view('layouts.landing', compact('customMenu', 'footerSetting'));
    }

}
