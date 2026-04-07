<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\ReservationTableAssigned;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendReservationTableAssignedListener
{
    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(ReservationTableAssigned $event): void
    {
        try {
            $reservation = $event->reservation;

            if (!$reservation->relationLoaded('customer')) {
                $reservation->load('customer');
            }
            if (!$reservation->relationLoaded('table')) {
                $reservation->load('table');
            }
            if (!$reservation->relationLoaded('branch')) {
                $reservation->load('branch.restaurant');
            }

            $restaurantId = $reservation->branch->restaurant_id ?? null;
            $currentTableId = $reservation->table_id ?? null;
            $previousTableId = $event->previousTableId;

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

            // Only send if a table was actually assigned (not removed)
            if (!$currentTableId) {
                return;
            }

            // Skip if table didn't change (already had this table)
            if ($currentTableId === $previousTableId) {
                return;
            }

            // Determine if this is a first assignment or a table change
            $isTableChange = $previousTableId !== null;


            // Check if notification is enabled for customer
            $customerPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where(function ($query) {
                    $query->where('notification_type', 'reservation_notification')
                        ->orWhere('notification_type', 'reservation_status_update');
                })
                ->where('recipient_type', 'customer')
                ->where('is_enabled', true)
                ->first();

            if (!$customerPreference) {
                return;
            }

            if (!$reservation->customer) {
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
                return;
            }

            $variables = $this->getReservationVariables($reservation, $isTableChange);
            
            $result = $this->notificationService->send(
                $restaurantId,
                'reservation_status_update',
                $customerPhone,
                $variables,
                'en',
                'customer'
            );


        } catch (\Exception $e) {
            Log::error('WhatsApp Reservation Table Assigned Listener Error: ' . $e->getMessage(), [
                'reservation_id' => $event->reservation->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getReservationVariables($reservation, bool $isTableChange = false): array
    {
        // Ensure relationships are loaded
        if (!$reservation->relationLoaded('table')) {
            $reservation->load('table');
        }
        if (!$reservation->relationLoaded('branch')) {
            $reservation->load('branch.restaurant');
        }
        
        $customerName = $reservation->customer->name ?? 'Customer';
        $reservationDate = $reservation->reservation_date_time->format('d M, Y') ?? 'N/A';
        $reservationTime = $reservation->reservation_date_time->format('h:i A') ?? 'N/A';
        $partySize = $reservation->party_size ?? 'N/A';
        
        // Get table name - should always be assigned at this point
        $tableName = 'Not assigned';
        if ($reservation->table_id && $reservation->table) {
            $tableName = $reservation->table->table_code ?? 'Table #' . $reservation->table_id;
        }
        
        $restaurantName = $reservation->branch->restaurant->name ?? '';
        $branchName = $reservation->branch->name ?? '';
        $contactNumber = $reservation->branch->restaurant->contact_number ?? '';
        $restaurantHash = $reservation->branch->restaurant->hash ?? '';

        // Get actual reservation status (should be Confirmed when table is assigned)
        $reservationStatus = $reservation->reservation_status ?? 'Confirmed';
        // Use Confirmed status when table is assigned
        $statusDisplay = 'Confirmed';

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
            $statusDisplay,        // Index 9: Status (Confirmed when table is assigned)
        ];
    }
}
