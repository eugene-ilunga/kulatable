<?php

namespace Modules\Hotel\Livewire\Settings;

use Livewire\Component;

class HotelSettings extends Component
{
    public $activeTab = 'taxes';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('hotel::livewire.settings.hotel-settings');
    }
}
