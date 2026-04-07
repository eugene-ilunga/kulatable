<?php

namespace Modules\Hotel\Observers;

use App\Models\Order;
use Illuminate\Support\Facades\Session;
use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Helpers\HotelHelper;

class OrderObserver
{
    /**
     * Handle the Order "creating" event.
     * Inject hotel context from session if available.
     */
    public function creating(Order $order): void
    {
        // Only inject hotel context if Hotel module is enabled
        if (!module_enabled('Hotel')) {
            return;
        }

        // Get hotel context from session
        $hotelContext = Session::get('hotel_context');

        if ($hotelContext) {
            $order->context_type = $hotelContext['context_type'] ?? null;
            $order->context_id = $hotelContext['context_id'] ?? null;
            $order->bill_to = $hotelContext['bill_to'] ?? 'PAY_NOW';
        }
    }

    /**
     * Handle the Order "updating" event.
     * Preserve hotel context if it's being updated.
     */
    public function updating(Order $order): void
    {
        // Only handle hotel context if Hotel module is enabled
        if (!module_enabled('Hotel')) {
            return;
        }

        // If hotel context is in session and order doesn't have it yet, inject it
        if (!$order->context_type && !$order->context_id) {
            $hotelContext = Session::get('hotel_context');

            if ($hotelContext) {
                $order->context_type = $hotelContext['context_type'] ?? null;
                $order->context_id = $hotelContext['context_id'] ?? null;
                $order->bill_to = $hotelContext['bill_to'] ?? 'PAY_NOW';
            }
        }
    }

    /**
     * Handle the Order "updated" event.
     * Post room service orders to guest folio when order status becomes served.
     */
    public function updated(Order $order): void
    {
        if (!module_enabled('Hotel')) {
            return;
        }

        // Post to folio when order is marked served/delivered and bill_to is POST_TO_ROOM
        if (!$order->wasChanged('order_status')) {
            return;
        }

        $status = $order->order_status->value ?? $order->order_status;
        if (!in_array($status, ['served', 'delivered'])) {
            return;
        }

        if (($order->bill_to ?? '') !== 'POST_TO_ROOM') {
            return;
        }

        if (($order->context_type ?? '') !== 'HOTEL_ROOM' || empty($order->context_id)) {
            return;
        }

        if ($order->posted_to_folio_at) {
            return;
        }

        $stay = Stay::with('folio')->find($order->context_id);
        if (!$stay || !$stay->folio) {
            return;
        }

        HotelHelper::postOrderToFolio($order, $stay->folio);
    }
}
