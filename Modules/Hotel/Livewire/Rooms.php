<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Enums\RoomStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class Rooms extends Component
{
    use LivewireAlert, WithPagination;

    public $showAddRoomModal = false;
    public $showEditRoomModal = false;
    public $confirmDeleteRoomModal = false;
    public $activeRoom;
    public $search = '';
    public $filterRoomType = '';
    public $filterStatus = '';
    public $showFilters = false;

    #[On('hideAddRoom')]
    public function hideAddRoom()
    {
        $this->showAddRoomModal = false;
    }

    public function showEditRoom($id)
    {
        $this->activeRoom = Room::findOrFail($id);
        $this->showEditRoomModal = true;
    }

    public function showDeleteRoom($id)
    {
        $this->confirmDeleteRoomModal = true;
        $this->activeRoom = Room::findOrFail($id);
    }

    #[On('hideEditRoom')]
    public function hideEditRoom()
    {
        $this->showEditRoomModal = false;
    }

    public function deleteRoom($id)
    {
        Room::destroy($id);
        $this->confirmDeleteRoomModal = false;

        $this->alert('success', __('hotel::modules.room.roomDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->activeRoom = null;
    }

    public function clearFilters()
    {
        $this->filterRoomType = '';
        $this->filterStatus = '';
    }

    public function render()
    {
        $query = Room::with('roomType')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('room_number', 'like', '%' . $this->search . '%')
                          ->orWhere('floor', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterRoomType, function ($q) {
                $q->where('room_type_id', $this->filterRoomType);
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->orderBy('floor')
            ->orderBy('room_number');

        return view('hotel::livewire.rooms', [
            'rooms' => $query->paginate(20),
            'roomTypes' => RoomType::where('is_active', true)->get(),
            'statuses' => RoomStatus::cases(),
        ]);
    }
}
