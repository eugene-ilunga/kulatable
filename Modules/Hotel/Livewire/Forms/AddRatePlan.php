<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\RatePlan;
use Modules\Hotel\Enums\RatePlanType;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AddRatePlan extends Component
{
    use LivewireAlert;

    public $name;
    public $description;
    public $type = RatePlanType::EP->value;
    public $cancellation_hours;
    public $cancellation_charge_percent = 0;
    public $is_active = true;

    public function mount()
    {
        $this->name = '';
        $this->description = '';
    }

    public function submitForm()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_column(RatePlanType::cases(), 'value')),
            'cancellation_hours' => 'nullable|integer|min:0',
            'cancellation_charge_percent' => 'required|numeric|min:0|max:100',
        ]);

        RatePlan::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'cancellation_hours' => $this->cancellation_hours,
            'cancellation_charge_percent' => $this->cancellation_charge_percent,
            'is_active' => $this->is_active,
        ]);

        $this->reset();
        $this->dispatch('hideAddRatePlan');
        $this->dispatch('ratePlanAdded');

        $this->alert('success', __('hotel::modules.ratePlan.ratePlanAdded'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.add-rate-plan', [
            'types' => RatePlanType::cases(),
        ]);
    }
}
