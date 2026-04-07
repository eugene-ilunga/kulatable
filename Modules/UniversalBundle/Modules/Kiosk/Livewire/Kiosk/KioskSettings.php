<?php

namespace Modules\Kiosk\Livewire\Kiosk;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Kiosk\Entities\Kiosk;

class KioskSettings extends Component
{
    use LivewireAlert;

    public $isEditing = false;
    public $formMode = 'add';

    public $activeKioskId = null;
    public $activeKiosk = null;
    public $confirmDeleteKioskModal = false;

    // Form fields
    public $kioskName = '';
    public $kioskCode = '';
    public $isActive = true;
    public $requireName = true;
    public $requireEmail = false;
    public $requirePhone = true;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->isEditing = false;
        $this->formMode = 'add';
        $this->activeKioskId = null;
        $this->activeKiosk = null;

        $this->kioskName = '';
        $this->kioskCode = '';
        $this->isActive = true;
        $this->requireName = true;
        $this->requireEmail = false;
        $this->requirePhone = true;
    }

    public function createMode(): void
    {
        $this->resetForm();
        $this->formMode = 'add';
        $this->isEditing = true;
    }

    public function showEditKiosk(int $id): void
    {
        $this->editMode($id);
    }

    public function editMode(int $id): void
    {
        $kiosk = Kiosk::findOrFail($id);
        $this->activeKioskId = $kiosk->id;
        $this->activeKiosk = $kiosk;

        $this->kioskName = $kiosk->name;
        $this->kioskCode = $kiosk->code;
        $this->isActive = (bool) ($kiosk->is_active ?? true);
        $this->requireName = (bool) ($kiosk->require_name ?? true);
        $this->requireEmail = (bool) ($kiosk->require_email ?? false);
        $this->requirePhone = (bool) ($kiosk->require_phone ?? true);

        $this->formMode = 'edit';
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function showDeleteKiosk(int $id): void
    {
        $this->activeKiosk = Kiosk::findOrFail($id);
        $this->activeKioskId = $id;
        $this->confirmDeleteKioskModal = true;
    }

    public function deleteKiosk(): void
    {
        Kiosk::destroy($this->activeKioskId);
        $this->activeKiosk = null;
        $this->activeKioskId = null;
        $this->confirmDeleteKioskModal = false;

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function saveKiosk(): void
    {
        $branchId = branch()->id;

        if ($this->formMode === 'add') {
            $this->validate([
                'kioskName' => 'required|unique:kiosks,name,NULL,id,branch_id,' . $branchId,
            ]);

            Kiosk::create([
                'branch_id' => $branchId,
                'name' => $this->kioskName,
                'code' => $this->generateUniqueKioskCode(),
                'is_active' => (bool) $this->isActive,
                'require_name' => (bool) $this->requireName,
                'require_email' => (bool) $this->requireEmail,
                'require_phone' => (bool) $this->requirePhone,
            ]);

            $this->alert('success', __('messages.settingsUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        } else {
            $this->validate([
                'kioskName' => 'required|unique:kiosks,name,' . $this->activeKioskId . ',id,branch_id,' . $branchId,
                'kioskCode' => 'required|alpha_dash|unique:kiosks,code,' . $this->activeKioskId . ',id,branch_id,' . $branchId,
            ]);

            Kiosk::where('id', $this->activeKioskId)->update([
                'branch_id' => $branchId,
                'name' => $this->kioskName,
                'is_active' => (bool) $this->isActive,
                'require_name' => (bool) $this->requireName,
                'require_email' => (bool) $this->requireEmail,
                'require_phone' => (bool) $this->requirePhone,
            ]);

            $this->alert('success', __('messages.settingsUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }

        $this->resetForm();
    }

    private function generateUniqueKioskCode(): string
    {        
        do {
            $code = (string) random_int(10000, 99999);
            $exists = Kiosk::withoutGlobalScopes()
                          ->where('code', $code)
                          ->exists();
        } while ($exists);
        
        return $code;
    }

    public function render()
    {
        $kiosks = Kiosk::where('branch_id', branch()->id)->get();

        return view('kiosk::livewire.kiosk.kiosk-settings', [
            'kiosks' => $kiosks,
        ]);
    }
}
