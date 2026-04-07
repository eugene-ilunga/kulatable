<?php

namespace Modules\Hotel\Livewire\Setting;

use Livewire\Component;

class Master extends Component
{
    public $activeSetting;

    public function mount()
    {
        $this->activeSetting = request('tab') ?: 'taxes';
    }

    public function render()
    {
        return view('hotel::livewire.setting.master');
    }
}
