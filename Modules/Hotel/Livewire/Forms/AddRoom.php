<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Enums\RoomStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Validation\Rule;

class AddRoom extends Component
{
    use LivewireAlert;

    public $room_number;
    public $room_type_id;
    public $floor;
    public $status = RoomStatus::VACANT_CLEAN->value;
    public $notes;
    public $is_active = true;

    public function mount()
    {
        $this->room_number = '';
        $this->floor = '';
        $this->notes = '';
    }

    public function submitForm()
    {
        $this->validate([
            'room_number' => ['required', 'string', 'max:255', Rule::unique('hotel_rooms', 'room_number')->where('branch_id', branch()?->id)],
            'room_type_id' => 'required|exists:hotel_room_types,id',
            'floor' => 'nullable|string|max:255',
            'status' => 'required|in:' . implode(',', array_column(RoomStatus::cases(), 'value')),
            'notes' => 'nullable|string',
        ]);

        Room::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'room_number' => $this->room_number,
            'room_type_id' => $this->room_type_id,
            'floor' => $this->floor ?? null,
            'status' => $this->status,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ]);

        $this->reset();
        $this->dispatch('hideAddRoom');
        $this->dispatch('roomAdded');

        $this->alert('success', __('hotel::modules.room.roomAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.add-room', [
            'roomTypes' => RoomType::where('is_active', true)->get(),
            'statuses' => RoomStatus::cases(),
        ]);
    }
}
