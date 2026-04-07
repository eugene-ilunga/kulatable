<?php

namespace Modules\Sms\Listeners;

use App\Events\ReservationConfirmationSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Sms\Notifications\ReservationConfirmation;

class SmsReservationConfirmationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ReservationConfirmationSent $event): void
    {
        try {
            $customer = $event->reservation->customer;
            if ($customer && $customer->email) {
                $customer->notify(new ReservationConfirmation($event->reservation));
            }
        } catch (\Exception $e) {
            Log::error('Error sending reservation confirmation email: ' . $e->getMessage());
        }
    }
} 