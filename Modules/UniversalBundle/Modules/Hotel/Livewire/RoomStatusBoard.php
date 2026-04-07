<?php

namespace Modules\Hotel\Livewire;

use Carbon\Carbon;
use Modules\Hotel\Entities\Folio;
use Modules\Hotel\Entities\FolioLine;
use Modules\Hotel\Entities\FolioPayment;
use Modules\Hotel\Entities\Guest;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Entities\StayGuest;
use Modules\Hotel\Enums\FolioLineType;
use Modules\Hotel\Enums\FolioStatus;
use Modules\Hotel\Enums\RoomStatus;
use Modules\Hotel\Enums\StayStatus;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class RoomStatusBoard extends Component
{
    use LivewireAlert;

    public $selectedFloor = '';
    public $filterStatus = '';
    public $filterRoomType = '';

    // Modal visibility & selected room
    public $showCheckInModal = false;
    public $selectedRoomId = null;

    // Primary guest for walk-in
    public $checkInGuest = [
        'first_name' => '',
        'last_name'  => '',
        'email'      => '',
        'phone'      => '',
        'id_type'    => '',
        'id_number'  => '',
    ];

    public $additionalGuests = [];

    // Stay details
    public $adults = 1;
    public $children = 0;
    public $checkOutDate = '';
    public $checkOutTime = '';

    // Pricing
    public $ratePerNight = 0;
    public $totalRoomCharge = 0;
    public $advancePaid = 0;

    public function mount()
    {
        if (request()->has('filterStatus')) {
            $this->filterStatus = request()->query('filterStatus');
        }
    }

    protected function blankGuest(): array
    {
        return [
            'first_name' => '',
            'last_name'  => '',
            'email'      => '',
            'phone'      => '',
            'id_type'    => '',
            'id_number'  => '',
        ];
    }

    public function addGuest(): void
    {
        $this->additionalGuests[] = $this->blankGuest();
    }

    public function removeGuest(int $index): void
    {
        unset($this->additionalGuests[$index]);
        $this->additionalGuests = array_values($this->additionalGuests);
    }

    public function updatedCheckOutDate(): void
    {
        $this->recalculateRoomCharge();
    }

    public function updatedRatePerNight(): void
    {
        $this->recalculateRoomCharge();
    }

    protected function recalculateRoomCharge(): void
    {
        if ($this->checkOutDate && (float) $this->ratePerNight >= 0) {
            $nights = $this->computeNights();
            $this->totalRoomCharge = round((float) $this->ratePerNight * $nights, 2);
        }
    }

    protected function computeNights(): int
    {
        if (!$this->checkOutDate) {
            return 1;
        }

        return max(1, now()->startOfDay()->diffInDays(Carbon::parse($this->checkOutDate)));
    }

    public function openCheckInModal($roomId): void
    {
        $room = Room::with('roomType')->find($roomId);
        if (!$room) {
            return;
        }

        $checkOutAt = now()->addHours(5);

        $this->selectedRoomId  = $roomId;
        $this->checkOutDate    = $checkOutAt->format('Y-m-d');
        $this->checkOutTime    = $checkOutAt->format('H:i');
        $this->ratePerNight    = $room->roomType->base_rate ?? 0;
        $this->totalRoomCharge = (float) $this->ratePerNight * 1; // 1 night default
        $this->advancePaid     = 0;
        $this->checkInGuest    = $this->blankGuest();
        $this->additionalGuests = [];
        $this->adults   = 1;
        $this->children = 0;
        $this->resetValidation();
        $this->showCheckInModal = true;
    }

    public function closeCheckInModal(): void
    {
        $this->showCheckInModal  = false;
        $this->selectedRoomId    = null;
        $this->checkInGuest      = $this->blankGuest();
        $this->additionalGuests  = [];
        $this->adults            = 1;
        $this->children          = 0;
        $this->checkOutDate      = '';
        $this->checkOutTime      = '';
        $this->ratePerNight      = 0;
        $this->totalRoomCharge   = 0;
        $this->advancePaid       = 0;
        $this->resetValidation();
    }

    public function processRoomCheckIn(): void
    {
        $this->validate([
            'checkInGuest.first_name' => 'required|string|max:255',
            'checkInGuest.last_name'  => 'nullable|string|max:255',
            'checkInGuest.email'      => 'nullable|email|max:255',
            'checkInGuest.phone'      => 'required|string|max:30',
            'checkInGuest.id_type'    => 'required|string|max:50',
            'checkInGuest.id_number'  => 'required|string|max:100',
            'checkOutDate'            => 'required|date|after_or_equal:today',
            'checkOutTime'            => 'nullable|date_format:H:i',
            'ratePerNight'            => 'required|numeric|min:0',
            'totalRoomCharge'         => 'required|numeric|min:0',
            'advancePaid'             => 'nullable|numeric|min:0',
            'adults'                  => 'required|integer|min:1',
            'children'                => 'nullable|integer|min:0',
        ]);

        // Validate any additional guests that have a name filled in
        $guestRules = [];
        foreach ($this->additionalGuests as $i => $g) {
            if (empty(trim($g['first_name'] ?? ''))) {
                continue;
            }
            $guestRules["additionalGuests.{$i}.first_name"] = 'required|string|max:255';
            $guestRules["additionalGuests.{$i}.email"]      = 'nullable|email|max:255';
            $guestRules["additionalGuests.{$i}.phone"]      = 'required|string|max:30';
            $guestRules["additionalGuests.{$i}.id_type"]    = 'required|string|max:50';
            $guestRules["additionalGuests.{$i}.id_number"]  = 'required|string|max:100';
        }
        if (!empty($guestRules)) {
            $this->validate($guestRules);
        }

        $room = Room::with('roomType')->findOrFail($this->selectedRoomId);

        if ($room->status !== RoomStatus::VACANT_CLEAN) {
            $this->alert('error', __('hotel::modules.checkIn.roomNotAvailable', ['number' => $room->room_number]), [
                'toast'    => true,
                'position' => 'top-end',
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // Create primary guest record for this walk-in
            $primaryGuest = Guest::create([
                'restaurant_id' => restaurant()->id,
                'branch_id'     => branch()?->id,
                'first_name'    => trim($this->checkInGuest['first_name']),
                'last_name'     => trim($this->checkInGuest['last_name'] ?? '') ?: null,
                'email'         => trim($this->checkInGuest['email'] ?? '') ?: null,
                'phone'         => trim($this->checkInGuest['phone']),
                'id_type'       => $this->checkInGuest['id_type'] ?: null,
                'id_number'     => trim($this->checkInGuest['id_number']),
            ]);

            $nights = $this->computeNights();

            // Create walk-in stay (reservation_id is nullable in schema)
            $stay = Stay::create([
                'restaurant_id'        => restaurant()->id,
                'branch_id'            => branch()?->id,
                'reservation_id'       => null,
                'room_id'              => $room->id,
                'stay_number'          => Stay::generateStayNumber(branch()?->id),
                'check_in_at'          => now(),
                'expected_checkout_at' => Carbon::parse($this->checkOutDate)
                    ->setTimeFromTimeString($this->checkOutTime ?? '11:00'),
                'status'               => StayStatus::CHECKED_IN,
                'adults'               => (int) $this->adults,
                'children'             => (int) ($this->children ?? 0),
                'checked_in_by'        => auth()->id(),
            ]);

            // Primary stay guest
            StayGuest::create([
                'stay_id'    => $stay->id,
                'guest_id'   => $primaryGuest->id,
                'is_primary' => true,
            ]);

            // Additional guests
            foreach ($this->additionalGuests as $guestData) {
                $firstName = trim($guestData['first_name'] ?? '');
                if (!$firstName) {
                    continue;
                }

                $addGuest = Guest::create([
                    'restaurant_id' => restaurant()->id,
                    'branch_id'     => branch()?->id,
                    'first_name'    => $firstName,
                    'last_name'     => trim($guestData['last_name'] ?? '') ?: null,
                    'email'         => trim($guestData['email'] ?? '') ?: null,
                    'phone'         => trim($guestData['phone'] ?? '') ?: null,
                    'id_type'       => $guestData['id_type'] ?: null,
                    'id_number'     => trim($guestData['id_number'] ?? '') ?: null,
                ]);

                StayGuest::create([
                    'stay_id'    => $stay->id,
                    'guest_id'   => $addGuest->id,
                    'is_primary' => false,
                ]);
            }

            // Create folio
            $folio = Folio::create([
                'restaurant_id' => restaurant()->id,
                'branch_id'     => branch()?->id,
                'stay_id'       => $stay->id,
                'folio_number'  => Folio::generateFolioNumber(branch()?->id),
                'status'        => FolioStatus::OPEN,
                'opened_at'     => now(),
            ]);

            // Post room charge line
            $totalCharge = (float) $this->totalRoomCharge;
            FolioLine::create([
                'folio_id'        => $folio->id,
                'type'            => FolioLineType::ROOM_CHARGE,
                'description'     => 'Room Charge (Room ' . $room->room_number . ' for ' . $nights . ' night(s))',
                'amount'          => $totalCharge,
                'tax_amount'      => 0,
                'discount_amount' => 0,
                'net_amount'      => $totalCharge,
                'posting_date'    => now(),
                'posted_by'       => auth()->id(),
            ]);

            $folio->recalculateTotals();

            // Apply advance payment (stored as 'advance' to match checkout display logic)
            $advancePaid = (float) ($this->advancePaid ?? 0);
            if ($advancePaid > 0) {
                FolioPayment::create([
                    'folio_id'              => $folio->id,
                    'payment_method'        => 'advance',
                    'amount'                => $advancePaid,
                    'transaction_reference' => null,
                    'received_by'           => auth()->id(),
                ]);
                $folio->recalculateTotals();
            }

            // Mark room occupied
            $room->update(['status' => RoomStatus::OCCUPIED]);

            DB::commit();

            $this->closeCheckInModal();

            $this->alert('success', __('hotel::modules.checkIn.guestCheckedInSuccessfully'), [
                'toast'    => true,
                'position' => 'top-end',
            ]);

            $this->dispatch('reservationCheckedIn');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->alert('error', $e->getMessage(), [
                'toast'    => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function render()
    {
        $query = Room::with(['roomType', 'currentStay.stayGuests.guest'])
            ->where('is_active', true)
            ->when($this->selectedFloor, fn ($q) => $q->where('floor', $this->selectedFloor))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterRoomType, fn ($q) => $q->where('room_type_id', $this->filterRoomType))
            ->orderBy('floor')
            ->orderBy('room_number');

        $rooms     = $query->get();
        $floors    = Room::where('is_active', true)->distinct()->pluck('floor')->filter()->sort()->values();
        $roomTypes = RoomType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $selectedRoom = $this->selectedRoomId
            ? Room::with('roomType')->find($this->selectedRoomId)
            : null;

        $nights = $this->computeNights();

        $statuses = RoomStatus::cases();

        return view('hotel::livewire.room-status-board', compact('rooms', 'floors', 'statuses', 'roomTypes', 'selectedRoom', 'nights'));
    }
}
