<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\HousekeepingTask;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Enums\HousekeepingTaskType;
use Modules\Hotel\Enums\HousekeepingTaskStatus;
use App\Models\User;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AddHousekeepingTask extends Component
{
    use LivewireAlert;

    public $room_id;
    public $task_date;
    public $type = HousekeepingTaskType::CLEAN->value;
    public $assigned_to;
    public $notes;

    public function mount()
    {
        $this->task_date = now()->format('Y-m-d');
    }

    public function submitForm()
    {
        $this->validate([
            'room_id' => 'required|exists:hotel_rooms,id',
            'task_date' => 'required|date',
            'type' => 'required|in:' . implode(',', array_column(HousekeepingTaskType::cases(), 'value')),
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        HousekeepingTask::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'room_id' => $this->room_id,
            'task_date' => $this->task_date,
            'type' => $this->type,
            'status' => HousekeepingTaskStatus::PENDING,
            'assigned_to' => $this->assigned_to ?: null,
            'notes' => $this->notes ?: null,
        ]);

        $this->reset();
        $this->task_date = now()->format('Y-m-d');
        $this->dispatch('hideAddHousekeepingTask');
        $this->dispatch('housekeepingTaskAdded');

        $this->alert('success', __('hotel::modules.housekeeping.housekeepingTaskCreatedSuccessfully'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.add-housekeeping-task', [
            'rooms' => Room::where('is_active', true)->orderBy('room_number')->get(),
            'types' => HousekeepingTaskType::cases(),
            'staff' => User::where('restaurant_id', restaurant()->id)->orWhere('branch_id', branch()?->id)->get(),
        ]);
    }
}
