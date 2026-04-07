<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\RatePlan;
use Modules\Hotel\Enums\RatePlanType;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\Attributes\On;

class RatePlans extends Component
{
    use LivewireAlert;

    public $showAddRatePlanModal = false;
    public $showEditRatePlanModal = false;
    public $confirmDeleteRatePlanModal = false;
    public $activeRatePlan;
    public $search = '';

    #[On('hideAddRatePlan')]
    public function hideAddRatePlan()
    {
        $this->showAddRatePlanModal = false;
    }

    public function showEditRatePlan($id)
    {
        $this->activeRatePlan = RatePlan::findOrFail($id);
        $this->showEditRatePlanModal = true;
    }

    public function showDeleteRatePlan($id)
    {
        $this->confirmDeleteRatePlanModal = true;
        $this->activeRatePlan = RatePlan::findOrFail($id);
    }

    #[On('hideEditRatePlan')]
    public function hideEditRatePlan()
    {
        $this->showEditRatePlanModal = false;
    }

    public function deleteRatePlan($id)
    {
        RatePlan::destroy($id);
        $this->confirmDeleteRatePlanModal = false;

        $this->alert('success', __('hotel::modules.ratePlan.ratePlanDeleted'), [
            'toast' => true,
            'position' => 'top-end',
        ]);

        $this->activeRatePlan = null;
    }

    public function render()
    {
        $query = RatePlan::query()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name');

        return view('hotel::livewire.rate-plans', [
            'ratePlans' => $query->get(),
        ]);
    }
}
