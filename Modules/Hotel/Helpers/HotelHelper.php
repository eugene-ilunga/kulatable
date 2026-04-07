<?php

namespace Modules\Hotel\Helpers;

use Modules\Hotel\Entities\Reservation;
use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Entities\Folio;
use Modules\Hotel\Entities\Event;
use App\Models\Order;
use Modules\Hotel\Enums\FolioLineType;

class HotelHelper
{
    /**
     * Post an order to a folio
     */
    public static function postOrderToFolio($order, $folio): bool
    {
        // Check if folio is open
        if ($folio->status !== \Modules\Hotel\Enums\FolioStatus::OPEN) {
            return false;
        }

        // Check if order is already posted
        if ($order->posted_to_folio_at) {
            return false;
        }

        // Order model uses 'total', 'sub_total', 'total_tax_amount', 'discount_amount' (no total_amount/net_amount)
        $amount = (float) ($order->total ?? $order->sub_total ?? 0);
        $taxAmount = (float) ($order->total_tax_amount ?? 0);
        $discountAmount = (float) ($order->discount_amount ?? 0);
        $netAmount = $amount;

        $folio->folioLines()->create([
            'type' => FolioLineType::FNB_POSTING,
            'description' => 'Food & Beverage - Order #' . ($order->order_number ?? $order->id),
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'posting_date' => now(),
            'posted_by' => auth()->id(),
        ]);

        // Mark order as posted
        $order->update([
            'posted_to_folio_at' => now(),
        ]);

        // Recalculate folio totals
        $folio->recalculateTotals();

        return true;
    }

    /**
     * Check if a stay can post charges (credit limit check)
     */
    public static function canPostToStay($stay): bool
    {
        $folio = $stay->folio;

        if (!$folio || $folio->status !== \Modules\Hotel\Enums\FolioStatus::OPEN) {
            return false;
        }

        // If credit limit is set, check balance
        if ($stay->credit_limit !== null) {
            $folio->recalculateTotals();
            return $folio->balance < $stay->credit_limit;
        }

        return true;
    }

    /**
     * Get room availability for date range
     */
    public static function getRoomAvailability($roomTypeId, $checkIn, $checkOut): int
    {
        $totalRooms = \Modules\Hotel\Entities\Room::where('room_type_id', $roomTypeId)
            ->where('is_active', true)
            ->count();

        $occupiedRooms = \Modules\Hotel\Entities\Stay::whereHas('room', function ($query) use ($roomTypeId) {
                $query->where('room_type_id', $roomTypeId);
            })
            ->where('status', \Modules\Hotel\Enums\StayStatus::CHECKED_IN)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where(function ($q) use ($checkIn, $checkOut) {
                    $q->where('check_in_at', '<=', $checkOut)
                      ->where('expected_checkout_at', '>=', $checkIn);
                });
            })
            ->count();

        return max(0, $totalRooms - $occupiedRooms);
    }
}
