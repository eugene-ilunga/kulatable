<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\NotifyWaiter;
use App\Models\User;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppPhoneResolver;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendNotifyWaiterListener
{
    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(NotifyWaiter $event): void
    {
        try {
            // Get table from table number
            $table = \App\Models\Table::where('table_code', $event->tableNumber)
                ->orWhere('id', $event->tableNumber)
                ->with(['branch.restaurant'])
                ->first();

            if (!$table || !$table->branch) {
                return;
            }

            $restaurantId = $table->branch->restaurant_id ?? null;

            if (!$restaurantId) {
                return;
            }

            // Check if WhatsApp module is in restaurant's package
            if (function_exists('restaurant_modules')) {
                $restaurant = $table->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        return;
                    }
                }
            }

            // Check if notification is enabled for staff
            $staffPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where(function ($query) {
                    $query->where('notification_type', 'waiter_request_acknowledgment')
                        ->orWhere('notification_type', 'staff_notification');
                })
                ->where('recipient_type', 'staff')
                ->where('is_enabled', true)
                ->first();

            if ($staffPreference) {
                $staff = $this->getStaffRecipients($restaurantId);

                $tableNumber = $table->table_code ?? $event->tableNumber ?? 'N/A';
                $restaurantName = $table->branch->restaurant->name ?? '';

                foreach ($staff as $staffMember) {
                    $staffPhone = WhatsAppPhoneResolver::fromUser($staffMember);
                    if (!$staffPhone) {
                        continue;
                    }

                    $variables = [
                        $staffMember->name ?? 'Staff',
                        $staffMember->name ?? 'Staff',
                        'waiter acknowledgment',
                        "Table {$tableNumber}",
                        $restaurantName ? "Restaurant: {$restaurantName}" : 'Waiter request acknowledged',
                    ];
                    
                    $this->notificationService->send(
                        $restaurantId,
                        'waiter_request_acknowledgment',
                        $staffPhone,
                        $variables
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp Notify Waiter Listener Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getStaffRecipients(int $restaurantId)
    {
        return User::where('restaurant_id', $restaurantId)
            ->whereNotNull('phone_number')
            ->whereHas('roles', function ($query) {
                $query->whereIn('display_name', ['Staff', 'Waiter']);
            })
            ->get();
    }
}
