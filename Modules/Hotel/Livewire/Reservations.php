<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Guest;
use Modules\Hotel\Entities\RoomType;
use Modules\Hotel\Entities\RatePlan;
use Modules\Hotel\Enums\ReservationStatus;
use Modules\Hotel\Helpers\HotelHelper;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Reservations extends Component
{
    use LivewireAlert, WithPagination;

    public $showEditReservationModal = false;
    public $confirmCancelReservationModal = false;
    public $activeReservation;
    public $search = '';
    public $filterStatus = '';
    public $filterDate = '';

    public function mount()
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function showEditReservation($id)
    {
        $this->activeReservation = Reservation::with([
            'primaryGuest',
            'ratePlan',
            'reservationRooms.roomType',
            'stays.folio.folioPayments',
        ])->findOrFail($id);
        $this->showEditReservationModal = true;
    }

    public function closeViewReservationModal()
    {
        $this->showEditReservationModal = false;
        $this->activeReservation = null;
    }

    protected function getListeners()
    {
        return [
            'reservationUpdated' => 'handleReservationUpdated',
        ];
    }

    public function clearFilters()
    {
        $this->filterStatus = '';
    }

    public function handleReservationUpdated()
    {
        $this->closeViewReservationModal();
    }

    public function showCancelReservation($id)
    {
        $this->confirmCancelReservationModal = true;
        $this->activeReservation = Reservation::findOrFail($id);
    }

    public function cancelReservation($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update([
            'status' => ReservationStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
        ]);

        $this->confirmCancelReservationModal = false;
        $this->alert('success', __('hotel::modules.reservation.reservationCancelled'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        $this->activeReservation = null;
    }

    public function downloadReservationReceipt($reservationId)
    {
        $reservation = Reservation::with([
            'primaryGuest',
            'tax',
            'reservationRooms.roomType',
            'reservationExtras.extraService',
            'stays.folio.folioPayments',
        ])->findOrFail($reservationId);

        $roomsTotal = (float) $reservation->reservationRooms->sum('total_amount');
        $extrasTotal = (float) $reservation->reservationExtras->sum('total_amount');
        $grossSubtotal = $roomsTotal + $extrasTotal;

        $netAfterDiscount = (float) ($reservation->subtotal_before_tax ?? 0);
        $discountAmount = max(0, $grossSubtotal - $netAfterDiscount);

        $advancePaid = (float) ($reservation->advance_paid ?? 0);
        $folioPayments = $reservation->stays?->flatMap(fn($s) => $s->folio ? $s->folio->folioPayments : collect()) ?? collect();
        $advanceAlreadyApplied = $folioPayments->contains(fn($p) => ($p->payment_method ?? null) === 'advance');
        $totalPaid = (float) ($advanceAlreadyApplied ? $folioPayments->sum('amount') : ($advancePaid + $folioPayments->sum('amount')));
        $balanceDue = max(0, (float) ($reservation->total_amount ?? 0) - $totalPaid);

        // Render a dedicated DomPDF view for the reservation receipt.
        $pdf = Pdf::loadView('hotel::receipts.reservation-receipt', [
            'reservation' => $reservation,
            'roomsTotal' => $roomsTotal,
            'extrasTotal' => $extrasTotal,
            'grossSubtotal' => $grossSubtotal,
            'discountAmount' => $discountAmount,
            'netAfterDiscount' => $netAfterDiscount,
            'totalPaid' => $totalPaid,
            'advancePaid' => $advancePaid,
            'balanceDue' => $balanceDue,
        ]);

        $fileName = 'reservation-receipt-' . $reservation->reservation_number . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }

    public function exportReservations(): StreamedResponse
    {
        $query = Reservation::with([
            'primaryGuest',
            'ratePlan',
            'tax',
            'reservationRooms.roomType',
            'reservationExtras.extraService',
            'stays.folio.folioPayments',
        ])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('reservation_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('primaryGuest', function ($q) {
                            $q->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%')
                                ->orWhere('phone', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterDate, function ($q) {
                $q->whereDate('check_in_date', $this->filterDate);
            })
            ->orderBy('check_in_date', 'desc');

        $fileName = 'hotel-reservations-' . now()->format('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Reservation #',
                'Status',
                'Guest Name',
                'Guest Phone',
                'Guest Email',
                'Check-in Date',
                'Check-in Time',
                'Check-out Date',
                'Check-out Time',
                'Rate Plan',
                'Rooms Count',
                'Rooms Details',
                'Extras Details',
                'Rooms Total',
                'Extras Total',
                'Discount Amount',
                'Subtotal (after discount, before tax)',
                'Tax Name',
                'Tax Rate (%)',
                'Tax Amount',
                'Total Amount',
                'Advance Paid',
                'Total Paid',
                'Balance Due',
                'Payments Details',
                'Special Requests',
                'Created At',
            ]);

            $query->chunk(200, function ($reservations) use ($out) {
                foreach ($reservations as $reservation) {
                    $roomsTotal = (float) ($reservation->reservationRooms->sum('total_amount') ?? 0);
                    $extrasTotal = (float) ($reservation->reservationExtras->sum('total_amount') ?? 0);
                    $grossSubtotal = $roomsTotal + $extrasTotal;
                    $netAfterDiscount = (float) ($reservation->subtotal_before_tax ?? 0);
                    $discountAmount = max(0, $grossSubtotal - $netAfterDiscount);

                    $advancePaid = (float) ($reservation->advance_paid ?? 0);
                    $folioPayments = $reservation->stays?->flatMap(fn($s) => $s->folio ? $s->folio->folioPayments : collect()) ?? collect();
                    $advanceAlreadyApplied = $folioPayments->contains(fn($p) => ($p->payment_method ?? null) === 'advance');
                    $totalPaid = (float) ($advanceAlreadyApplied ? $folioPayments->sum('amount') : ($advancePaid + $folioPayments->sum('amount')));
                    $balanceDue = max(0, (float) ($reservation->total_amount ?? 0) - $totalPaid);

                    $roomsDetails = $reservation->reservationRooms
                        ->map(function ($rr) {
                            $name = $rr->roomType?->name ?? '-';
                            $qty = (int) ($rr->quantity ?? 0);
                            $rate = (float) ($rr->rate ?? 0);
                            $total = (float) ($rr->total_amount ?? 0);
                            return "{$name} (Qty: {$qty}, Rate: {$rate}, Total: {$total})";
                        })
                        ->implode(' | ');

                    $extrasDetails = $reservation->reservationExtras
                        ->map(function ($re) {
                            $name = $re->extraService?->name ?? '-';
                            $qty = (int) ($re->quantity ?? 0);
                            $unit = (float) ($re->unit_price ?? 0);
                            $total = (float) ($re->total_amount ?? 0);
                            return "{$name} (Qty: {$qty}, Unit: {$unit}, Total: {$total})";
                        })
                        ->implode(' | ');

                    $paymentsDetails = $folioPayments
                        ->map(function ($p) {
                            $dt = optional($p->created_at)->format('Y-m-d H:i');
                            $method = (string) ($p->payment_method ?? '');
                            $ref = (string) ($p->transaction_reference ?? '');
                            $amount = (float) ($p->amount ?? 0);
                            return "{$dt} {$method} {$ref} {$amount}";
                        })
                        ->implode(' | ');

                    fputcsv($out, [
                        $reservation->reservation_number,
                        $reservation->status?->label() ?? (string) $reservation->status,
                        $reservation->primaryGuest?->full_name ?? '',
                        $reservation->primaryGuest?->phone ?? '',
                        $reservation->primaryGuest?->email ?? '',
                        optional($reservation->check_in_date)->format('Y-m-d'),
                        $reservation->check_in_time ? Carbon::parse($reservation->check_in_time)->format('H:i') : '',
                        optional($reservation->check_out_date)->format('Y-m-d'),
                        $reservation->check_out_time ? Carbon::parse($reservation->check_out_time)->format('H:i') : '',
                        $reservation->ratePlan?->name ?? '',
                        (int) ($reservation->rooms_count ?? 0),
                        $roomsDetails,
                        $extrasDetails,
                        $roomsTotal,
                        $extrasTotal,
                        $discountAmount,
                        $netAfterDiscount,
                        $reservation->tax?->name ?? '',
                        $reservation->tax?->rate ?? '',
                        (float) ($reservation->tax_amount ?? 0),
                        (float) ($reservation->total_amount ?? 0),
                        $advancePaid,
                        $totalPaid,
                        $balanceDue,
                        $paymentsDetails,
                        (string) ($reservation->special_requests ?? ''),
                        optional($reservation->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $fileName);
    }

    public function render()
    {
        $query = Reservation::with(['primaryGuest', 'ratePlan', 'reservationRooms.roomType'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('reservation_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('primaryGuest', function ($q) {
                            $q->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%')
                                ->orWhere('phone', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterDate, function ($q) {
                $q->whereDate('check_in_date', $this->filterDate);
            })
            ->orderBy('check_in_date', 'desc');

        return view('hotel::livewire.reservations', [
            'reservations' => $query->paginate(20),
            'statuses' => ReservationStatus::cases(),
        ]);
    }
}
