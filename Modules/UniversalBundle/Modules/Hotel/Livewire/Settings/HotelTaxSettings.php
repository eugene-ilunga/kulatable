<?php

namespace Modules\Hotel\Livewire\Settings;

use Modules\Hotel\Entities\Tax;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\Attributes\On;

class HotelTaxSettings extends Component
{
    use LivewireAlert;

    protected $listeners = ['refreshHotelTaxes' => 'refresh'];

    public $showAddModal = false;
    public $showEditModal = false;
    public $confirmDeleteModal = false;
    public $activeTax = null;

    public function refresh()
    {
        $this->reset(['showEditModal', 'activeTax']);
    }

    public function showAdd()
    {
        $this->activeTax = null;
        $this->showAddModal = true;
    }

    public function showEdit($id)
    {
        $this->activeTax = Tax::findOrFail($id);
        $this->showEditModal = true;
    }

    public function showDelete($id)
    {
        $this->activeTax = Tax::findOrFail($id);
        $this->confirmDeleteModal = true;
    }

    public function deleteTax($id)
    {
        Tax::destroy($id);
        $this->activeTax = null;
        $this->confirmDeleteModal = false;
        $this->dispatch('refreshHotelTaxes');
        $this->alert('success', __('hotel::modules.settings.taxDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
        ]);
    }

    #[On('hotelTaxSaved')]
    public function hideModals()
    {
        $this->showAddModal = false;
        $this->showEditModal = false;
        $this->activeTax = null;
        $this->dispatch('refreshHotelTaxes');
    }

    public function render()
    {
        $taxes = Tax::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();

        return view('hotel::livewire.settings.hotel-tax-settings', [
            'taxes' => $taxes,
        ]);
    }
}
