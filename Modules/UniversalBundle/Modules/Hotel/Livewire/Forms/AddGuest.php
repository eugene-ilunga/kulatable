<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Guest;
use App\Models\Customer;
use App\Helper\Files;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddGuest extends Component
{
    use LivewireAlert, WithFileUploads;

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

    protected $rules = [
        'id_proof_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
    ];

    public function mount()
    {
        $this->first_name = '';
        $this->last_name = '';
    }

    public function submitForm()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:255',
            'id_type' => 'required|string|max:255',
            'id_number' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'id_proof_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
        ]);

        $idProofPath = null;
        if ($this->id_proof_file) {
            $idProofPath = Files::uploadLocalOrS3($this->id_proof_file, 'guest-id-proof');
        }

        Guest::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'customer_id' => $this->customer_id ?: null,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name ?: null,
            'email' => $this->email ?: null,
            'phone' => $this->phone,
            'id_type' => $this->id_type,
            'id_number' => $this->id_number,
            'id_proof_file' => $idProofPath,
            'address' => $this->address ?: null,
            'city' => $this->city ?: null,
            'state' => $this->state ?: null,
            'country' => $this->country ?: null,
            'postal_code' => $this->postal_code ?: null,
            'date_of_birth' => $this->date_of_birth ?: null,
            'nationality' => $this->nationality ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->reset();
        $this->dispatch('hideAddGuest');
        $this->dispatch('guestAdded');

        $this->alert('success', __('hotel::modules.guest.guestAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.add-guest', [
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }
}
