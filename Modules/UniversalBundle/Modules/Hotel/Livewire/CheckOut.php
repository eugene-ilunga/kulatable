<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Folio;
use Modules\Hotel\Entities\FolioLine;
use Modules\Hotel\Entities\FolioPayment;
use Modules\Hotel\Entities\HousekeepingTask;
use Modules\Hotel\Enums\StayStatus;
use Modules\Hotel\Enums\FolioStatus;
use Modules\Hotel\Enums\RoomStatus;
use Modules\Hotel\Enums\FolioLineType;
use Modules\Hotel\Enums\ReservationStatus;
use Modules\Hotel\Enums\HousekeepingTaskType;
use Modules\Hotel\Enums\HousekeepingTaskStatus;
use App\Models\Order;
use Modules\Hotel\Helpers\HotelHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckOut extends Component
{
    use LivewireAlert, WithPagination;

    public $showCheckOutModal = false;
    public $selectedStay;
    public $search = '';
    public $filterDate = '';
    public $paymentMethod = 'cash';
    public $paymentAmount = 0;
    public $transactionReference = '';
    public $discountAmount = 0;
    public $paidRoomServiceTotal = 0;
    public $effectiveTotalPayments = 0;
    public $effectiveBalance = 0;

    public function mount()
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function updatedDiscountAmount(): void
    {
        $discount = max(0, (float)($this->discountAmount ?? 0));
        $this->paymentAmount = max(0, round($this->effectiveBalance - $discount, 2));
    }

    public function showCheckOutForm($stayId)
    {
        $this->selectedStay = Stay::with(['room.roomType', 'folio.folioLines', 'folio.folioPayments', 'stayGuests.guest', 'reservation.reservationRooms'])
            ->findOrFail($stayId);
        
        if ($this->selectedStay->folio) {
            $folio = $this->selectedStay->folio;
            $this->syncRoomServiceOrdersToFolio($this->selectedStay, $folio);
            $this->ensureReservationAdjustmentsOnFolio($this->selectedStay, $folio);
            $folio->recalculateTotals();

            // Compute paid room-service (F&B posting) total based on linked orders
            // IMPORTANT: force reload relations because orders may have just been posted to folio
            // and earlier eager-loaded collections won't include new lines.
            $folio->load(['folioLines', 'folioPayments']);
            $this->selectedStay->setRelation('folio', $folio);
            $roomServiceLines = $folio->folioLines->where('type', FolioLineType::FNB_POSTING);

            $orderIds = $roomServiceLines
                ->filter(function ($line) {
                    return $line->reference_type === Order::class
                        || $line->reference_type === 'App\\Models\\Order';
                })
                ->pluck('reference_id')
                ->filter()
                ->unique()
                ->toArray();

            $ordersById = !empty($orderIds)
                ? Order::whereIn('id', $orderIds)->get()->keyBy('id')
                : collect();

            $paidRoomServiceTotal = 0;
            foreach ($roomServiceLines as $line) {
                $order = $ordersById[$line->reference_id] ?? null;
                if ($order && $order->status === 'paid') {
                    $paidRoomServiceTotal += (float) $line->net_amount;
                }
            }

            $this->paidRoomServiceTotal = $paidRoomServiceTotal;
            $this->effectiveTotalPayments = (float) $folio->total_payments + $this->paidRoomServiceTotal;
            $this->effectiveBalance = (float) $folio->total_charges - $this->effectiveTotalPayments;

            // Default payment amount to effective outstanding balance
            $this->paymentAmount = max(0, $this->effectiveBalance);
        }
        
        $this->showCheckOutModal = true;
    }

    public function processCheckOut($stayId)
    {
        $stay = Stay::with(['folio.folioLines', 'reservation.reservationRooms'])->findOrFail($stayId);
        $folio = $stay->folio;

        if (!$folio || $folio->status !== FolioStatus::OPEN) {
            $this->alert('error', __('hotel::modules.checkOut.folioNotOpen'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            $this->ensureReservationAdjustmentsOnFolio($stay, $folio);

            // Apply discount if any
            if ($this->discountAmount > 0) {
                FolioLine::create([
                    'folio_id' => $folio->id,
                    'type' => FolioLineType::DISCOUNT,
                    'description' => __('hotel::modules.checkOut.checkOutDiscount'),
                    'amount' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => $this->discountAmount,
                    'net_amount' => -$this->discountAmount,
                    'posting_date' => now(),
                    'posted_by' => Auth::id(),
                ]);
            }

            // Recalculate folio
            $folio->recalculateTotals();

            // Process payment if any
            if ($this->paymentAmount > 0) {
                FolioPayment::create([
                    'folio_id' => $folio->id,
                    'payment_method' => $this->paymentMethod,
                    'amount' => $this->paymentAmount,
                    'transaction_reference' => $this->transactionReference,
                    'received_by' => Auth::id(),
                ]);

                $folio->recalculateTotals();

                // Update unpaid room service orders to paid status
                Order::where('context_type', 'HOTEL_ROOM')
                    ->where('context_id', $stay->id)
                    ->where('status', 'billed')
                    ->where('bill_to', 'POST_TO_ROOM')
                    ->update([
                        'status' => 'paid',
                        'updated_at' => now(),
                    ]);
            }

            // Close folio
            $folio->update([
                'status' => FolioStatus::CLOSED,
                'closed_at' => now(),
                'closed_by' => Auth::id(),
            ]);

            // Check out stay
            $stay->update([
                'status' => StayStatus::CHECKED_OUT,
                'actual_checkout_at' => now(),
                'checked_out_by' => Auth::id(),
            ]);

            // If all stays for this reservation are checked out, update reservation status as well
            if ($stay->reservation) {
                $reservation = $stay->reservation()->with('stays')->first();

                if ($reservation) {
                    $allStaysCheckedOut = $reservation->stays->every(function ($s) {
                        return $s->status === StayStatus::CHECKED_OUT;
                    });

                    if ($allStaysCheckedOut) {
                        $reservation->update([
                            'status' => ReservationStatus::CHECKED_OUT,
                        ]);
                    }
                }
            }

            // Update room status to VACANT_DIRTY
            $room = $stay->room;
            if ($room) {
                $room->update([
                    'status' => RoomStatus::VACANT_DIRTY,
                ]);

                // Automatically create a housekeeping task to clean this room
                // on the same day as checkout, if a similar pending task does not already exist.
                $today = now()->toDateString();

                $existingTask = HousekeepingTask::where('room_id', $room->id)
                    ->whereDate('task_date', $today)
                    ->where('type', HousekeepingTaskType::CLEAN)
                    ->whereIn('status', [
                        HousekeepingTaskStatus::PENDING,
                        HousekeepingTaskStatus::IN_PROGRESS,
                    ])
                    ->exists();

                if (! $existingTask) {
                    HousekeepingTask::create([
                        'restaurant_id' => restaurant()->id,
                        'branch_id'     => branch()?->id,
                        'room_id'       => $room->id,
                        'task_date'     => $today,
                        'type'          => HousekeepingTaskType::CLEAN,
                        'status'        => HousekeepingTaskStatus::PENDING,
                        'assigned_to'   => null,
                        'notes'         => __('hotel::modules.housekeeping.autoCreatedAfterCheckout'),
                    ]);
                }
            }

            DB::commit();

            $this->showCheckOutModal = false;
            $this->selectedStay = null;
            $this->reset(['paymentMethod', 'paymentAmount', 'transactionReference', 'discountAmount']);

            $this->alert('success', __('hotel::modules.checkOut.guestCheckedOutSuccessfully'), [
                'toast' => true,
                'position' => 'top-end',
            ]);

            $this->dispatch('stayCheckedOut');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->alert('error', $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function render()
    {
        $query = Stay::with(['room.roomType', 'folio', 'stayGuests.guest'])
            ->where('status', StayStatus::CHECKED_IN)
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('stay_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('stayGuests.guest', function ($q) {
                            $q->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('room', function ($q) {
                            $q->where('room_number', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterDate, function ($q) {
                $q->whereDate('expected_checkout_at', $this->filterDate);
            })
            ->orderBy('expected_checkout_at');

        return view('hotel::livewire.check-out', [
            'stays' => $query->paginate(20),
        ]);
    }

    protected function ensureReservationAdjustmentsOnFolio(Stay $stay, Folio $folio): void
    {
        if (!$stay->reservation) {
            return;
        }

        $alreadyAllocated = $folio->folioLines
            ->whereIn('description', [
                'Reservation Extras Allocation',
                'Reservation Tax Allocation',
                'Reservation Discount Allocation',
            ])
            ->isNotEmpty();

        if ($alreadyAllocated) {
            return;
        }

        $reservation = $stay->reservation;
        $totalRoomCharges = (float) $reservation->reservationRooms->sum('total_amount');
        if ($totalRoomCharges <= 0) {
            return;
        }

        $stayRoomCharge = (float) $folio->folioLines
            ->where('type', FolioLineType::ROOM_CHARGE)
            ->sum('net_amount');

        if ($stayRoomCharge <= 0) {
            return;
        }

        $ratio = max(0, min(1, $stayRoomCharge / $totalRoomCharges));

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

    /**
     * Ensure all POS "Post to Room" orders are posted to the folio before checkout.
     *
     * Why: some orders may be in `payment_due` state (not strictly "billed"/"paid"),
     * or POS posting may have failed earlier. Checkout should still show the bill.
     */
    protected function syncRoomServiceOrdersToFolio(Stay $stay, Folio $folio): void
    {
        if ($folio->status !== FolioStatus::OPEN) {
            return;
        }

        try {
            $folio->loadMissing('folioLines');

            $alreadyPostedOrderIds = $folio->folioLines
                ->where('type', FolioLineType::FNB_POSTING)
                ->whereIn('reference_type', [Order::class, 'App\\Models\\Order'])
                ->pluck('reference_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $orders = Order::query()
                ->where('context_type', 'HOTEL_ROOM')
                ->where('context_id', $stay->id)
                ->where('bill_to', 'POST_TO_ROOM')
                ->whereIn('status', ['billed', 'paid', 'payment_due'])
                ->get();

            foreach ($orders as $order) {
                if ($order->posted_to_folio_at) {
                    continue;
                }

                if (in_array($order->id, $alreadyPostedOrderIds, true)) {
                    $order->update(['posted_to_folio_at' => now()]);
                    continue;
                }

                HotelHelper::postOrderToFolio($order, $folio);
            }
        } catch (\Throwable $e) {
            // Never block checkout UI if posting fails.
        }
    }
}
