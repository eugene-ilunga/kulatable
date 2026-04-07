<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Venue;
use Modules\Hotel\Entities\Event;
use Modules\Hotel\Enums\EventStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class Banquet extends Component
{
    use LivewireAlert, WithPagination;

    public $showAddVenueModal = false;
    public $showEditVenueModal = false;
    public $showAddEventModal = false;
    public $showEditEventModal = false;
    public $showViewEventModal = false;
    public $confirmDeleteEventModal = false;
    public $confirmDeleteVenueModal = false;
    public $activeEvent;
    public $activeVenue;
    public $search = '';
    public $venueSearch = '';
    public $filterStatus = '';
    public $filterVenue = '';

    #[On('hideAddVenue')]
    public function hideAddVenue()
    {
        $this->showAddVenueModal = false;
    }

    #[On('hideEditVenue')]
    public function hideEditVenue()
    {
        $this->showEditVenueModal = false;
    }

    #[On('hideAddEvent')]
    public function hideAddEvent()
    {
        $this->showAddEventModal = false;
    }

    #[On('hideEditEvent')]
    public function hideEditEvent()
    {
        $this->showEditEventModal = false;
    }

    public function showEditVenue($id)
    {
        $this->activeVenue = Venue::findOrFail($id);
        $this->showEditVenueModal = true;
    }

    public function showDeleteVenue($id)
    {
        $this->confirmDeleteVenueModal = true;
        $this->activeVenue = Venue::findOrFail($id);
    }

    public function deleteVenue($id)
    {
        Venue::destroy($id);
        $this->confirmDeleteVenueModal = false;

        $this->alert('success', __('hotel::modules.banquet.venueDeleted'), [
            'toast' => true,
            'position' => 'top-end',
        ]);

        $this->activeVenue = null;
    }

    public function showViewEvent($id)
    {
        $this->activeEvent = Event::with(['venue', 'customer'])->findOrFail($id);
        $this->showViewEventModal = true;
    }

    public function showEditEvent($id)
    {
        $this->activeEvent = Event::with(['venue', 'customer'])->findOrFail($id);
        $this->showEditEventModal = true;
    }

    public function closeViewEventModal()
    {
        $this->showViewEventModal = false;
        $this->activeEvent = null;
    }

    public function showDeleteEvent($id)
    {
        $this->confirmDeleteEventModal = true;
        $this->activeEvent = Event::findOrFail($id);
    }

    public function deleteEvent($id)
    {
        Event::destroy($id);
        $this->confirmDeleteEventModal = false;

        $this->alert('success', __('hotel::modules.banquet.eventDeleted'), [
            'toast' => true,
            'position' => 'top-end',
        ]);

        $this->activeEvent = null;
    }

    public function render()
    {
        $eventQuery = Event::with(['venue', 'customer'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('event_number', 'like', '%' . $this->search . '%')
                        ->orWhere('event_name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterVenue, function ($q) {
                $q->where('venue_id', $this->filterVenue);
            })
            ->orderBy('start_time', 'desc');

        $venueQuery = Venue::query()
            ->when($this->venueSearch, function ($q) {
                $q->where('name', 'like', '%' . $this->venueSearch . '%');
            })
            ->orderBy('name');

        return view('hotel::livewire.banquet', [
            'events' => $eventQuery->paginate(20),
            'venues' => Venue::where('is_active', true)->orderBy('name')->get(),
            'venuesList' => $venueQuery->get(),
            'statuses' => EventStatus::cases(),
        ]);
    }
}
