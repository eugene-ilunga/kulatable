<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Guest;
use App\Models\Customer;
use App\Helper\Files;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditGuest extends Component
{
    use LivewireAlert, WithFileUploads;

    public $guest;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $id_type;
    public $id_number;
    public $address;
    public $city;
    public $state;
    public $country;
    public $postal_code;
    public $date_of_birth;
    public $nationality;
    public $customer_id;
    public $notes;
    public $id_proof_file;

    public function mount($activeGuest)
    {
        $this->guest = $activeGuest;
        $this->first_name = $activeGuest->first_name;
        $this->last_name = $activeGuest->last_name;
        $this->email = $activeGuest->email;
        $this->phone = $activeGuest->phone;
        $this->id_type = $activeGuest->id_type;
        $this->id_number = $activeGuest->id_number;
        $this->address = $activeGuest->address;
        $this->city = $activeGuest->city;
        $this->state = $activeGuest->state;
        $this->country = $activeGuest->country;
        $this->postal_code = $activeGuest->postal_code;
        $this->date_of_birth = $activeGuest->date_of_birth?->format('Y-m-d');
        $this->nationality = $activeGuest->nationality;
        $this->customer_id = $activeGuest->customer_id;
        $this->notes = $activeGuest->notes;
    }

    public function submitForm()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|max:255',
            'id_type' => 'required|string|max:255',
            'id_number' => 'required|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'id_proof_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
        ]);

        $updateData = [
            'customer_id' => $this->customer_id ?: null,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name ?: null,
            'email' => $this->email ?: null,
            'phone' => $this->phone,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'country' => $this->country ?: null,
            'postal_code' => $this->postal_code ?: null,
            'date_of_birth' => $this->date_of_birth ?: null,
            'nationality' => $this->nationality ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->id_proof_file) {
            $updateData['id_proof_file'] = Files::uploadLocalOrS3($this->id_proof_file, 'guest-id-proof');
        }

        $this->guest->update($updateData);

        $this->dispatch('hideEditGuest');
        $this->dispatch('guestUpdated');

        $this->alert('success', __('hotel::modules.guest.guestUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-guest', [
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }
}
