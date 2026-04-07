<div>
    <div class="p-4 bg-white block sm:flex items-center justify-between dark:bg-gray-800 dark:border-gray-700">
        <div class="w-full mb-1">
            <div class="mb-4">
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ __('hotel::modules.housekeeping.housekeepingTasks') }}</h1>
            </div>

            <div class="items-center justify-between block sm:flex gap-2 mb-4 mt-4">
                <div class="flex flex-col sm:flex-row gap-2 flex-1">
                    <x-input type="date" wire:model.live="filterDate" class="block w-full sm:w-40" />
                    
                    <x-select wire:model.live="filterStatus" class="block w-full sm:w-48">
                        <option value="">{{ __('hotel::modules.housekeeping.allStatuses') }}</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model.live="filterRoom" class="block w-full sm:w-48">
                        <option value="">{{ __('hotel::modules.housekeeping.allRooms') }}</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                        @endforeach
                    </x-select>
                </div>

                @if(user_can('Create Hotel Housekeeping Task'))
                <div class="inline-flex gap-x-4 mb-4 sm:mb-0">
                    <x-button type='button' wire:click="$toggle('showAddTaskModal')">{{ __('hotel::modules.housekeeping.addTask') }}</x-button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden shadow">
                    <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.housekeeping.date') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.housekeeping.room') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.housekeeping.type') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.housekeeping.assignedTo') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">{{ __('hotel::modules.housekeeping.status') }}</th>
                                <th class="py-2.5 px-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400 text-right">{{ __('hotel::modules.housekeeping.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($tasks as $task)
                            <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $task->task_date->format('M d, Y') }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $task->room->room_number ?? '--' }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $task->type->label() }}
                                </td>
                                <td class="py-2.5 px-4 text-sm text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $task->assignedTo->name ?? __('hotel::modules.housekeeping.unassigned') }}
                                </td>
                                <td class="py-2.5 px-4 text-base whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        ];
                                        $color = $statusColors[$task->status->value] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $color }}">
                                        {{ $task->status->label() }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 space-x-2 whitespace-nowrap text-right">
                                    @if($task->status->value === 'pending')
                                        @if(user_can('Update Hotel Housekeeping Task'))
                                        <x-secondary-button-table wire:click='showEditTask({{ $task->id }})'>
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg>
                                            {{ __('hotel::modules.housekeeping.editTask') }}
                                        </x-secondary-button-table>
                                        @endif

                                        @if(user_can('Delete Hotel Housekeeping Task'))
                                        <x-danger-button-table wire:click="showDeleteTask({{ $task->id }})">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                        </x-danger-button-table>
                                        @endif
                                    @endif

                                    @if($task->status->value !== 'completed' && $task->status->value !== 'cancelled' && user_can('Complete Hotel Housekeeping Task'))
                                    <x-button wire:click="showCompleteTask({{ $task->id }})" size="sm">{{ __('hotel::modules.housekeeping.complete') }}</x-button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="py-8 px-4 text-center text-gray-900 dark:text-gray-400" colspan="6">
                                    <p class="text-base font-medium">{{ __('hotel::modules.housekeeping.noHousekeepingTasksFound') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4">
        {{ $tasks->links() }}
    </div>

    <x-right-modal wire:model.live="showAddTaskModal">
        <x-slot name="title">{{ __('hotel::modules.housekeeping.addHousekeepingTask') }}</x-slot>
        <x-slot name="content">
            @if ($showAddTaskModal)
                <livewire:hotel::forms.add-housekeeping-task />
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showAddTaskModal', false)">{{ __('hotel::modules.housekeeping.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    @if ($activeTask)
    <x-right-modal wire:model.live="showEditTaskModal">
        <x-slot name="title">{{ __('hotel::modules.housekeeping.editTask') }}</x-slot>
        <x-slot name="content">
            <livewire:hotel::forms.edit-housekeeping-task :activeTask="$activeTask" :key="str()->random(50)" />
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showEditTaskModal', false)">{{ __('hotel::modules.housekeeping.close') }}</x-secondary-button>
        </x-slot>
    </x-right-modal>

    <x-confirmation-modal wire:model="confirmDeleteTaskModal">
        <x-slot name="title">{{ __('hotel::modules.housekeeping.deleteTask') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.housekeeping.deleteTaskMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmDeleteTaskModal')">{{ __('hotel::modules.housekeeping.cancel') }}</x-secondary-button>
            <x-danger-button class="ml-3" wire:click='deleteTask({{ $activeTask->id }})'>{{ __('hotel::modules.housekeeping.delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <x-confirmation-modal wire:model="confirmCompleteTaskModal">
        <x-slot name="title">{{ __('hotel::modules.housekeeping.completeTask') }}</x-slot>
        <x-slot name="content">{{ __('hotel::modules.housekeeping.completeTaskMessage') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmCompleteTaskModal')">{{ __('hotel::modules.housekeeping.cancel') }}</x-secondary-button>
            <x-button class="ml-3" wire:click='completeTask({{ $activeTask->id }})'>{{ __('hotel::modules.housekeeping.complete') }}</x-button>
        </x-slot>
    </x-confirmation-modal>
    @endif
</div>
