<?php

namespace App\Livewire\Settings;

use App\Models\Branch;
use App\Models\BranchOperationalShift;
use App\Scopes\BranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class BranchOperationalShifts extends Component
{
    use LivewireAlert;

    public Branch $branch;
    public $selectedBranchId;
    public $shifts = [];
    public $showShiftModal = false;
    public $editingShiftId = null;
    public $shiftName = '';
    public $startTime = '09:00';
    public $endTime = '17:00';
    public $dayOfWeek = 'All';
    public $selectedDays = []; // For checkbox UI - can contain multiple days
    public $isActive = true;
    public $sortOrder = 0;
    public $branches = [];

    public function mount()
    {
        // Get current branch
        $this->branch = Branch::where('id', branch()->id)->with('operationalShifts')->first();
        $this->selectedBranchId = $this->branch->id;

        // Get all branches for selector (if multiple)
        $this->branches = Branch::where('restaurant_id', restaurant()->id)->get();

        $this->loadShifts();
    }

    public function updatedSelectedBranchId($value)
    {
        if ($value) {
            $this->branch = Branch::where('id', $value)->with('operationalShifts')->first();
            if ($this->branch) {
                $this->selectedBranchId = $this->branch->id;
                $this->loadShifts();
                $this->resetShiftForm();
            } else {
                $this->shifts = [];
            }
        }
    }
    
    public function getBranchNameProperty()
    {
        return $this->branch?->name ?? '';
    }

    public function loadShifts()
    {
        if (!$this->branch || !$this->branch->id) {
            $this->shifts = [];
            return;
        }
        
        // This allows viewing shifts for branches other than the session branch
        $shifts = BranchOperationalShift::withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $this->branch->id)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();
        
        // Convert to array and ensure day_of_week is properly decoded
        $this->shifts = $shifts->map(function($shift) {
            $shiftArray = $shift->toArray();
            // Ensure day_of_week is properly decoded as array
            if (isset($shiftArray['day_of_week'])) {
                if (is_string($shiftArray['day_of_week'])) {
                    $shiftArray['day_of_week'] = json_decode($shiftArray['day_of_week'], true) ?? [];
                }
            }
            return $shiftArray;
        })->toArray();
    }

    public function openAddModal()
    {
        $this->resetShiftForm();
        $this->showShiftModal = true;
    }

    public function openEditModal($shiftId)
    {
        // Reset form first to clear any previous unsaved changes
        $this->resetShiftForm();
        
        // Use withoutGlobalScope to bypass BranchScope when editing shifts from different branches
        // Always fetch fresh data from database to ensure we have the latest values
        $shift = BranchOperationalShift::withoutGlobalScope(BranchScope::class)->findOrFail($shiftId);
        
        $this->editingShiftId = $shift->id;
        $this->shiftName = $shift->shift_name ?? '';
        // Convert time to H:i format for time picker (it will display in 12-hour based on restaurant settings)
        $this->startTime = Carbon::parse($shift->start_time)->format('H:i');
        $this->endTime = Carbon::parse($shift->end_time)->format('H:i');
        // Handle JSON array for day_of_week (now stored as JSON)
        $days = $shift->day_of_week; // This is already an array due to the cast
        if (!is_array($days)) {
            $days = json_decode($days, true) ?? [];
        }
        if (empty($days)) {
            $days = [];
        }
        // Convert 'All' to all 7 days for display
        if (in_array('All', $days)) {
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        }
        // Filter out 'All' and ensure selectedDays is properly initialized as an array
        $this->selectedDays = array_values(array_filter(array_unique($days), function($day) {
            return $day !== 'All' && !empty($day);
        }));
        // For display purposes, use first day or default to Monday
        $this->dayOfWeek = !empty($this->selectedDays) ? $this->selectedDays[0] : 'Monday';
        $this->isActive = $shift->is_active;
        $this->sortOrder = $shift->sort_order;
        $this->showShiftModal = true;
        
        // Debug: Log when opening edit modal
        Log::info('Open Edit Modal', [
            'shiftId' => $shiftId,
            'selectedDays' => $this->selectedDays,
            'days_from_db' => $days
        ]);
    }

    public function closeModal()
    {
        $this->showShiftModal = false;
        $this->resetShiftForm();
    }

    public function resetShiftForm()
    {
        $this->editingShiftId = null;
        $this->shiftName = '';
        $this->startTime = '09:00';
        $this->endTime = '17:00';
        $this->dayOfWeek = 'Monday';
        $this->selectedDays = [];
        $this->isActive = true;
        // Auto-generate sort order based on existing shifts count
        $this->sortOrder = count($this->shifts);
    }

    public function updatedSelectedDays($value)
    {
        // Filter out 'All' if it exists (legacy support)
        // Ensure selectedDays is a proper array
        if (!is_array($this->selectedDays)) {
            $this->selectedDays = [];
        }
        
        // Remove 'All' and empty values, then re-index
        $this->selectedDays = array_values(array_filter($this->selectedDays, function($d) {
            return !empty($d) && is_string($d) && $d !== 'All';
        }));
        
        // Update dayOfWeek for display purposes
        $this->dayOfWeek = !empty($this->selectedDays) ? $this->selectedDays[0] : 'Monday';
    }

    public function saveShift()
    {        
        // Filter out 'All' from selectedDays for validation
        $validDays = is_array($this->selectedDays) 
            ? array_filter($this->selectedDays, function($day) {
                return !empty($day) && is_string($day) && $day !== 'All';
            })
            : [];
        
        $this->validate([
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i',
            'shiftName' => 'required|string|max:255',
            'selectedDays' => 'required|array',
            'selectedDays.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'isActive' => 'boolean',
            'sortOrder' => 'integer|min:0',
        ], [
            'startTime.required' => __('validation.required', ['attribute' => __('modules.settings.startTime')]),
            'endTime.required' => __('validation.required', ['attribute' => __('modules.settings.endTime')]),
            'shiftName.required' => __('validation.required', ['attribute' => __('modules.settings.shiftName')]),
            'selectedDays.required' => __('validation.required', ['attribute' => __('modules.settings.dayOfWeek')]),
        ]);
        
        // Additional validation: ensure at least one valid day is selected (excluding 'All')
        if (empty($validDays)) {
            $this->addError('selectedDays', __('validation.required', ['attribute' => __('modules.settings.dayOfWeek')]));
            return;
        }

        // Ensure editingShiftId is properly set - if null, we're creating a new shift
        if ($this->editingShiftId) {
            // Update existing shift - use withoutGlobalScope to bypass BranchScope
            $shift = BranchOperationalShift::withoutGlobalScope(BranchScope::class)->findOrFail($this->editingShiftId);
            
            // Ensure selectedDays is a proper array and not empty
            // Convert to array and filter out any empty values
            $selectedDaysArray = is_array($this->selectedDays) ? $this->selectedDays : [];
            $selectedDaysArray = array_filter($selectedDaysArray, function($day) {
                return !empty($day) && is_string($day) && $day !== 'All';
            });
            
            // If empty or invalid, default to all days
            if (empty($selectedDaysArray)) {
                $daysToSave = ['All'];
            } else {
                $daysToSave = array_values(array_unique($selectedDaysArray)); // Re-index and remove duplicates
            }
            
            $shift->update([
                'shift_name' => $this->shiftName ?: null,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'day_of_week' => $daysToSave, // Model will auto-encode via cast
                'is_active' => $this->isActive,
                'sort_order' => $this->sortOrder,
                'restaurant_id' => $this->branch->restaurant_id,
            ]);
            
            // Refresh the shift to ensure we have the latest data
            $shift->refresh();

            $this->alert('success', __('messages.shiftUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
            ]);
        } else {
            // Create new shift - auto-generate sort_order if not set
            $sortOrder = $this->sortOrder ?? count($this->shifts);
            
            // Ensure selectedDays is a proper array and not empty
            $selectedDaysArray = is_array($this->selectedDays) ? $this->selectedDays : [];
            $selectedDaysArray = array_filter($selectedDaysArray, function($day) {
                return !empty($day) && is_string($day) && $day !== 'All';
            });
            
            // If empty or invalid, default to all days
            $daysToSave = !empty($selectedDaysArray) 
                ? array_values(array_unique($selectedDaysArray)) // Re-index array
                : ['All'];
            
            BranchOperationalShift::create([
                'branch_id' => $this->branch->id,
                'restaurant_id' => $this->branch->restaurant_id,
                'shift_name' => $this->shiftName ?: null,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'day_of_week' => $daysToSave, // Model will auto-encode via cast
                'is_active' => $this->isActive,
                'sort_order' => $sortOrder,
            ]);

            $this->alert('success', __('messages.shiftCreated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
            ]);
        }

        $this->loadShifts();
        $this->closeModal();
    }

    public function deleteShift($shiftId)
    {
        // Use withoutGlobalScope to bypass BranchScope when deleting shifts from different branches
        $shift = BranchOperationalShift::withoutGlobalScope(BranchScope::class)->findOrFail($shiftId);
        $shift->delete();

        $this->alert('success', __('messages.shiftDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
        ]);

        $this->loadShifts();
    }

    public function render()
    {
        // Sync with session branch if it changed (e.g., from sidebar)
        // Only sync if selectedBranchId matches session branch (meaning it was changed from sidebar)
        $currentBranchId = branch()->id;
        
        // If branch is not set, initialize from session
        if (!$this->branch) {
            $this->branch = Branch::where('id', $currentBranchId)->with('operationalShifts')->first();
            if ($this->branch) {
                $this->selectedBranchId = $this->branch->id;
                $this->loadShifts();
            } else {
                $this->shifts = [];
            }
        } 
        // If selectedBranchId is set and doesn't match current branch, update branch (manual selection from dropdown)
        elseif ($this->selectedBranchId && $this->selectedBranchId != $this->branch->id) {
            $this->branch = Branch::where('id', $this->selectedBranchId)->with('operationalShifts')->first();
            if ($this->branch) {
                $this->loadShifts();
            } else {
                $this->shifts = [];
            }
        }
        // If session branch changed and selectedBranchId matches session (changed from sidebar), sync
        elseif ($this->branch && $this->branch->id !== $currentBranchId && $this->selectedBranchId == $currentBranchId) {
            $this->branch = Branch::where('id', $currentBranchId)->with('operationalShifts')->first();
            if ($this->branch) {
                $this->selectedBranchId = $this->branch->id;
                $this->loadShifts();
            } else {
                $this->shifts = [];
            }
        }
        // Ensure shifts are loaded if branch exists but shifts are empty
        elseif ($this->branch && empty($this->shifts)) {
            $this->loadShifts();
        }

        // Get business day info for display (similar to Orders component)
        $businessDayInfo = null;
        if ($this->branch) {
            $boundaries = getBusinessDayBoundaries($this->branch, now());
            $restaurantTimezone = $this->branch->restaurant->timezone ?? 'UTC';
            $timeFormat = $this->branch->restaurant->time_format ?? 'h:i A';
            
            // Use business_day_end for display (shows full business day end, not "now")
            $displayEnd = isset($boundaries['business_day_end']) 
                ? $boundaries['business_day_end'] 
                : $boundaries['end'];
            
            $businessDayStart = $boundaries['start'];
            $calendarDate = $boundaries['calendar_date'];
            
            // If business day ends on next calendar day, show info
            if ($displayEnd->toDateString() !== $calendarDate) {
                $businessDayInfo = [
                    'start' => $businessDayStart->format($timeFormat),
                    'end' => $displayEnd->format($timeFormat),
                    'end_date' => $displayEnd->toDateString(),
                    'calendar_date' => $calendarDate,
                    'extends_to_next_day' => true,
                ];
            } else {
                // Business day is within same calendar day
                $businessDayInfo = [
                    'start' => $businessDayStart->format($timeFormat),
                    'end' => $displayEnd->format($timeFormat),
                    'end_date' => $displayEnd->toDateString(),
                    'calendar_date' => $calendarDate,
                    'extends_to_next_day' => false,
                ];
            }
        }

        return view('livewire.settings.branch-operational-shifts', [
            'businessDayInfo' => $businessDayInfo,
            'branch' => $this->branch, // Ensure branch is passed to view
            'branchName' => $this->branchName, // Pass computed branch name for reactivity
            'shifts' => $this->shifts, // Explicitly pass shifts to ensure reactivity
        ]);
    }
}
