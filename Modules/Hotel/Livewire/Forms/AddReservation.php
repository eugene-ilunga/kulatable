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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Hotel\Notifications\HotelReservationCreated;

class AddReservation extends Component
{
    use LivewireAlert, WithFileUploads;

    public $guests = [];
    public $check_in_date;
    public $check_out_date;
    public $check_in_time;
    public $check_out_time;
    public $adults = 1;
    public $children = 0;
    public $rate_plan_id;
    public $status = ReservationStatus::TENTATIVE->value;
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
    public $roomsTotal = 0;
    public $extrasTotal = 0;
    public $subTotal = 0;
    public $discountAmount = 0;
    public $taxAmount = 0;
    public $totalAmount = 0;
    public $amountAfterDiscount = 0;
    public $remainingBalance = 0;

    protected function allowedStatuses(): array
    {
        return [
            ReservationStatus::TENTATIVE,
            ReservationStatus::CONFIRMED,
        ];
    }

    public function mount()
    {
        $this->check_in_date = now()->format('Y-m-d');
        $this->check_out_date = now()->addDay()->format('Y-m-d');
        $this->check_in_time = '14:00';
        $this->check_out_time = '11:00';
        $this->addGuest();
        $this->checkAvailability();
        $this->syncSelectedExtrasFromCatalog();
        $this->refreshBookingTotals();
    }

    /**
     * Ensure extra rows exist with stable wire:model keys (qty + unit price).
     */
    protected function syncSelectedExtrasFromCatalog(): void
    {
        foreach (ExtraService::where('is_active', true)->get() as $extra) {
            $existing = $this->selectedExtras[$extra->id] ?? [];
            $qty = isset($existing['quantity']) ? (int) $existing['quantity'] : 0;
            $unitPrice = $existing['unit_price'] ?? $extra->price;
            if ($unitPrice === '' || $unitPrice === null) {
                $unitPrice = (float) $extra->price;
            } else {
                $unitPrice = (float) $unitPrice;
            }
            $this->selectedExtras[$extra->id] = [
                'quantity' => max(0, $qty),
                'unit_price' => max(0, $unitPrice),
            ];
        }
    }

    public function reservationNights(): int
    {
        if (!$this->check_in_date || !$this->check_out_date) {
            return 1;
        }

        return max(1, Carbon::parse($this->check_in_date)->diffInDays(Carbon::parse($this->check_out_date)));
    }

    public function updatedSelectedRooms(): void
    {
        foreach ($this->availableRooms as $roomTypeId => $meta) {
            if (!isset($this->selectedRooms[$roomTypeId])) {
                continue;
            }
            $qty = max(0, (int) ($this->selectedRooms[$roomTypeId]['quantity'] ?? 0));
            $max = (int) ($meta['available'] ?? 0);
            if ($qty > $max) {
                $qty = $max;
            }
            $this->selectedRooms[$roomTypeId]['quantity'] = $qty;
        }

        $this->refreshBookingTotals();
    }

    public function updatedSelectedExtras(): void
    {
        foreach (array_keys($this->selectedExtras) as $extraId) {
            $row = $this->selectedExtras[$extraId];
            $this->selectedExtras[$extraId]['quantity'] = max(0, (int) ($row['quantity'] ?? 0));
            $price = $row['unit_price'] ?? null;
            if ($price !== '' && $price !== null && (float) $price < 0) {
                $this->selectedExtras[$extraId]['unit_price'] = 0;
            }
        }

        $this->refreshBookingTotals();
    }

    /**
     * Sync public summary fields used in the Blade sidebar (must run after rooms/extras/discount/tax inputs change).
     */
    protected function refreshBookingTotals(): void
    {
        $this->subTotal = round((float) $this->Subtotal(), 2);
        $this->roomsTotal = round((float) $this->RoomsTotal(), 2);
        $this->extrasTotal = round((float) $this->ExtrasTotal(), 2);
        $this->discountAmount = round((float) $this->DiscountAmount(), 2);
        $this->taxAmount = round((float) $this->TaxAmount(), 2);
        $this->totalAmount = round((float) $this->TotalAmount(), 2);
        $this->amountAfterDiscount = round((float) $this->AmountAfterDiscount(), 2);
        $this->remainingBalance = round((float) $this->RemainingBalance(), 2);
    }

    public function updatedApplyDiscount(): void
    {
        $this->refreshBookingTotals();
    }

    public function updatedDiscountType(): void
    {
        $this->refreshBookingTotals();
    }

    public function updatedDiscountValue(): void
    {
        $this->refreshBookingTotals();
    }

    public function updatedTaxId(): void
    {
        $this->refreshBookingTotals();
    }

    public function updatedAdvancePaid(): void
    {
        $this->refreshBookingTotals();
    }

