<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\ReservationReceived;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendReservationReceivedListener
{
    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReservationReceived $event): void
    {
        try {
            $reservation = $event->reservation;
            $restaurantId = $reservation->branch->restaurant_id ?? null;

            if (!$restaurantId) {
                return;
            }

            // Check if WhatsApp module is in restaurant's package
            if (function_exists('restaurant_modules')) {
                $restaurant = $reservation->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        return;
                    }
                }
            }

            // Check if notification is enabled for admin
            $adminPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where('notification_type', 'new_order_alert')
                ->where('recipient_type', 'admin')
                ->where('is_enabled', true)
                ->first();

            if (!$adminPreference) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping admin notification - preference not enabled");
                return;
            }

            // Get admin users with phone numbers (remove branch scope to get all admins for restaurant)
            $admins = \App\Models\User::withoutGlobalScope(\App\Scopes\BranchScope::class)
                ->role('Admin_' . $restaurantId)
                ->where('restaurant_id', $restaurantId)
                ->whereNotNull('phone_number')
                ->get();

            if ($admins->isEmpty()) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping admin notification - no admins with phone numbers found");
                return;
            }

                foreach ($admins as $admin) {
                    // Get admin phone number (combine phone_code and phone_number, no + sign)
                    $adminPhone = null;
                    if ($admin->phone_number) {
                        if ($admin->phone_code) {
                            $adminPhone = $admin->phone_code . $admin->phone_number;
                        } else {
                            $adminPhone = $admin->phone_number;
                        }
                    }

                    if (!$adminPhone) {
                        Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping admin '{$admin->name}' - no valid phone number");
                        continue;
                    }

                    $variables = $this->getReservationAlertVariables($reservation, $admin->name);
                    
                    $result = $this->notificationService->send(
                        $restaurantId,
                        'new_order_alert',
                        $adminPhone,
                        $variables,
                        'en',
                        'admin'
                    );
                    // Final result logged by WhatsAppNotificationService
                }

        } catch (\Exception $e) {
            Log::error('WhatsApp Reservation Received Listener Error: ' . $e->getMessage(), [
                'reservation_id' => $event->reservation->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getReservationAlertVariables($reservation, string $recipientName = 'Admin'): array
    {
        // For new_order_alert template: [recipient_name, message_context, order_number, order_type, customer_name, amount]
        // But for reservations, we'll adapt it: [recipient_name, message_context, reservation_info, reservation_type, customer_name, amount]
        $customerName = $reservation->customer->name ?? 'Guest';
        $reservationDate = $reservation->reservation_date_time->format('d M, Y') ?? 'N/A';
        $reservationTime = $reservation->reservation_date_time->format('h:i A') ?? 'N/A';
        $partySize = $reservation->party_size ?? 'N/A';
        $reservationInfo = "Reservation on {$reservationDate} at {$reservationTime} for {$partySize} guests";

        return [
            $recipientName, // {{1}} - Recipient name
            'New', // {{2}} - Message context
            $reservationInfo, // {{3}} - Reservation info (instead of order number)
            'Reservation', // {{4}} - Type
            $customerName, // {{5}} - Customer name
            'N/A', // {{6}} - Amount (not applicable for reservations)
            $reservation->id ?? null, // Order ID for button URL (reservation ID)
        ];
    }
}

