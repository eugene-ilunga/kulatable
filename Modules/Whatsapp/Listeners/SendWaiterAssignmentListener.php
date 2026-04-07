<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\OrderWaiterAssigned;
use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Modules\Whatsapp\Services\WhatsAppPhoneResolver;

class SendWaiterAssignmentListener
{
    public function __construct(
        private WhatsAppNotificationService $notificationService
    ) {
    }

    public function handle(OrderWaiterAssigned $event): void
    {
        try {
            $order = $event->order->loadMissing(['waiter', 'table', 'branch.restaurant']);
            $restaurantId = $order->branch->restaurant_id ?? null;

            if (!$restaurantId || !$order->waiter) {
                return;
            }

            if (function_exists('restaurant_modules')) {
                $restaurant = $order->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant && !in_array('Whatsapp', restaurant_modules($restaurant))) {
                    return;
                }
            }

            $preference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where(function ($query) {
                    $query->where('notification_type', 'table_assignment')
                        ->orWhere('notification_type', 'staff_notification');
                })
                ->where('recipient_type', 'staff')
                ->where('is_enabled', true)
                ->first();

            $waiterPhone = WhatsAppPhoneResolver::fromUser($order->waiter);
            if (!$preference || !$waiterPhone) {
                return;
            }

            $tableLabel = $order->table?->table_code ? "Table {$order->table->table_code}" : 'No table assigned';
            $detail = $event->previousWaiter && $event->previousWaiter->id !== $order->waiter->id
                ? "{$tableLabel} has been assigned to you for Order #{$order->show_formatted_order_number}"
                : "You have been assigned to Order #{$order->show_formatted_order_number} ({$tableLabel})";

            $variables = [
                $order->waiter->name ?? 'Waiter',
                $order->waiter->name ?? 'Waiter',
                'table assignment',
                "Order #{$order->show_formatted_order_number}",
                $detail,
            ];

            $sent = $this->notificationService->send(
                $restaurantId,
                'table_assignment',
                $waiterPhone,
                $variables,
                'en',
                'waiter'
            );
        } catch (\Exception $e) {
            Log::error('WhatsApp Waiter Assignment Error: ' . $e->getMessage(), [
                'order_id' => $event->order->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
