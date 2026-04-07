<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\HousekeepingTask;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Enums\HousekeepingTaskType;
use Modules\Hotel\Enums\HousekeepingTaskStatus;
use Modules\Hotel\Enums\RoomStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Carbon\Carbon;

class Housekeeping extends Component
{
    use LivewireAlert, WithPagination;

    public $showAddTaskModal = false;
    public $showEditTaskModal = false;
    public $confirmDeleteTaskModal = false;
    public $confirmCompleteTaskModal = false;
    public $activeTask;
    public $filterDate = '';
    public $filterStatus = '';
    public $filterRoom = '';

    public function mount()
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    #[On('hideAddHousekeepingTask')]
    public function hideAddHousekeepingTask()
    {
        $this->showAddTaskModal = false;
    }

    #[On('hideEditHousekeepingTask')]
    public function hideEditHousekeepingTask()
    {
        $this->showEditTaskModal = false;
    }

    public function showEditTask($id)
    {
        $this->activeTask = HousekeepingTask::with('room')->findOrFail($id);
        $this->showEditTaskModal = true;
    }

    public function showDeleteTask($id)
    {
        $this->confirmDeleteTaskModal = true;
        $this->activeTask = HousekeepingTask::findOrFail($id);
    }

    public function deleteTask($id)
    {
        HousekeepingTask::destroy($id);
        $this->confirmDeleteTaskModal = false;

        $this->alert('success', __('hotel::modules.housekeeping.taskDeletedSuccessfully'), [
            'toast' => true,
            'position' => 'top-end',
        ]);

        $this->activeTask = null;
    }

    public function showCompleteTask($id)
    {
        $this->confirmCompleteTaskModal = true;
        $this->activeTask = HousekeepingTask::findOrFail($id);
    }

    public function completeTask($id)
    {
        $task = HousekeepingTask::findOrFail($id);
        
        $task->update([
            'status' => HousekeepingTaskStatus::COMPLETED,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        if (
            $task->room &&
            in_array($task->type, [HousekeepingTaskType::CLEAN, HousekeepingTaskType::DEEP_CLEAN], true)
        ) {
            // Update room status to VACANT_CLEAN when cleaning or deep-clean task is completed
            $task->room->update(['status' => RoomStatus::VACANT_CLEAN]);
        }

        $this->confirmCompleteTaskModal = false;
        $this->activeTask = null;

        $this->alert('success', __('hotel::modules.housekeeping.taskCompletedSuccessfully'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        $query = HousekeepingTask::with(['room.roomType', 'assignedTo'])
            ->when($this->filterDate, function ($q) {
                $q->where('task_date', $this->filterDate);
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterRoom, function ($q) {
                $q->where('room_id', $this->filterRoom);
            })
            ->orderBy('task_date', 'desc')
            ->orderBy('status');

        return view('hotel::livewire.housekeeping', [
            'tasks' => $query->paginate(20),
            'rooms' => Room::where('is_active', true)->orderBy('room_number')->get(),
            'statuses' => HousekeepingTaskStatus::cases(),
            'types' => HousekeepingTaskType::cases(),
        ]);
    }
}
