<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Guest;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class Guests extends Component
{
    use LivewireAlert, WithPagination;

    public $showAddGuestModal = false;
    public $showEditGuestModal = false;
    public $showViewGuestModal = false;

    /** @var int|null */
    public $viewGuestId = null;

    public $confirmDeleteGuestModal = false;
    public $activeGuest;
    public $search = '';

    public function showEditGuest($id)
    {
        $this->activeGuest = Guest::findOrFail($id);
        $this->showEditGuestModal = true;
    }

    public function showViewGuest($id): void
    {
        $this->viewGuestId = (int) $id;
        $this->showViewGuestModal = true;
    }

    public function updatedShowViewGuestModal($value): void
    {
        if (! $value) {
            $this->viewGuestId = null;
        }
    }

    #[On('hideAddGuest')]
    public function hideAddGuest()
    {
        $this->showAddGuestModal = false;
    }

    #[On('hideEditGuest')]
    public function hideEditGuest()
    {
        $this->showEditGuestModal = false;
    }

    public function showDeleteGuest($id)
    {
        $this->confirmDeleteGuestModal = true;
        $this->activeGuest = Guest::findOrFail($id);
    }

    public function deleteGuest($id)
    {
        Guest::destroy($id);
        $this->confirmDeleteGuestModal = false;

        $this->alert('success', __('hotel::modules.guest.guestDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->activeGuest = null;
    }

    public function render()
    {
        $query = Guest::query()
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('first_name');

        $viewGuest = null;
        if ($this->viewGuestId) {
            $viewGuest = Guest::with('customer')->find($this->viewGuestId);
        }

        return view('hotel::livewire.guests', [
            'guests' => $query->paginate(20),
            'viewGuest' => $viewGuest,
        ]);
    }
}
