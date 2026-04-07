<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Helpers\HotelHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Carbon\Carbon;

class CheckAvailability extends Component
{
    use LivewireAlert;

    public $check_in_date = '';
    public $check_out_date = '';
    public $availability = [];

    public function mount()
    {
        $this->check_in_date = now()->format('Y-m-d');
        $this->check_out_date = now()->addDay()->format('Y-m-d');
    }

    public function checkAvailability()
    {
        $this->validate([
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
        ], [
            'check_out_date.after' => __('hotel::modules.checkAvailability.checkOutAfterCheckIn'),
        ]);

        $checkIn = Carbon::parse($this->check_in_date);
        $checkOut = Carbon::parse($this->check_out_date);

        $roomTypes = RoomType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $this->availability = [];

        foreach ($roomTypes as $roomType) {
            $available = HotelHelper::getRoomAvailability($roomType->id, $checkIn, $checkOut);
            $totalRooms = \Modules\Hotel\Entities\Room::where('room_type_id', $roomType->id)
                ->where('is_active', true)
                ->count();
            $this->availability[] = [
                'room_type' => $roomType,
                'available' => $available,
                'total' => $totalRooms,
                'occupied' => $totalRooms - $available,
            ];
        }
    }

    public function render()
    {
        return view('hotel::livewire.check-availability');
    }
}
