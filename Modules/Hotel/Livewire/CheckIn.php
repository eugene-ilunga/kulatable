<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Guest;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Entities\StayGuest;
use Modules\Hotel\Entities\Room;
use Modules\Hotel\Entities\Folio;
use Modules\Hotel\Entities\FolioLine;
use Modules\Hotel\Entities\FolioPayment;
use Modules\Hotel\Enums\FolioLineType;
use Modules\Hotel\Enums\StayStatus;
use Modules\Hotel\Enums\RoomStatus;
use Modules\Hotel\Enums\FolioStatus;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Helper\Files;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckIn extends Component
{
    use LivewireAlert, WithFileUploads, WithPagination;

    public $showCheckInModal = false;
    public $selectedReservation;
    public $search = '';
    public $filterDate = '';
    public $roomAssignments = [];

    public $additionalGuests = [];

    protected function blankGuest(): array
    {
        return [
            'first_name' => '',
            'last_name'  => '',
            'email'      => '',
            'phone'      => '',
            'id_type'    => '',
            'id_number'  => '',
            'id_proof_file' => null,
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

    public function mount()
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function showCheckInForm($reservationId)
    {
        $this->selectedReservation = Reservation::with(['primaryGuest', 'reservationRooms.roomType'])->findOrFail($reservationId);
        $this->roomAssignments = [];
        $this->additionalGuests = [];
        $this->showCheckInModal = true;
    }

    public function processCheckIn($reservationId)
    {
        $reservation = Reservation::with('reservationRooms')->findOrFail($reservationId);

        if ($reservation->status->value !== 'confirmed' && $reservation->status->value !== 'tentative') {
            $this->alert('error', __('hotel::modules.checkIn.onlyConfirmedTentativeCanCheckIn'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        // Validate additional guests (same rules as AddGuest form)
        $guestRules = [];
        foreach ($this->additionalGuests as $i => $g) {
            if (empty(trim($g['first_name'] ?? ''))) continue;
            $guestRules["additionalGuests.{$i}.first_name"] = 'required|string|max:255';
            $guestRules["additionalGuests.{$i}.email"]      = 'nullable|email|max:255';
            $guestRules["additionalGuests.{$i}.phone"]      = 'required|string|max:255';
            $guestRules["additionalGuests.{$i}.id_type"]    = 'required|string|max:255';
            $guestRules["additionalGuests.{$i}.id_number"]  = 'required|string|max:255';
            $guestRules["additionalGuests.{$i}.id_proof_file"] = 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120';
        }
        if (!empty($guestRules)) {
            $this->validate($guestRules);
        }

        try {
            DB::beginTransaction();

            $roomAssignments = is_array($this->roomAssignments) ? $this->roomAssignments : [];
            
            // Process room assignments
            foreach ($roomAssignments as $reservationRoomId => $roomData) {
                // Handle array format [reservationRoomId => [0 => roomId, 1 => roomId]]
                if (is_array($roomData)) {
                    foreach ($roomData as $index => $roomId) {
                        if (!$roomId) continue;
                        $this->assignRoomToReservation($reservation, $reservationRoomId, $roomId);
                    }
                } else {
                    // Simple format: [reservationRoomId => roomId] - single room assignment
                    if (!$roomData) continue;
                    $this->assignRoomToReservation($reservation, $reservationRoomId, $roomData);
                }
            }

            // If no rooms assigned, throw error
            if (empty($roomAssignments) || !collect($roomAssignments)->flatten()->filter()->count()) {
                throw new \Exception(__('hotel::modules.checkIn.assignAtLeastOneRoom'));
            }

            // Update reservation status
            $reservation->update(['status' => \Modules\Hotel\Enums\ReservationStatus::CHECKED_IN]);

            DB::commit();

            $this->showCheckInModal = false;
            $this->selectedReservation = null;
            $this->roomAssignments = [];
            $this->additionalGuests = [];

            $this->alert('success', __('hotel::modules.checkIn.guestCheckedInSuccessfully'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            $this->dispatch('reservationCheckedIn');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->alert('error', $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    protected function assignRoomToReservation($reservation, $reservationRoomId, $roomId)
    {
        $reservationRoom = $reservation->reservationRooms->find($reservationRoomId);
        if (!$reservationRoom) return;

        $room = Room::findOrFail($roomId);
        
        // Check if room is available (only VACANT_CLEAN allowed)
        if ($room->status !== RoomStatus::VACANT_CLEAN) {
            throw new \Exception(__('hotel::modules.checkIn.roomNotAvailable', ['number' => $room->room_number]));
        }

        // Create stay
        $stay = Stay::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'reservation_id' => $reservation->id,
            'room_id' => $room->id,
            'stay_number' => Stay::generateStayNumber(branch()?->id),
            'check_in_at' => now(),
            'expected_checkout_at' => Carbon::parse($reservation->check_out_date)->setTimeFromTimeString($reservation->check_out_time ?? '11:00'),
            'status' => StayStatus::CHECKED_IN,
            'adults' => $reservation->adults,
            'children' => $reservation->children,
            'checked_in_by' => Auth::id(),
        ]);

        // Add primary guest to stay
        StayGuest::create([
            'stay_id'    => $stay->id,
            'guest_id'   => $reservation->primary_guest_id,
            'is_primary' => true,
        ]);

        // Create and link additional guests
        foreach ($this->additionalGuests as $guestData) {
            $firstName = trim($guestData['first_name'] ?? '');
            if (!$firstName) continue;

            $idProofPath = null;
            if (!empty($guestData['id_proof_file']) && is_object($guestData['id_proof_file'])) {
                $idProofPath = Files::uploadLocalOrS3($guestData['id_proof_file'], 'guest-id-proof');
            }

            $guest = Guest::create([
                'restaurant_id' => restaurant()->id,
                'branch_id'     => branch()?->id,
                'first_name'    => $firstName,
                'last_name'     => trim($guestData['last_name'] ?? '') ?: null,
                'email'         => trim($guestData['email'] ?? '') ?: null,
                'phone'         => trim($guestData['phone'] ?? '') ?: null,
                'id_type'       => $guestData['id_type'] ?: null,
                'id_number'     => trim($guestData['id_number'] ?? '') ?: null,
                'id_proof_file' => $idProofPath,
            ]);

            StayGuest::create([
                'stay_id'    => $stay->id,
                'guest_id'   => $guest->id,
                'is_primary' => false,
            ]);
        }

        // Create folio
        $folio = Folio::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'stay_id' => $stay->id,
            'folio_number' => Folio::generateFolioNumber(branch()?->id),
            'status' => FolioStatus::OPEN,
            'opened_at' => now(),
        ]);

        // Post initial room charge (from reservation room total; split by quantity when multiple rooms)
        $nights = $reservation->check_in_date->diffInDays($reservation->check_out_date) ?: 1;
        $roomChargePerStay = $reservationRoom->total_amount / max(1, (int) $reservationRoom->quantity);

        FolioLine::create([
            'folio_id' => $folio->id,
            'type' => FolioLineType::ROOM_CHARGE,
            'description' => 'Room Charge (Room ' . $room->room_number . ' for ' . $nights . ' night(s))',
            'amount' => $roomChargePerStay,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'net_amount' => $roomChargePerStay,
            'posting_date' => now(),
            'posted_by' => Auth::id(),
        ]);

        // Carry reservation-level extras/tax/discount into folio so checkout reflects reservation summary.
        $this->postReservationAdjustmentsToFolio($reservation, $folio, (float) $roomChargePerStay);

        $folio->recalculateTotals();

        // Apply advance paid on reservation as an initial folio payment (once per reservation)
        $advancePaid = (float) ($reservation->advance_paid ?? 0);
        if ($advancePaid > 0) {
            $advanceAlreadyApplied = FolioPayment::where('payment_method', 'advance')
                ->whereHas('folio.stay', function ($q) use ($reservation) {
                    $q->where('reservation_id', $reservation->id);
                })
                ->exists();

            if (! $advanceAlreadyApplied) {
                FolioPayment::create([
                    'folio_id' => $folio->id,
                    'payment_method' => 'advance',
                    'amount' => $advancePaid,
                    'transaction_reference' => $reservation->advance_payment_method ?: null,
                    'received_by' => Auth::id(),
                ]);

                $folio->recalculateTotals();
            }
        }

        // Update room status
        $room->update(['status' => RoomStatus::OCCUPIED]);

        // Update reservation room with assigned room
        $reservationRoom->update(['room_id' => $room->id]);
    }

    protected function postReservationAdjustmentsToFolio($reservation, $folio, float $roomChargePerStay): void
    {
        $totalRoomCharges = (float) $reservation->reservationRooms->sum('total_amount');
        if ($totalRoomCharges <= 0) {
            return;
        }

        $ratio = max(0, min(1, $roomChargePerStay / $totalRoomCharges));

        $extrasAllocated = round(((float) ($reservation->extras_amount ?? 0)) * $ratio, 2);
        $taxAllocated = round(((float) ($reservation->tax_amount ?? 0)) * $ratio, 2);

        $preDiscountSubtotal = $totalRoomCharges + (float) ($reservation->extras_amount ?? 0);
        $subtotalAfterDiscount = (float) ($reservation->subtotal_before_tax ?? $preDiscountSubtotal);
        $discountTotal = max(0, round($preDiscountSubtotal - $subtotalAfterDiscount, 2));
        $discountAllocated = round($discountTotal * $ratio, 2);

        if ($extrasAllocated > 0) {
            FolioLine::create([
                'folio_id' => $folio->id,
                'type' => FolioLineType::OTHER,
                'description' => 'Reservation Extras Allocation',
                'amount' => $extrasAllocated,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'net_amount' => $extrasAllocated,
                'posting_date' => now(),
                'posted_by' => Auth::id(),
            ]);
        }

        if ($taxAllocated > 0) {
            FolioLine::create([
                'folio_id' => $folio->id,
                'type' => FolioLineType::TAX,
                'description' => 'Reservation Tax Allocation',
                'amount' => $taxAllocated,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'net_amount' => $taxAllocated,
                'posting_date' => now(),
                'posted_by' => Auth::id(),
            ]);
        }

        if ($discountAllocated > 0) {
            FolioLine::create([
                'folio_id' => $folio->id,
                'type' => FolioLineType::DISCOUNT,
                'description' => 'Reservation Discount Allocation',
                'amount' => 0,
                'tax_amount' => 0,
                'discount_amount' => $discountAllocated,
                'net_amount' => -$discountAllocated,
                'posting_date' => now(),
                'posted_by' => Auth::id(),
            ]);
        }
    }

    public function render()
    {
        $query = Reservation::with(['primaryGuest', 'reservationRooms.roomType'])
            ->whereIn('status', ['tentative', 'confirmed'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('reservation_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('primaryGuest', function ($q) {
                            $q->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterDate, function ($q) {
                $q->where('check_in_date', $this->filterDate);
            })
            ->orderBy('check_in_date')
            ->orderBy('check_in_time');

        return view('hotel::livewire.check-in', [
            'reservations' => $query->paginate(20),
            'availableRooms' => Room::where('status', RoomStatus::VACANT_CLEAN)
                ->with('roomType')
                ->get()
                ->groupBy('room_type_id'),
        ]);
    }
}
