<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\ReservationConfirmationSent;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendReservationConfirmationListener
{
    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReservationConfirmationSent $event): void
    {
        try {
            $reservation = $event->reservation;
            
            // Ensure customer and branch relationships are loaded
            if (!$reservation->relationLoaded('customer')) {
                $reservation->load('customer');
            }
            if (!$reservation->relationLoaded('branch')) {
                $reservation->load('branch.restaurant');
            }
            
            $restaurantId = $reservation->branch->restaurant_id ?? null;

            if (!$restaurantId) {
                Log::info('WhatsApp Reservation Confirmation Listener: Skipping - no restaurant_id', [
                    'reservation_id' => $reservation->id ?? null,
                ]);
                return;
            }

            // Check if WhatsApp module is in restaurant's package
            if (function_exists('restaurant_modules')) {
                $restaurant = \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        Log::info('WhatsApp Reservation Confirmation Listener: Skipping - WhatsApp module not in restaurant package', [
                            'reservation_id' => $reservation->id ?? null,
                            'restaurant_id' => $restaurantId,
                        ]);
                        return;
                    }
                }
            }

            Log::info('WhatsApp Reservation Confirmation Listener: Event triggered', [
                'reservation_id' => $reservation->id ?? null,
                'restaurant_id' => $restaurantId,
            ]);

            // Check if notification is enabled for customer
            // Check both old notification type (reservation_confirmation) and consolidated (reservation_notification)
            $customerPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where(function($query) {
                    $query->where('notification_type', 'reservation_notification')
                        ->orWhere('notification_type', 'reservation_confirmation');
                })
                ->where('recipient_type', 'customer')
                ->where('is_enabled', true)
                ->first();

            if (!$customerPreference) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping customer notification - preference not enabled");
                return;
            }

            if (!$reservation->customer) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping customer notification - no customer assigned");
                return;
            }

            // Get customer phone number (combine phone_code and phone, no + sign)
            $customerPhone = null;
            if ($reservation->customer->phone) {
                if ($reservation->customer->phone_code) {
                    $customerPhone = $reservation->customer->phone_code . $reservation->customer->phone;
                } else {
                    $customerPhone = $reservation->customer->phone;
                }
            }

            if (!$customerPhone) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping customer notification - customer has no phone number");
                return;
            }

            $variables = $this->getReservationVariables($reservation);
            
            $result = $this->notificationService->send(
                $restaurantId,
                'reservation_confirmation',
                $customerPhone,
                $variables,
                'en',
                'customer'
            );

            // Final result logged by WhatsAppNotificationService

        } catch (\Exception $e) {
            Log::error('WhatsApp Reservation Confirmation Listener Error: ' . $e->getMessage(), [
                'reservation_id' => $event->reservation->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function getReservationVariables($reservation): array
    {
        $customerName = $reservation->customer->name ?? 'Customer';
        $reservationDate = $reservation->reservation_date_time->format('d M, Y') ?? 'N/A';
        $reservationTime = $reservation->reservation_date_time->format('h:i A') ?? 'N/A';
        $partySize = $reservation->party_size ?? 'N/A';
        
        // Get table name if assigned, otherwise "Not assigned"
        $tableName = 'Not assigned';
        if ($reservation->table_id && $reservation->table) {
            $tableName = $reservation->table->table_code ?? 'Table #' . $reservation->table_id;
        }
        
        $restaurantName = $reservation->branch->restaurant->name ?? '';
        $branchName = $reservation->branch->name ?? '';
        $contactNumber = $reservation->branch->restaurant->contact_number ?? '';
        $restaurantHash = $reservation->branch->restaurant->hash ?? '';

        return [
            $customerName,        // Index 0: Customer name
            $reservationDate,      // Index 1: Date
            $reservationTime,      // Index 2: Time
            $partySize,            // Index 3: Number of guests
            $tableName,            // Index 4: Table name or "Not assigned"
            $restaurantName,       // Index 5: Restaurant name
            $branchName,           // Index 6: Branch name
            $contactNumber,        // Index 7: Contact number
            $restaurantHash,       // Index 8: Restaurant hash/slug for button URL
        ];
    }
}

