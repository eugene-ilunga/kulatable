<?php

namespace Modules\Hotel\Livewire;

use App\Models\Order;
use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Helpers\HotelHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class RoomService extends Component
{
    use LivewireAlert, WithPagination;

    public $showCreateOrderModal = false;
    public $search = '';
    public $filterStatus = '';

    public function render()
    {
        $query = Order::where('context_type', 'HOTEL_ROOM')
            ->with(['customer', 'items.menuItem'])
            ->when($this->search, function ($q) {
                $searchTerm = '%' . $this->search . '%';
                $q->where(function ($query) use ($searchTerm) {
                    $query->where('order_number', 'like', $searchTerm)
                        ->orWhereIn('context_id', function ($subQuery) use ($searchTerm) {
                            $subQuery->select('hotel_stays.id')
                                ->from('hotel_stays')
                                ->join('hotel_rooms', 'hotel_stays.room_id', '=', 'hotel_rooms.id')
                                ->where('hotel_rooms.room_number', 'like', $searchTerm);
                        });
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('order_status', $this->filterStatus);
            })
            ->orderBy('created_at', 'desc');

        return view('hotel::livewire.room-service', [
            'orders' => $query->paginate(20),
            'stays' => Stay::where('status', \Modules\Hotel\Enums\StayStatus::CHECKED_IN)
                ->with(['room', 'stayGuests.guest'])
                ->get(),
        ]);
    }
}
