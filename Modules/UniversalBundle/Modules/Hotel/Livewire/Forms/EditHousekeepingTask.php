<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\HousekeepingTask;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Enums\HousekeepingTaskType;
use Modules\Hotel\Enums\HousekeepingTaskStatus;
use Modules\Hotel\Enums\RoomStatus;
use App\Models\User;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class EditHousekeepingTask extends Component
{
    use LivewireAlert;

    public $task;
    public $room_id;
    public $task_date;
    public $type;
    public $status;
    public $assigned_to;
    public $notes;

    public function mount($activeTask)
    {
        $this->task = $activeTask;
        $this->room_id = $activeTask->room_id;
        $this->task_date = $activeTask->task_date->format('Y-m-d');
        $this->type = $activeTask->type->value;
        $this->status = $activeTask->status->value;
        $this->assigned_to = $activeTask->assigned_to;
        $this->notes = $activeTask->notes;
    }

    public function submitForm()
    {
        $this->validate([
            'room_id' => 'required|exists:hotel_rooms,id',
            'task_date' => 'required|date',
            'type' => 'required|in:' . implode(',', array_column(HousekeepingTaskType::cases(), 'value')),
            'status' => 'required|in:' . implode(',', array_column(HousekeepingTaskStatus::cases(), 'value')),
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $updateData = [
            'room_id' => $this->room_id,
            'task_date' => $this->task_date,
            'type' => $this->type,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to ?: null,
            'notes' => $this->notes ?: null,
        ];

        // If status is being changed to completed, set completed_at and completed_by
        if ($this->status === HousekeepingTaskStatus::COMPLETED->value && $this->task->status !== HousekeepingTaskStatus::COMPLETED) {
            $updateData['completed_at'] = now();
            $updateData['completed_by'] = auth()->id();
        }

        $this->task->update($updateData);

        // If status is changed to completed and task type is CLEAN or DEEP_CLEAN, update room status
        if (
            $this->status === HousekeepingTaskStatus::COMPLETED->value &&
            in_array($this->task->type, [HousekeepingTaskType::CLEAN, HousekeepingTaskType::DEEP_CLEAN], true) &&
            $this->task->room
        ) {
            $this->task->room->update(['status' => RoomStatus::VACANT_CLEAN]);
        }

        $this->dispatch('hideEditHousekeepingTask');
        $this->dispatch('housekeepingTaskUpdated');

        $this->alert('success', __('hotel::modules.housekeeping.taskUpdatedSuccessfully'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-housekeeping-task', [
            'rooms' => Room::where('is_active', true)->orderBy('room_number')->get(),
            'types' => HousekeepingTaskType::cases(),
            'statuses' => HousekeepingTaskStatus::cases(),
            'staff' => User::where('restaurant_id', restaurant()->id)->orWhere('branch_id', branch()?->id)->get(),
        ]);
    }
}

