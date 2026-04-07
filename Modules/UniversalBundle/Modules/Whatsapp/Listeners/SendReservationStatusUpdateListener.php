<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\ReservationStatusUpdated;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendReservationStatusUpdateListener
{
    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReservationStatusUpdated $event): void
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
            $currentStatus = $reservation->reservation_status ?? 'N/A';
            $previousStatus = $event->previousStatus;

            if (!$restaurantId) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping status update - no restaurant_id");
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

            // Only send notification for status changes to Cancelled, Pending, or Confirmed
            if (!in_array($currentStatus, ['Cancelled', 'Pending', 'Confirmed'])) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping status update - status '{$currentStatus}' not in [Cancelled, Pending, Confirmed]");
                return;
            }

            // Check if notification is enabled for customer
            // Check both old notification type (reservation_status_update) and consolidated (reservation_notification)
            $customerPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where(function($query) {
                    $query->where('notification_type', 'reservation_notification')
                        ->orWhere('notification_type', 'reservation_status_update');
                })
                ->where('recipient_type', 'customer')
                ->where('is_enabled', true)
                ->first();

            if (!$customerPreference) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping status update ({$currentStatus}) - preference not enabled");
                return;
            }

            if (!$reservation->customer) {
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping status update ({$currentStatus}) - no customer assigned");
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
                Log::info("WhatsApp Reservation #{$reservation->id}: ⏭️ Skipping status update ({$currentStatus}) - customer has no phone number");
                return;
            }

            $variables = $this->getReservationVariables($reservation, $currentStatus);
            
            $result = $this->notificationService->send(
                $restaurantId,
                'reservation_status_update',
                $customerPhone,
                $variables,
                'en',
                'customer'
            );
            // Final result logged by WhatsAppNotificationService

        } catch (\Exception $e) {
            Log::error('WhatsApp Reservation Status Update Listener Error: ' . $e->getMessage(), [
                'reservation_id' => $event->reservation->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getReservationVariables($reservation, $status): array
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

        // Format status for display
        $statusDisplay = match($status) {
            'Cancelled' => 'Cancelled',
            'Pending' => 'Pending',
            'Confirmed' => 'Confirmed',
            'Checked_In' => 'Checked In',
            'No_Show' => 'No Show',
            default => 'Updated',
        };

        // Variables format expected by template mapper: [customer_name, date, time, party_size, table_name, restaurant_name, branch_name, contact_number, restaurant_hash, status]
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
            $statusDisplay,        // Index 9: Status (Cancelled, Confirmed, Pending)
        ];
    }
}

