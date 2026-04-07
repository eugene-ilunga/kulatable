<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Event;
use Modules\Hotel\Entities\Venue;
use App\Models\Customer;
use Modules\Hotel\Enums\EventStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AddEvent extends Component
{
    use LivewireAlert;

    public $venue_id;
    public $customer_id;
    public $event_name;
    public $description;
    public $start_time;
    public $end_time;
    public $expected_guests = 0;
    public $status = EventStatus::TENTATIVE->value;
    public $package_amount = 0;
    public $advance_paid = 0;
    public $special_requests;
    public $showAddCustomerModal = false;

    protected $listeners = ['closeAddCustomer'];

    public function mount()
    {
        $this->start_time = now()->format('Y-m-d\TH:i');
        $this->end_time = now()->addHours(4)->format('Y-m-d\TH:i');
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

        Event::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'venue_id' => $this->venue_id,
            'customer_id' => $this->customer_id ?: null,
            'event_number' => Event::generateEventNumber(branch()?->id),
            'event_name' => $this->event_name,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'expected_guests' => $this->expected_guests,
            'status' => $this->status,
            'package_amount' => $this->package_amount,
            'advance_paid' => $this->advance_paid,
            'special_requests' => $this->special_requests,
            'created_by' => auth()->id(),
        ]);

        $this->reset();
        $this->start_time = now()->format('Y-m-d\TH:i');
        $this->end_time = now()->addHours(4)->format('Y-m-d\TH:i');
        $this->dispatch('hideAddEvent');
        $this->dispatch('eventAdded');

        $this->alert('success', __('hotel::modules.banquet.eventCreated'), [
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
        return view('hotel::livewire.forms.add-event', [
            'venues' => Venue::where('is_active', true)->get(),
            'customers' => Customer::orderBy('name')->get(),
            'statuses' => EventStatus::cases(),
        ]);
    }
}
