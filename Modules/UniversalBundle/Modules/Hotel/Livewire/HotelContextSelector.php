<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Enums\StayStatus;
use Livewire\Component;

class HotelContextSelector extends Component
{
    public $selectedStayId;
    public $billTo = 'PAY_NOW';

    public function mount()
    {
        // Load available stays
    }

    public function confirmSelection()
    {
        if (!$this->selectedStayId) {
            return;
        }

        $this->dispatch('hotelContextSelected', [
            'context_type' => 'HOTEL_ROOM',
            'context_id' => $this->selectedStayId,
            'bill_to' => $this->billTo,
        ]);

        $this->dispatch('closeModal');
    }

    public function render()
    {
        $stays = Stay::where('status', StayStatus::CHECKED_IN)
            ->with(['room.roomType', 'stayGuests.guest'])
            ->get();

        return view('hotel::livewire.hotel-context-selector', [
            'stays' => $stays,
        ]);
    }
}
