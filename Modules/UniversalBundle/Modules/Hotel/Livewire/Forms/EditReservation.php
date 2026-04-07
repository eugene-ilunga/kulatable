<?php

namespace Modules\Hotel\Livewire\Forms;

use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Guest;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Entities\RatePlan;
use Modules\Hotel\Entities\ReservationRoom;
use Modules\Hotel\Entities\ReservationGuest;
use Modules\Hotel\Entities\ReservationExtra;
use Modules\Hotel\Entities\Tax;
use Modules\Hotel\Entities\ExtraService;
use Modules\Hotel\Enums\ReservationStatus;
use Modules\Hotel\Helpers\HotelHelper;
use App\Helper\Files;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

class EditReservation extends Component
{
    use LivewireAlert, WithFileUploads;

    public $reservationId;
    public $guests = [];
    public $check_in_date;
    public $check_out_date;
    public $check_in_time;
    public $check_out_time;
    public $adults = 1;
    public $children = 0;
    public $rate_plan_id;
    public $status;
    public $special_requests;
    public $advance_paid = 0;
    public $selectedRooms = [];
    public $availableRooms = [];
    public $reason_for_trip;
    public $means_of_transport;
    public $place_of_origin;
    public $vehicle_registration_number;
    public $final_destination;
    public $apply_discount = false;
    public $discount_type = 'percentage';
    public $discount_value = 0;
    public $tax_id;
    public $advance_payment_method;
    public $selectedExtras = [];

    protected function allowedStatuses(): array
    {
        return [
            ReservationStatus::TENTATIVE,
            ReservationStatus::CONFIRMED,
        ];
    }

