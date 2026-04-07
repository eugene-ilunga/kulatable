<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Venue;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AddVenue extends Component
{
    use LivewireAlert;

    public $name;
    public $description;
    public $capacity = 0;
    public $base_rate = 0;
    public $amenities = [];
    public $amenityInput = '';
    public $is_active = true;

    public function mount()
    {
        $this->name = '';
        $this->description = '';
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

        Venue::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'name' => $this->name,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'base_rate' => $this->base_rate,
            'amenities' => $this->amenities,
            'is_active' => $this->is_active,
        ]);

        $this->reset();
        $this->dispatch('hideAddVenue');
        $this->dispatch('venueAdded');

        $this->alert('success', __('hotel::modules.banquet.venueAdded'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.add-venue');
    }
}
