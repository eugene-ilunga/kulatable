<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\RatePlan;
use Modules\Hotel\Enums\RatePlanType;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EditRatePlan extends Component
{
    use LivewireAlert;

    public $ratePlan;
    public $name;
    public $description;
    public $type;
    public $cancellation_hours;
    public $cancellation_charge_percent;
    public $is_active;

    public function mount($activeRatePlan)
    {
        $this->ratePlan = $activeRatePlan;
        $this->name = $activeRatePlan->name;
        $this->description = $activeRatePlan->description;
        $this->type = $activeRatePlan->type->value;
        $this->cancellation_hours = $activeRatePlan->cancellation_hours;
        $this->cancellation_charge_percent = $activeRatePlan->cancellation_charge_percent;
        $this->is_active = $activeRatePlan->is_active;
    }

    public function submitForm()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_column(RatePlanType::cases(), 'value')),
            'cancellation_hours' => 'nullable|integer|min:0',
            'cancellation_charge_percent' => 'required|numeric|min:0|max:100',
        ]);

        $this->ratePlan->update([
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'cancellation_hours' => $this->cancellation_hours,
            'cancellation_charge_percent' => $this->cancellation_charge_percent,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('hideEditRatePlan');
        $this->dispatch('ratePlanUpdated');

        $this->alert('success', __('hotel::modules.ratePlan.ratePlanUpdated'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-rate-plan', [
            'types' => RatePlanType::cases(),
        ]);
    }
}
