<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationTool
{
    /**
     * Get reservations list
     */
    public function getReservations(array $args): array
    {
        try {
            $status = $args['status'] ?? null;
            $dateFrom = $args['date_from'] ?? now()->toDateString();
            $dateTo = $args['date_to'] ?? now()->addDays(7)->toDateString();
            $slotType = $args['slot_type'] ?? null; // Breakfast, Lunch, Dinner
            $limit = min($args['limit'] ?? 50, 100); // Cap at 100
            $branchId = branch()->id ?? null;

            if (!$branchId) {
                return [
                    'error' => 'No branch context available',
                ];
            }

            // Validate dates
            try {
                $from = Carbon::parse($dateFrom)->startOfDay();
                $to = Carbon::parse($dateTo)->endOfDay();
            } catch (\Exception $e) {
                return [
                    'error' => 'Invalid date format. Use YYYY-MM-DD',
                ];
            }

            $query = Reservation::where('branch_id', $branchId)
                ->whereBetween('reservation_date_time', [$from, $to]);

            if ($status) {
                $query->where('reservation_status', $status);
            }

            if ($slotType) {
                $query->where('reservation_slot_type', $slotType);
            }

            $reservations = $query->with(['table', 'customer'])
                ->orderBy('reservation_date_time')
                ->limit($limit)
                ->get();

            return $reservations->map(function ($reservation) {
                return [
                    'reservation_id' => (int) $reservation->id,
                    'reservation_date_time' => $reservation->reservation_date_time->toDateTimeString(),
                    'table' => $reservation->table ? $reservation->table->table_code : null,
                    'customer' => $reservation->customer ? $reservation->customer->name : null,
                    'party_size' => (int) $reservation->party_size,
                    'status' => $reservation->reservation_status,
                    'slot_type' => $reservation->reservation_slot_type,
                    'special_requests' => $reservation->special_requests,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get reservation statistics
     */
    public function getReservationStats(array $args): array
    {
        try {
            $dateFrom = $args['date_from'] ?? now()->toDateString();
            $dateTo = $args['date_to'] ?? now()->addDays(7)->toDateString();
            $branchId = branch()->id ?? null;

            if (!$branchId) {
                return [
                    'error' => 'No branch context available',
                ];
            }

            // Validate dates
            try {
                $from = Carbon::parse($dateFrom)->startOfDay();
                $to = Carbon::parse($dateTo)->endOfDay();
            } catch (\Exception $e) {
                return [
                    'error' => 'Invalid date format. Use YYYY-MM-DD',
                ];
            }

            $stats = Reservation::where('branch_id', $branchId)
                ->whereBetween('reservation_date_time', [$from, $to])
                ->select(
                    DB::raw('COUNT(*) as total_reservations'),
                    DB::raw('SUM(CASE WHEN reservation_status = "Confirmed" THEN 1 ELSE 0 END) as confirmed'),
                    DB::raw('SUM(CASE WHEN reservation_status = "Checked_In" THEN 1 ELSE 0 END) as checked_in'),
                    DB::raw('SUM(CASE WHEN reservation_status = "Cancelled" THEN 1 ELSE 0 END) as cancelled'),
                    DB::raw('SUM(CASE WHEN reservation_status = "No_Show" THEN 1 ELSE 0 END) as no_show'),
                    DB::raw('SUM(party_size) as total_guests'),
                    DB::raw('AVG(party_size) as avg_party_size')
                )
                ->first();

            return [
                'total_reservations' => (int) ($stats->total_reservations ?? 0),
                'confirmed' => (int) ($stats->confirmed ?? 0),
                'checked_in' => (int) ($stats->checked_in ?? 0),
                'cancelled' => (int) ($stats->cancelled ?? 0),
                'no_show' => (int) ($stats->no_show ?? 0),
                'total_guests' => (int) ($stats->total_guests ?? 0),
                'avg_party_size' => round((float) ($stats->avg_party_size ?? 0), 2),
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

