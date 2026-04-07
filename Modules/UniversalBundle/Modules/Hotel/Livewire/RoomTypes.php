<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\RoomType;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\Attributes\On;

class RoomTypes extends Component
{
    use LivewireAlert;

    public $showAddRoomTypeModal = false;
    public $showEditRoomTypeModal = false;
    public $confirmDeleteRoomTypeModal = false;
    public $activeRoomType;
    public $search = '';
    public $filterStatus = '';
    public $showFilters = false;

    #[On('hideAddRoomType')]
    public function hideAddRoomType()
    {
        $this->showAddRoomTypeModal = false;
    }

    public function showEditRoomType($id)
    {
        $this->activeRoomType = RoomType::findOrFail($id);
        $this->showEditRoomTypeModal = true;
    }

    public function showDeleteRoomType($id)
    {
        $this->confirmDeleteRoomTypeModal = true;
        $this->activeRoomType = RoomType::findOrFail($id);
    }

    #[On('hideEditRoomType')]
    public function hideEditRoomType()
    {
        $this->showEditRoomTypeModal = false;
    }

    public function deleteRoomType($id)
    {
        RoomType::destroy($id);
        $this->confirmDeleteRoomTypeModal = false;

        $this->alert('success', __('hotel::modules.roomType.roomTypeDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->activeRoomType = null;
    }

    public function clearFilters()
    {
        $this->filterStatus = '';
    }

    public function render()
    {
        $query = RoomType::withCount('rooms')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1' ? true : false);
            })
            ->orderBy('sort_order');

        return view('hotel::livewire.room-types', [
            'roomTypes' => $query->get()
        ]);
    }
}