    public function roomLineTotal(int|string $roomTypeId): float
    {
        $roomTypeId = (int) $roomTypeId;
        $roomData = $this->selectedRooms[$roomTypeId] ?? null;
        if (!$roomData) {
            return 0.0;
        }
        $qty = (int) ($roomData['quantity'] ?? 0);
        if ($qty <= 0) {
            return 0.0;
        }
        $defaultRate = (float) ($this->availableRooms[$roomTypeId]['base_rate'] ?? 0);
        $rateInput = $roomData['rate'] ?? null;
        $rate = ($rateInput === '' || $rateInput === null)
            ? $defaultRate
            : (float) $rateInput;
        $nights = $this->reservationNights();

        return round($rate * $nights * $qty, 2);
    }

    public function extraLineTotal(int|string $extraId, float $defaultUnitPrice): float
    {
        $extraId = (int) $extraId;
        $extraData = $this->selectedExtras[$extraId] ?? null;
        if (!$extraData) {
            return 0.0;
        }
        $qty = (int) ($extraData['quantity'] ?? 0);
        if ($qty <= 0) {
            return 0.0;
        }
        $priceInput = $extraData['unit_price'] ?? null;
        $price = ($priceInput === '' || $priceInput === null)
            ? $defaultUnitPrice
            : (float) $priceInput;

        return round($price * $qty, 2);
    }

    public function addGuest()
    {
        $this->guests[] = [
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

    public function updatedCheckInDate(): void
    {
        $this->checkAvailability();
    }

    public function updatedCheckOutDate(): void
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

        $this->refreshBookingTotals();
    }

    public function Subtotal()
    {
        $nights = $this->reservationNights();
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

    public function DiscountAmount()
    {
        if (!$this->apply_discount) {
            return 0;
        }

        $subtotal = $this->subTotal;
        if (!$this->discount_value || $this->discount_value <= 0) {
            return 0;
        }
        if ($this->discount_type === 'percentage') {
            return round($subtotal * ($this->discount_value / 100), 2);
        }

        return round((float) min($this->discount_value, $subtotal), 2);
    }

    public function TaxAmount()
    {
        $afterDiscount = $this->subTotal - $this->discountAmount;
        if (!$this->tax_id) {
            return 0;
        }
        $tax = Tax::find($this->tax_id);
        if (!$tax) {
            return 0;
        }
        return round($afterDiscount * ((float) $tax->rate / 100), 2);
    }

    public function TotalAmount()
    {
        return round($this->subTotal - $this->discountAmount + $this->taxAmount, 2);
    }

    /**
     * Net after discount, before tax (tax base).
     */
    public function AmountAfterDiscount()
    {
        return max(0, round($this->subTotal - $this->discountAmount, 2));
    }

    /**
     * Amount still to collect at checkout (total minus advance); never negative.
     */
    public function RemainingBalance()
    {
        $advance = (float) ($this->advance_paid ?? 0);

        return max(0, round($this->totalAmount - $advance, 2));
    }

    public function RoomsTotal()
    {
        $nights = $this->reservationNights();
        
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

    public function ExtrasTotal()
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
            'guests.*.phone' => 'required|string|max:255',
            'guests.*.id_type' => 'required|string|max:255',
            'guests.*.id_number' => 'required|string|max:255',
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

        $this->refreshBookingTotals();

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

        $nights = max(1, $checkIn->diffInDays($checkOut));

        // Create a single primary guest record.
        $guestData = $this->guests[0];
        $idProofPath = null;
        if (!empty($guestData['id_proof_file']) && is_object($guestData['id_proof_file'])) {
            $idProofPath = Files::uploadLocalOrS3($guestData['id_proof_file'], 'guest-id-proof');
        }

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

        $primaryGuestId = $guest->id;

        $subtotal = $this->subTotal;
        $discountAmount = $this->discountAmount;
        $taxAmount = $this->taxAmount;
        $totalAmount = $this->totalAmount;

        $reservation = Reservation::create([
            'restaurant_id' => restaurant()->id,
            'branch_id' => branch()?->id,
            'reservation_number' => Reservation::generateReservationNumber(branch()?->id),
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
            'created_by' => Auth::id(),
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

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

        if (!blank($guest->email)) {
            try {
                $reservation->load([
                    'restaurant',
                    'branch',
                    'primaryGuest',
                    'tax',
                    'reservationRooms.roomType',
                    'reservationExtras.extraService',
                ]);

                Notification::route('mail', $guest->email)
                    ->notify(new HotelReservationCreated($reservation));
            } catch (\Throwable $e) {
                Log::error('Error sending hotel reservation created email: ' . $e->getMessage(), [
                    'reservation_id' => $reservation->id,
                    'guest_id' => $guest->id,
                    'email' => $guest->email,
                ]);
            }
        }

        $this->alert('success', __('hotel::modules.reservation.reservationCreated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);

        return $this->redirect(route('hotel.reservations.index'), navigate: true);
    }

    public function render()
    {
        return view('hotel::livewire.forms.add-reservation', [
            'ratePlans' => RatePlan::where('is_active', true)->get(),
            'statuses' => $this->allowedStatuses(),
            'taxes' => Tax::where('is_active', true)->get(),
            'extraServices' => ExtraService::where('is_active', true)->get(),
        ]);
    }
}