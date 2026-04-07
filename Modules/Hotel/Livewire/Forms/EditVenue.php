<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Venue;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EditVenue extends Component
{
    use LivewireAlert;

    public $venue;
    public $name;
    public $description;
    public $capacity = 0;
    public $base_rate = 0;
    public $amenities = [];
    public $amenityInput = '';
    public $is_active = true;

    public function mount($activeVenue)
    {
        $this->venue = $activeVenue;
        $this->name = $activeVenue->name;
        $this->description = $activeVenue->description;
        $this->capacity = $activeVenue->capacity;
        $this->base_rate = $activeVenue->base_rate;
        $this->amenities = $activeVenue->amenities ?? [];
        $this->is_active = $activeVenue->is_active;
    }

    public function addAmenity()
    {
        if (!empty($this->amenityInput)) {
            $this->amenities[] = $this->amenityInput;
            $this->amenityInput = '';
        }
    }

    public function removeAmenity($index)
    {
        unset($this->amenities[$index]);
        $this->amenities = array_values($this->amenities);
    }

    public function submitForm()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:0',
            'base_rate' => 'required|numeric|min:0',
        ]);

        $this->venue->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'capacity' => $this->capacity,
            'base_rate' => $this->base_rate,
            'amenities' => $this->amenities,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('hideEditVenue');
        $this->dispatch('venueUpdated');

        $this->alert('success', __('hotel::modules.banquet.venueUpdated'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-venue');
    }
}

