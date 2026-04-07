<?php

namespace Modules\Hotel\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Enums\StayStatus;

class PosHotelContextListener extends Component
{
    /**
     * Listen to hotel context selection events from POS
     */
    protected $listeners = [
        'hotelContextSelected' => 'setHotelContext',
        'clearHotelContext' => 'clearContext',
        'resetPos' => 'clearContext',
        'showHotelContextModal' => 'showModal',
    ];

    public $showModal = false;
    public $availableStays = [];
    public $selectedStayId = null;
    public $billTo = 'PAY_NOW';

    /**
     * Show the hotel context modal
     */
    public function showModal(): void
    {
        $this->loadAvailableStays();
        $this->showModal = true;
    }

    /**
     * Close the modal
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedStayId = null;
        $this->billTo = 'PAY_NOW';
    }

    /**
     * Set hotel context in session
     */
    public function setHotelContext($data): void
    {
        $context = [
            'context_type' => $data['context_type'] ?? null,
            'context_id' => $data['context_id'] ?? null,
            'bill_to' => $data['bill_to'] ?? 'PAY_NOW',
        ];

        Session::put('hotel_context', $context);
        $this->closeModal();
        $this->dispatch('$refresh'); // Refresh parent component to show updated context
    }

    /**
     * Select a stay and set context
     */
    public function selectStay(): void
    {
        if (!$this->selectedStayId) {
            return;
        }

        $stay = Stay::find($this->selectedStayId);
        if (!$stay) {
            return;
        }

        $this->setHotelContext([
            'context_type' => 'HOTEL_ROOM',
            'context_id' => $stay->id,
            'bill_to' => $this->billTo,
        ]);
        
        // Dispatch event to refresh the POS component
        $this->dispatch('refreshPos');
    }

    /**
     * Clear hotel context from session
     */
    public function clearContext(): void
    {
        Session::forget('hotel_context');
        // Dispatch event to refresh the POS component
        $this->dispatch('refreshPos');
    }

    /**
     * Load available stays for selection
     */
    public function loadAvailableStays(): void
    {
        if (!module_enabled('Hotel')) {
            $this->availableStays = [];
            return;
        }

        $this->availableStays = Stay::where('status', StayStatus::CHECKED_IN)
            ->with(['room.roomType', 'stayGuests.guest'])
            ->get();
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('hotel::livewire.pos-hotel-context-listener');
    }
}
