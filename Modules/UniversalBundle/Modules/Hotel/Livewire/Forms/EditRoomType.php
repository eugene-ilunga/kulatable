<?php

namespace Modules\Hotel\Livewire\Forms;

use App\Helper\Files;
use Modules\Hotel\Entities\RoomType;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditRoomType extends Component
{
    use LivewireAlert, WithFileUploads;

    public $roomType;
    public $imageTemp;
    public $name;
    public $description;
    public $max_occupancy;
    public $base_occupancy;
    public $base_rate;
    public $amenities = [];
    public $amenityInput = '';
    public $is_active;
    public $sort_order;

    public function mount($activeRoomType)
    {
        $this->roomType = $activeRoomType;
        $this->name = $activeRoomType->name;
        $this->description = $activeRoomType->description;
        $this->max_occupancy = $activeRoomType->max_occupancy;
        $this->base_occupancy = $activeRoomType->base_occupancy;
        $this->base_rate = $activeRoomType->base_rate;
        $this->amenities = $activeRoomType->amenities ?? [];
        $this->is_active = $activeRoomType->is_active;
        $this->sort_order = $activeRoomType->sort_order;
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

        $updateData = [
            'name' => $this->name,
            'description' => $this->description,
            'max_occupancy' => $this->max_occupancy,
            'base_occupancy' => $this->base_occupancy,
            'base_rate' => $this->base_rate,
            'amenities' => $this->amenities,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->imageTemp) {
            $updateData['image'] = Files::uploadLocalOrS3($this->imageTemp, 'room_type', 350);
        }

        $this->roomType->update($updateData);

        $this->dispatch('hideEditRoomType');
        $this->dispatch('roomTypeUpdated');

        $this->alert('success', __('hotel::modules.roomType.roomTypeUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-room-type');
    }
}
