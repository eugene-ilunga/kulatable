<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Enums\StayStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class Stays extends Component
{
    use LivewireAlert, WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $selectedStay = null;
    public $showViewModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function viewStay(int $id): void
    {
        $this->selectedStay = Stay::with([
            'room.roomType',
            'reservation',
            'stayGuests.guest',
            'folio.folioLines',
            'folio.folioPayments',
            'checkedInBy',
            'checkedOutBy',
        ])->findOrFail($id);

        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->selectedStay = null;
    }

    public function render()
    {
        $stays = Stay::with(['room.roomType', 'stayGuests.guest', 'folio'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('stay_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('room', fn ($r) => $r->where('room_number', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('stayGuests.guest', fn ($g) => $g->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%')
                            ->orWhere('phone', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('check_in_at', 'desc')
            ->paginate(20);

        return view('hotel::livewire.stays', [
            'stays' => $stays,
            'statuses' => StayStatus::cases(),
        ]);
    }
}
