<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Enums\RoomStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Illuminate\Validation\Rule;

class EditRoom extends Component
{
    use LivewireAlert;

    public $room;
    public $room_number;
    public $room_type_id;
    public $floor;
    public $status;
    public $notes;
    public $is_active;

    public function mount($activeRoom)
    {
        $this->room = $activeRoom;
        $this->room_number = $activeRoom->room_number;
        $this->room_type_id = $activeRoom->room_type_id;
        $this->floor = $activeRoom->floor;
        $this->status = $activeRoom->status->value;
        $this->notes = $activeRoom->notes;
        $this->is_active = $activeRoom->is_active;
    }

    public function submitForm()
    {
        $this->validate([
            'room_number' => ['required', 'max:255', Rule::unique('hotel_rooms', 'room_number')->ignore($this->room->id)->where('branch_id', $this->room->branch_id)],
            'room_type_id' => 'required|exists:hotel_room_types,id',
            'floor' => 'nullable|max:255',
            'status' => 'required|in:' . implode(',', array_column(RoomStatus::cases(), 'value')),
            'notes' => 'nullable|string',
        ]);

        $this->room->update([
            'room_number' => $this->room_number,
            'room_type_id' => $this->room_type_id,
            'floor' => $this->floor ?? null,
            'status' => $this->status,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('hideEditRoom');
        $this->dispatch('roomUpdated');

        $this->alert('success', __('hotel::modules.room.roomUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-room', [
            'roomTypes' => RoomType::where('is_active', true)->get(),
            'statuses' => RoomStatus::cases(),
        ]);
    }
}
