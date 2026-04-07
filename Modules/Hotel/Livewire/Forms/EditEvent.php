<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Event;
use Modules\Hotel\Entities\Venue;
use App\Models\Customer;
use Modules\Hotel\Enums\EventStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EditEvent extends Component
{
    use LivewireAlert;

    public $event;
    public $venue_id;
    public $customer_id;
    public $event_name;
    public $description;
    public $start_time;
    public $end_time;
    public $expected_guests = 0;
    public $status;
    public $package_amount = 0;
    public $advance_paid = 0;
    public $special_requests;
    public $showAddCustomerModal = false;

    protected $listeners = ['closeAddCustomer'];

    public function mount($activeEvent)
    {
        $this->event = $activeEvent;
        $this->venue_id = $activeEvent->venue_id;
        $this->customer_id = $activeEvent->customer_id;
        $this->event_name = $activeEvent->event_name;
        $this->description = $activeEvent->description;
        $this->start_time = $activeEvent->start_time->format('Y-m-d\TH:i');
        $this->end_time = $activeEvent->end_time->format('Y-m-d\TH:i');
        $this->expected_guests = $activeEvent->expected_guests;
        $this->status = $activeEvent->status->value;
        $this->package_amount = $activeEvent->package_amount;
        $this->advance_paid = $activeEvent->advance_paid;
        $this->special_requests = $activeEvent->special_requests;
    }

    public function submitForm()
    {
        $this->validate([
            'venue_id' => 'required|exists:hotel_venues,id',
            'customer_id' => 'nullable|exists:customers,id',
            'event_name' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'expected_guests' => 'required|integer|min:0',
            'status' => 'required|in:' . implode(',', array_column(EventStatus::cases(), 'value')),
            'package_amount' => 'required|numeric|min:0',
            'advance_paid' => 'required|numeric|min:0',
        ]);

        $this->event->update([
            'venue_id' => $this->venue_id,
            'customer_id' => $this->customer_id ?: null,
            'event_name' => $this->event_name,
            'description' => $this->description ?: null,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'expected_guests' => $this->expected_guests,
            'status' => $this->status,
            'package_amount' => $this->package_amount,
            'advance_paid' => $this->advance_paid,
            'special_requests' => $this->special_requests ?: null,
        ]);

        $this->dispatch('hideEditEvent');
        $this->dispatch('eventUpdated');

        $this->alert('success', __('hotel::modules.banquet.eventUpdated'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function closeAddCustomer()
    {
        $this->showAddCustomerModal = false;
        // Component will automatically re-render and fetch fresh customers
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-event', [
            'venues' => Venue::where('is_active', true)->get(),
            'customers' => Customer::orderBy('name')->get(),
            'statuses' => EventStatus::cases(),
        ]);
    }
}

