<?php

namespace Modules\Hotel\Livewire\Forms;

use App\Helper\Files;
use Modules\Hotel\Entities\RoomType;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddRoomType extends Component
{
    use LivewireAlert, WithFileUploads;

    public $name;
    public $imageTemp;
    public $description;
    public $max_occupancy = 2;
    public $base_occupancy = 2;
    public $base_rate = 0;
    public $amenities = [];
    public $amenityInput = '';
    public $is_active = true;
    public $sort_order = 0;

    public function mount()
    {
        $this->name = '';
        $this->description = '';
        $this->amenities = [];
        $this->imageTemp = null;
    }

    public function updatedImageTemp()
    {
        if ($this->imageTemp) {
            $this->validate(['imageTemp' => 'image|mimes:jpeg,png,jpg|max:2048']);
        }
    }

    public function removeSelectedImage()
    {
        $this->imageTemp = null;
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
            'max_occupancy' => 'required|integer|min:1',
            'base_occupancy' => 'required|integer|min:1|lte:max_occupancy',
            'base_rate' => 'required|numeric|min:0',
            'sort_order' => 'integer|min:0',
            'imageTemp' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = null;
        if ($this->imageTemp) {
            $imagePath = Files::uploadLocalOrS3($this->imageTemp, 'room_type', 350);
        }

        RoomType::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'name' => $this->name,
            'image' => $imagePath,
            'description' => $this->description,
            'max_occupancy' => $this->max_occupancy,
            'base_occupancy' => $this->base_occupancy,
            'base_rate' => $this->base_rate,
            'amenities' => $this->amenities,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        $this->reset();
        $this->dispatch('hideAddRoomType');
        $this->dispatch('roomTypeAdded');

        $this->alert('success', __('hotel::modules.roomType.roomTypeAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.add-room-type');
    }
}
