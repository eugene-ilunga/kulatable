<?php

namespace Modules\Webhooks\Livewire\Restaurant;

use Livewire\Component;

class Setting extends Component
{
    // Accept settings from parent (settings master passes it)
    public $settings = null;
    
    public function render()
    {
        return view('webhooks::livewire.restaurant.setting');
    }
}
