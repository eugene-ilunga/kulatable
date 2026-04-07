<?php

namespace Modules\MultiPOS\Livewire\Restaurant;

use Livewire\Component;
use Modules\MultiPOS\Entities\PosMachine;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Setting extends Component
{
    use LivewireAlert;

    public $currentBranch;
    public $editingMachineId = null;
    public $editingMachineAlias = '';
    public $confirmDeleteMachineModal = false;
    public $machineToDelete = null;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        abort_unless(in_array('MultiPOS', restaurant_modules()), 403);
        abort_if(!user_can('Manage MultiPOS Machines'), 403);
        $this->currentBranch = branch();
    }

    public function approveMachine($machineId)
    {
        $machine = PosMachine::where('branch_id', $this->currentBranch->id)
            ->findOrFail($machineId);

        if ($machine->status !== 'pending') {
            $this->alert('error', __('multipos::messages.settings.machine_not_pending'));
            return;
        }

        // Use the helper function to get the user
        $user = user();

        if (!$user) {
            $this->alert('error', __('multipos::messages.settings.user_not_found'));
            return;
        }

        $machine->activate($user);

        $this->alert('success', __('multipos::messages.settings.machine_approved'));
    }

    public function disableMachine($machineId)
    {
        $machine = PosMachine::where('branch_id', $this->currentBranch->id)
            ->findOrFail($machineId);

        if ($machine->status === 'declined') {
            $this->alert('error', __('multipos::messages.settings.machine_already_declined'));
            return;
        }

        $machine->decline();

        $this->alert('success', __('multipos::messages.settings.machine_declined'));
    }

    public function editMachine($machineId)
    {
        $machine = PosMachine::where('branch_id', $this->currentBranch->id)
            ->findOrFail($machineId);

        $this->editingMachineId = $machineId;
        $this->editingMachineAlias = $machine->alias ?? '';
    }

    public function cancelEdit()
    {
        $this->editingMachineId = null;
        $this->editingMachineAlias = '';
    }

    public function saveEdit()
    {
        if (!$this->editingMachineAlias) {
            $this->alert('error', __('multipos::messages.settings.alias_required'));
            return;
        }

        $machine = PosMachine::where('branch_id', $this->currentBranch->id)
            ->findOrFail($this->editingMachineId);

        $machine->update(['alias' => $this->editingMachineAlias]);

        $this->editingMachineId = null;
        $this->editingMachineAlias = '';

        $this->alert('success', __('multipos::messages.settings.machine_updated'));
    }

    public function showDeleteMachine($machineId)
    {
        $this->machineToDelete = $machineId;
        $this->confirmDeleteMachineModal = true;
    }

    public function deleteMachine()
    {
        if (!$this->machineToDelete) {
            return;
        }

        $machine = PosMachine::where('branch_id', $this->currentBranch->id)
            ->findOrFail($this->machineToDelete);

        // Get device_id before deletion for potential cookie clearing
        $deletedDeviceId = $machine->device_id;
        $deletedBranchId = $machine->branch_id;

        $machine->delete();

        // Check if this was the last machine with this device_id for this branch
        // If so, and if the current request is from the same browser (same device_id in cookie),
        // then we can clear the cookie for this browser
        if ($deletedDeviceId) {
            $cookieName = config('multipos.cookie.name', 'pos_token');
            $currentDeviceId = request()->cookie($cookieName);
            
            // Only clear cookie if:
            // 1. The deleted machine's device_id matches the current browser's cookie
            // 2. There are no other machines with this device_id for this branch
            $otherMachinesCount = PosMachine::where('device_id', $deletedDeviceId)
                ->where('branch_id', $deletedBranchId)
                ->count();
            
            if ($currentDeviceId === $deletedDeviceId && $otherMachinesCount === 0) {
                // This browser had the machine, and it was the last one - clear the cookie
                $this->dispatch('clear_pos_cookie', name: $cookieName, device_id: $deletedDeviceId);
            }
        }

        $this->confirmDeleteMachineModal = false;
        $this->machineToDelete = null;

        $this->alert('success', __('multipos::messages.settings.machine_deleted'));
    }

    public function render()
    {
        $machines = PosMachine::with(['creator', 'approver'])
            ->where('branch_id', $this->currentBranch->id)
            ->latest()
            ->paginate(15);

        return view('multipos::livewire.restaurant.setting', compact('machines'));
    }
}
