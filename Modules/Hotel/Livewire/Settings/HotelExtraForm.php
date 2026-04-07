<?php

namespace Modules\Hotel\Livewire\Settings;

use Modules\Hotel\Entities\ExtraService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class HotelExtraForm extends Component
{
    use LivewireAlert;

    public ?int $extraId = null;
    public $name = '';
    public $price = 0;
    public $is_active = true;

    public function mount($extraId = null)
    {
        if ($extraId) {
            $extra = ExtraService::findOrFail($extraId);
            $this->extraId = $extra->id;
            $this->name = $extra->name;
            $this->price = $extra->price;
            $this->is_active = $extra->is_active;
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $data = [
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'name' => $this->name,
            'price' => $this->price,
            'is_active' => $this->is_active,
        ];

        if ($this->extraId) {
            ExtraService::findOrFail($this->extraId)->update($data);
            $this->alert('success', __('hotel::modules.settings.extraUpdated'), ['toast' => true, 'position' => 'top-end']);
        } else {
            ExtraService::create($data);
            $this->alert('success', __('hotel::modules.settings.extraAdded'), ['toast' => true, 'position' => 'top-end']);
        }

        $this->dispatch('hotelExtraSaved');
    }

    public function render()
    {
        return view('hotel::livewire.settings.hotel-extra-form');
    }
}