    public function mount($reservationId = null)
    {
        $this->reservationId = $reservationId;
        if (!$this->reservationId) {
            return;
        }

        $reservation = Reservation::with([
            'reservationRooms',
            'reservationGuests.guest',
            'reservationExtras.extraService',
            'tax',
        ])->findOrFail($this->reservationId);

        if (!in_array($reservation->status->value, ['tentative', 'confirmed'])) {
            $this->alert('error', __('hotel::modules.reservation.onlyTentativeConfirmedEditable'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
            return $this->redirect(route('hotel.reservations.index'), navigate: true);
        }

        $this->check_in_date = $reservation->check_in_date->format('Y-m-d');
        $this->check_out_date = $reservation->check_out_date->format('Y-m-d');
        $this->check_in_time = $reservation->check_in_time ? Carbon::parse($reservation->check_in_time)->format('H:i') : '14:00';
        $this->check_out_time = $reservation->check_out_time ? Carbon::parse($reservation->check_out_time)->format('H:i') : '11:00';
        $this->adults = $reservation->adults;
        $this->children = $reservation->children ?? 0;
        $this->rate_plan_id = $reservation->rate_plan_id;
        $this->status = $reservation->status->value;
        $this->special_requests = $reservation->special_requests;
        $this->advance_paid = $reservation->advance_paid ?? 0;
        $this->reason_for_trip = $reservation->reason_for_trip;
        $this->means_of_transport = $reservation->means_of_transport;
        $this->place_of_origin = $reservation->place_of_origin;
        $this->vehicle_registration_number = $reservation->vehicle_registration_number;
        $this->final_destination = $reservation->final_destination;
        $this->discount_type = $reservation->discount_type ?? 'percentage';
        $this->discount_value = $reservation->discount_value ?? 0;
        $this->apply_discount = (float) ($reservation->discount_value ?? 0) > 0;
        $this->tax_id = $reservation->tax_id;
        $this->advance_payment_method = $reservation->advance_payment_method;

        $primary = $reservation->primaryGuest;
        if ($primary) {
            $this->guests[] = [
                'id' => $primary->id,
                'first_name' => $primary->first_name,
                'last_name' => $primary->last_name,
                'email' => $primary->email,
                'phone' => $primary->phone,
                'id_type' => $primary->id_type,
                'id_number' => $primary->id_number,
                'id_proof_file' => null,
            ];
        } else {
            $this->addGuest();
        }

        foreach ($reservation->reservationRooms as $rr) {
            $this->selectedRooms[$rr->room_type_id] = [
                'quantity' => $rr->quantity,
                'rate' => $rr->rate,
            ];
        }

        foreach ($reservation->reservationExtras as $re) {
            $this->selectedExtras[$re->extra_service_id] = [
                'quantity' => $re->quantity,
                'unit_price' => $re->unit_price,
            ];
        }

        $this->checkAvailability();
    }

    public function addGuest()
    {
        $this->guests[] = [
            'id' => null,
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'id_type' => 'passport',
            'id_number' => '',
            'id_proof_file' => null,
        ];
    }

    public function removeGuest($index)
    {
        // Single guest mode for reservation form.
        return;
    }

    public function updatedCheckInDate()
    {
        $this->checkAvailability();
    }

    public function updatedCheckOutDate()
    {
        $this->checkAvailability();
    }

    public function checkAvailability()
    {
        if (!$this->check_in_date || !$this->check_out_date) {
            return;
        }

        $checkIn = Carbon::parse($this->check_in_date);
        $checkOut = Carbon::parse($this->check_out_date);

        if ($checkOut < $checkIn) {
            return;
        }

        $checkOutForAvailability = $checkOut->equalTo($checkIn) ? $checkOut->copy()->addDay() : $checkOut;
        $roomTypes = RoomType::where('is_active', true)->get();
        $this->availableRooms = [];

        foreach ($roomTypes as $roomType) {
            $availability = HotelHelper::getRoomAvailability($roomType->id, $checkIn, $checkOutForAvailability);
            $existing = $this->selectedRooms[$roomType->id] ?? [];
            $qty = isset($existing['quantity']) ? (int) $existing['quantity'] : 0;
            $rate = $existing['rate'] ?? $roomType->base_rate;
            if ($rate === '' || $rate === null) {
                $rate = $roomType->base_rate;
            } else {
                $rate = (float) $rate;
            }
            $this->selectedRooms[$roomType->id] = ['quantity' => $qty, 'rate' => $rate];
            $this->availableRooms[$roomType->id] = [
                'room_type_id' => $roomType->id,
                'room_type_name' => $roomType->name,
                'base_rate' => (float) $roomType->base_rate,
                'available' => max($availability, $qty),
                'quantity' => $qty,
                'rate' => $rate,
            ];
        }
    }

    public function getSubtotalProperty()
    {
        $nights = $this->check_in_date && $this->check_out_date
            ? max(1, Carbon::parse($this->check_in_date)->diffInDays(Carbon::parse($this->check_out_date)))
            : 1;

        $roomsTotal = 0;
        foreach ($this->selectedRooms ?? [] as $roomTypeId => $roomData) {
            $qty = (int) ($roomData['quantity'] ?? 0);
            if ($qty > 0) {
                $defaultRate = (float) ($this->availableRooms[$roomTypeId]['base_rate'] ?? 0);
                $rateInput = $roomData['rate'] ?? null;
                $rate = ($rateInput === '' || $rateInput === null)
                    ? $defaultRate
                    : (float) $rateInput;
                $roomsTotal += $rate * $nights * $qty;
            }
        }

        $extrasTotal = 0;
        foreach ($this->selectedExtras ?? [] as $extraId => $extraData) {
            $qty = (int) ($extraData['quantity'] ?? 0);
            if ($qty > 0) {
                $defaultPrice = (float) (ExtraService::find($extraId)?->price ?? 0);
                $priceInput = $extraData['unit_price'] ?? null;
                $price = ($priceInput === '' || $priceInput === null)
                    ? $defaultPrice
                    : (float) $priceInput;
                $extrasTotal += $price * $qty;
            }
        }

        return $roomsTotal + $extrasTotal;
    }

    public function getDiscountAmountProperty()
    {
        if (!$this->apply_discount) {
            return 0;
        }

        $subtotal = $this->subtotal;
        if (!$this->discount_value || $this->discount_value <= 0) {
            return 0;
        }
        if ($this->discount_type === 'percentage') {
            return round($subtotal * ($this->discount_value / 100), 2);
        }
        return min($this->discount_value, $subtotal);
    }

    public function getTaxAmountProperty()
    {
        $afterDiscount = $this->subtotal - $this->discountAmount;
        if (!$this->tax_id) {
            return 0;
        }
        $tax = Tax::find($this->tax_id);
        if (!$tax) {
            return 0;
        }
        return round($afterDiscount * ((float) $tax->rate / 100), 2);
    }

    public function getTotalAmountProperty()
    {
        return round($this->subtotal - $this->discountAmount + $this->taxAmount, 2);
    }

    public function getAmountAfterDiscountProperty()
    {
        return max(0, round($this->subtotal - $this->discountAmount, 2));
    }

    public function getRemainingBalanceProperty()
    {
        $advance = (float) ($this->advance_paid ?? 0);

        return max(0, round($this->totalAmount - $advance, 2));
    }

    public function getRoomsTotalProperty()
    {
        $nights = $this->check_in_date && $this->check_out_date
            ? max(1, Carbon::parse($this->check_in_date)->diffInDays(Carbon::parse($this->check_out_date)))
            : 1;
        $total = 0;
        foreach ($this->selectedRooms ?? [] as $roomTypeId => $roomData) {
            $qty = (int) ($roomData['quantity'] ?? 0);
            if ($qty > 0) {
                $defaultRate = $this->availableRooms[$roomTypeId]['base_rate'] ?? 0;
                $rateInput = $roomData['rate'] ?? null;
                $rate = ($rateInput === '' || $rateInput === null)
                    ? (float) $defaultRate
                    : (float) $rateInput;
                $total += $rate * $nights * $qty;
            }
        }
        return $total;
    }

    public function getExtrasTotalProperty()
    {
        $total = 0;
        foreach ($this->selectedExtras ?? [] as $extraId => $extraData) {
            $qty = (int) ($extraData['quantity'] ?? 0);
            if ($qty > 0) {
                $defaultPrice = (float) (ExtraService::find($extraId)?->price ?? 0);
                $priceInput = $extraData['unit_price'] ?? null;
                $price = ($priceInput === '' || $priceInput === null)
                    ? $defaultPrice
                    : (float) $priceInput;
                $total += $price * $qty;
            }
        }
        return $total;
    }

    public function submitForm()
    {
        // Reservation form currently supports exactly one guest.
        $this->guests = [array_values($this->guests)[0] ?? [
            'id' => null,
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'phone' => '',
            'id_type' => 'passport',
            'id_number' => '',
            'id_proof_file' => null,
        ]];

        $this->validate([
            'guests' => 'required|array|min:1',
            'guests.*.first_name' => 'required|string|max:255',
            'guests.*.last_name' => 'nullable|string|max:255',
            'guests.*.email' => 'nullable|email|max:255',
            'guests.*.phone' => 'required|max:255',
            'guests.*.id_type' => 'required|string|max:255',
            'guests.*.id_number' => 'required|max:255',
            'guests.*.id_proof_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,pdf|max:5120',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after_or_equal:check_in_date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'adults' => 'required|integer|min:1',
            'children' => 'integer|min:0',
            'rate_plan_id' => 'nullable|exists:hotel_rate_plans,id',
            'status' => 'required|in:' . implode(',', array_column($this->allowedStatuses(), 'value')),
            'advance_paid' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_id' => 'nullable|exists:hotel_taxes,id',
        ]);

        if ((float) ($this->advance_paid ?? 0) > 0 && blank($this->advance_payment_method)) {
            $this->addError('advance_payment_method', __('validation.required'));
            return;
        }

        $checkIn = Carbon::parse($this->check_in_date);
        $checkOut = Carbon::parse($this->check_out_date);

        if ($checkOut->lt($checkIn)) {
            $this->addError('check_out_date', __('hotel::modules.reservation.checkOutNotBeforeCheckIn'));
            return;
        }

        if ($checkOut->equalTo($checkIn)) {
            if (!$this->check_in_time || !$this->check_out_time) {
                $this->addError('check_out_time', __('hotel::modules.reservation.checkOutTimeAfterCheckInTime'));
                return;
            }
            $checkInAt = Carbon::createFromFormat('Y-m-d H:i', $this->check_in_date . ' ' . $this->check_in_time);
            $checkOutAt = Carbon::createFromFormat('Y-m-d H:i', $this->check_out_date . ' ' . $this->check_out_time);
            if ($checkOutAt->lte($checkInAt)) {
                $this->addError('check_out_time', __('hotel::modules.reservation.checkOutTimeAfterCheckInTime'));
                return;
            }
        }

        $totalQuantity = collect($this->selectedRooms)->sum(fn ($room) => (int) ($room['quantity'] ?? 0));
        if (empty($this->selectedRooms) || !$totalQuantity) {
            $this->alert('error', __('hotel::modules.reservation.selectAtLeastOneRoom'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
            return;
        }

        $reservation = Reservation::findOrFail($this->reservationId);

        if (!in_array($reservation->status->value, ['tentative', 'confirmed'])) {
            $this->alert('error', __('hotel::modules.reservation.reservationCannotBeEdited'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
            return;
        }

        $nights = max(1, $checkIn->diffInDays($checkOut));

        // Sync a single primary guest.
        $guestData = $this->guests[0];
        $idProofPath = null;
        if (!empty($guestData['id_proof_file']) && is_object($guestData['id_proof_file'])) {
            $idProofPath = Files::uploadLocalOrS3($guestData['id_proof_file'], 'guest-id-proof');
        }

        $guest = null;
        if (!empty($guestData['id'])) {
            $guest = Guest::find($guestData['id']);
        }

        if ($guest) {
            $guest->update([
                'first_name' => $guestData['first_name'],
                'last_name' => $guestData['last_name'] ?: null,
                'email' => $guestData['email'] ?: null,
                'phone' => $guestData['phone'],
                'id_type' => $guestData['id_type'],
                'id_number' => $guestData['id_number'],
                'id_proof_file' => $idProofPath ?? $guest->id_proof_file,
            ]);
        } else {
            $guest = Guest::create([
                'restaurant_id' => restaurant()->id,
                'branch_id' => branch()?->id,
                'first_name' => $guestData['first_name'],
                'last_name' => $guestData['last_name'] ?: null,
                'email' => $guestData['email'] ?: null,
                'phone' => $guestData['phone'],
                'id_type' => $guestData['id_type'],
                'id_number' => $guestData['id_number'],
                'id_proof_file' => $idProofPath,
            ]);
        }

        $primaryGuestId = $guest->id;
        $subtotal = $this->subtotal;
        $discountAmount = $this->discountAmount;
        $taxAmount = $this->taxAmount;
        $totalAmount = $this->totalAmount;

        $reservation->update([
            'primary_guest_id' => $primaryGuestId,
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'adults' => $this->adults,
            'children' => $this->children,
            'rate_plan_id' => $this->rate_plan_id,
            'status' => $this->status,
            'special_requests' => $this->special_requests,
            'advance_paid' => $this->advance_paid,
            'reason_for_trip' => $this->reason_for_trip,
            'means_of_transport' => $this->means_of_transport,
            'place_of_origin' => $this->place_of_origin,
            'vehicle_registration_number' => $this->vehicle_registration_number,
            'final_destination' => $this->final_destination,
            'discount_type' => $this->apply_discount ? $this->discount_type : null,
            'discount_value' => $this->apply_discount ? $this->discount_value : 0,
            'tax_id' => $this->tax_id,
            'advance_payment_method' => (float) ($this->advance_paid ?? 0) > 0 ? $this->advance_payment_method : null,
            'subtotal_before_tax' => $subtotal - $discountAmount,
            'tax_amount' => $taxAmount,
            'extras_amount' => $this->extrasTotal,
            'rooms_count' => collect($this->selectedRooms)->sum(fn ($room) => (int) ($room['quantity'] ?? 0)),
            'total_amount' => $totalAmount,
        ]);

        $reservation->reservationGuests()->delete();
        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $reservation->reservationRooms()->delete();
        foreach ($this->selectedRooms as $roomTypeId => $roomData) {
            if (($roomData['quantity'] ?? 0) > 0) {
                $roomType = RoomType::find($roomTypeId);
                $rateInput = $roomData['rate'] ?? null;
                $rate = ($rateInput === '' || $rateInput === null)
                    ? (float) $roomType->base_rate
                    : (float) $rateInput;
                $amount = $rate * $nights * $roomData['quantity'];
                ReservationRoom::create([
                    'reservation_id' => $reservation->id,
                    'room_type_id' => $roomTypeId,
                    'quantity' => $roomData['quantity'],
                    'rate' => $rate,
                    'total_amount' => $amount,
                ]);
            }
        }

        $reservation->reservationExtras()->delete();
        foreach ($this->selectedExtras ?? [] as $extraId => $extraData) {
            if (($extraData['quantity'] ?? 0) > 0) {
                $defaultPrice = (float) (ExtraService::find($extraId)?->price ?? 0);
                $unitPriceInput = $extraData['unit_price'] ?? null;
                $unitPrice = ($unitPriceInput === '' || $unitPriceInput === null)
                    ? $defaultPrice
                    : (float) $unitPriceInput;
                $total = $unitPrice * $extraData['quantity'];
                ReservationExtra::create([
                    'reservation_id' => $reservation->id,
                    'extra_service_id' => $extraId,
                    'quantity' => $extraData['quantity'],
                    'unit_price' => $unitPrice,
                    'total_amount' => $total,
                ]);
            }
        }

        $this->alert('success', __('hotel::modules.reservation.reservationUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);

        return $this->redirect(route('hotel.reservations.index'), navigate: true);
    }

    public function render()
    {
        return view('hotel::livewire.forms.edit-reservation', [
            'ratePlans' => RatePlan::where('is_active', true)->get(),
            'statuses' => $this->allowedStatuses(),
            'taxes' => Tax::where('is_active', true)->get(),
            'extraServices' => ExtraService::where('is_active', true)->get(),
        ]);
    }
}