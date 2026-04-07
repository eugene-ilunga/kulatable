<?php

namespace Modules\Hotel\Livewire\Settings;

use Modules\Hotel\Entities\Tax;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class HotelTaxForm extends Component
{
    use LivewireAlert;

    public ?int $taxId = null;
    public $name = '';
    public $rate = 0;
    public $is_active = true;

    public function mount($taxId = null)
    {
        if ($taxId) {
            $tax = Tax::findOrFail($taxId);
            $this->taxId = $tax->id;
            $this->name = $tax->name;
            $this->rate = $tax->rate;
            $this->is_active = $tax->is_active;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $data = [
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'name' => $this->name,
            'rate' => $this->rate,
            'is_active' => $this->is_active,
        ];

        if ($this->taxId) {
            Tax::findOrFail($this->taxId)->update($data);
            $this->alert('success', __('hotel::modules.settings.taxUpdated'), ['toast' => true, 'position' => 'top-end']);
        } else {
            Tax::create($data);
            $this->alert('success', __('hotel::modules.settings.taxAdded'), ['toast' => true, 'position' => 'top-end']);
        }

        $this->dispatch('hotelTaxSaved');
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('hotel::livewire.settings.hotel-tax-form');
    }
}
