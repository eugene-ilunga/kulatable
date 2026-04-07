<?php

namespace Modules\Hotel\Livewire\Settings;

use Modules\Hotel\Entities\ExtraService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\Attributes\On;

class HotelExtraServiceSettings extends Component
{
    use LivewireAlert;

    protected $listeners = ['refreshHotelExtras' => 'refresh'];

    public $showAddModal = false;
    public $showEditModal = false;
    public $confirmDeleteModal = false;
    public $activeExtra = null;

    public function refresh()
    {
        $this->reset(['showEditModal', 'activeExtra']);
    }

    public function showAdd()
    {
        $this->activeExtra = null;
        $this->showAddModal = true;
    }

    public function showEdit($id)
    {
        $this->activeExtra = ExtraService::findOrFail($id);
        $this->showEditModal = true;
    }

    public function showDelete($id)
    {
        $this->activeExtra = ExtraService::findOrFail($id);
        $this->confirmDeleteModal = true;
    }

    public function deleteExtra($id)
    {
        ExtraService::destroy($id);
        $this->activeExtra = null;
        $this->confirmDeleteModal = false;
        $this->dispatch('refreshHotelExtras');
        $this->alert('success', __('hotel::modules.settings.extraDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
        ]);
    }

    #[On('hotelExtraSaved')]
    public function hideModals()
    {
        $this->showAddModal = false;
        $this->showEditModal = false;
        $this->activeExtra = null;
        $this->dispatch('refreshHotelExtras');
    }

    public function render()
    {
        $extras = ExtraService::where('restaurant_id', restaurant()->id)
            ->orderBy('name')
            ->get();

        return view('hotel::livewire.settings.hotel-extra-service-settings', [
            'extras' => $extras,
        ]);
    }
}
