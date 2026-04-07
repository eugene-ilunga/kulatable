<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\OrderTableAssigned;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\Services\WhatsAppNotificationService;

class SendTableAssignmentListener // Temporarily removed ShouldQueue for testing
{
    // Temporarily removed InteractsWithQueue for testing

    public function __construct(
        private WhatsAppNotificationService $notificationService
    ) {}

    public function handle(OrderTableAssigned $event): void
    {
        try {
            $order = $event->order->load(['customer', 'branch.restaurant']);
            $newTable = $event->newTable;
            $previousTable = $event->previousTable;

            // Check if WhatsApp module is in restaurant's package
            $restaurantId = $order->branch->restaurant_id ?? null;
            if ($restaurantId && function_exists('restaurant_modules')) {
                $restaurant = $order->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        return;
                    }
                }
            }

            // Get waiter assigned to the order (if any)
            $waiter = $order->waiter;
            
            // If no waiter assigned to order, try to get waiter assigned to the new table
            if (!$waiter && $newTable->waiter_id) {
                $waiter = User::find($newTable->waiter_id);
            }

            // Send notification to waiter
            if ($waiter && $waiter->phone_number) {
                $this->notifyWaiter($order, $newTable, $previousTable, $waiter);
            }

            // Send notification to staff users if staff notification preference is enabled
            $this->notifyStaff($order, $newTable, $previousTable, $waiter?->id);

            // Send notification to admin/manager
            $this->notifyAdmins($order, $newTable, $previousTable);

            // Send notification to customer (if order has a customer)
            $this->notifyCustomer($order, $newTable, $previousTable);

        } catch (\Exception $e) {
            // Log the error with order details for debugging
        }
    }

    private function notifyWaiter($order, $newTable, $previousTable, $waiter): void
    {
        $restaurantId = $order->branch->restaurant_id;
        $phone = $this->formatPhoneNumber($waiter->phone_code, $waiter->phone_number);

        // formatStaffNotification expects: [header_status, staff_name, notification_type, target, details]
        // Template: Header "Status: {{1}}" + Body "Hello {{1}}, regarding {{2}} for {{3}}. Detail: {{4}}"
        // Since {{1}} is shared between header and body, we need waiter name in both positions
        $variables = [
            $waiter->name,                                    // [0] - Header {{1}} - Status (waiter name)
            $waiter->name,                                    // [1] - Body {{1}} - Staff name (after "Hello")
            'table assignment',                               // [2] - Body {{2}} - Notification type
            "Order #{$order->show_formatted_order_number}",  // [3] - Body {{3}} - Order reference
            $this->getTableAssignmentMessage($newTable, $previousTable) // [4] - Body {{4}} - Assignment details
        ];

        $sent = $this->notificationService->send(
            $restaurantId,
            'table_assignment',
            $phone,
            $variables,
            'en',
            'waiter'
        );
    }

    private function notifyStaff($order, $newTable, $previousTable, ?int $excludeUserId = null): void
    {
        $restaurantId = $order->branch->restaurant_id;

        $preference = \Modules\Whatsapp\Entities\WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
            ->where(function ($query) {
                $query->where('notification_type', 'table_assignment')
                    ->orWhere('notification_type', 'staff_notification');
            })
            ->where('recipient_type', 'staff')
            ->where('is_enabled', true)
            ->first();

        if (!$preference) {
            return;
        }

        $staffMembers = User::where('restaurant_id', $restaurantId)
            ->whereNotNull('phone_number')
            ->whereHas('roles', function ($query) {
                $query->whereIn('display_name', ['Staff', 'Waiter']);
            })
            ->when($excludeUserId, function ($query) use ($excludeUserId) {
                $query->where('id', '!=', $excludeUserId);
            })
            ->get();

        foreach ($staffMembers as $staffMember) {
            $phone = $this->formatPhoneNumber($staffMember->phone_code, $staffMember->phone_number);

            $variables = [
                $staffMember->name,
                $staffMember->name,
                'table assignment',
                "Order #{$order->show_formatted_order_number}",
                $this->getTableAssignmentMessage($newTable, $previousTable),
            ];

            $sent = $this->notificationService->send(
                $restaurantId,
                'table_assignment',
                $phone,
                $variables,
                'en',
                'staff'
            );
        }
    }

    private function notifyAdmins($order, $newTable, $previousTable): void
    {
        $restaurantId = $order->branch->restaurant_id;
        
        // Get admin users for this restaurant
        $admins = User::role("Admin_{$restaurantId}")
            ->where('restaurant_id', $restaurantId)
            ->whereNotNull('phone_number')
            ->get();

        foreach ($admins as $admin) {
            $phone = $this->formatPhoneNumber($admin->phone_code, $admin->phone_number);

            // formatStaffNotification expects: [header_status, staff_name, notification_type, target, details]
            // Template: Header "Status: {{1}}" + Body "Hello {{1}}, regarding {{2}} for {{3}}. Detail: {{4}}"
            // Since {{1}} is shared between header and body, we need admin name in both positions
            $variables = [
                $admin->name,                                     // [0] - Header {{1}} - Status (admin name)
                $admin->name,                                     // [1] - Body {{1}} - Staff name (after "Hello")
                'table assignment',                               // [2] - Body {{2}} - Notification type
                "Order #{$order->show_formatted_order_number}",  // [3] - Body {{3}} - Order reference
                $this->getTableAssignmentMessage($newTable, $previousTable) // [4] - Body {{4}} - Assignment details
            ];

            $sent = $this->notificationService->send(
                $restaurantId,
                'table_assignment',
                $phone,
                $variables,
                'en',
                'admin'
            );
        }
    }

    private function getTableAssignmentMessage($newTable, $previousTable): string
    {
        if ($previousTable) {
            return "Table changed from {$previousTable->table_code} to {$newTable->table_code}";
        }
        
        return "Table {$newTable->table_code} has been assigned";
    }

    private function notifyCustomer($order, $newTable, $previousTable): void
    {
        // Check if order has a customer
        if (!$order->customer_id || !$order->customer) {
            return;
        }

        $customer = $order->customer;
        if (!$customer->phone) {
            return;
        }

        $restaurantId = $order->branch->restaurant_id;
        $phone = $this->formatPhoneNumber($customer->phone_code, $customer->phone);

        // Use order_notification template for customer table changes
        // Template expects exactly 5 body parameters + button parameters
        $orderNumber = $order->show_formatted_order_number;
        // Extract numeric part for display (e.g., "Order #22" -> "22")
        $orderNumberNumeric = $orderNumber;
        if (preg_match('/(\d+)/', $orderNumber, $matches)) {
            $orderNumberNumeric = $matches[1];
        }

        $variables = [
            // Body parameters (5 required)
            $customer->name,                                  // {{1}} - Customer name
            'your table assignment has been updated',         // {{2}} - Main message
            $orderNumberNumeric,                             // {{3}} - Order number (numeric only)
            $this->getCustomerTableMessage($newTable, $previousTable), // {{4}} - Table change details
            'Please check your new table location',          // {{5}} - Additional information
        ];

        // The button URL parameter is handled separately by the notification service
        $restaurantHash = $order->branch->restaurant->restaurantHash;
        $orderId = $order->id;

        // Use order_notifications template for customer (with View Order button)
        // Template expects: [customer_name, message, order_number, order_details, estimated_time, restaurant_name, contact_number, order_id, restaurant_hash]
        $orderNumber = $order->show_formatted_order_number;
        // Extract numeric part for display (e.g., "Order #22" -> "22")
        $orderNumberNumeric = $orderNumber;
        if (preg_match('/(\d+)/', $orderNumber, $matches)) {
            $orderNumberNumeric = $matches[1];
        }

        $orderVariables = [
            $customer->name,                                  // {{1}} - Customer name
            'your table assignment has been updated',         // {{2}} - Main message
            $orderNumberNumeric,                             // {{3}} - Order number (numeric only)
            $this->getCustomerTableMessage($newTable, $previousTable), // {{4}} - Table change details
            'Please check your new table location',          // {{5}} - Additional information
            $order->branch->restaurant->name,                 // {{6}} - Restaurant name
            $order->branch->restaurant->phone ?? 'N/A',      // {{7}} - Contact number
            $order->id,                                       // {{8}} - Order ID (for button)
            $order->branch->restaurant->restaurantHash        // {{9}} - Restaurant hash (for button)
        ];

        $sent = $this->notificationService->send(
            $restaurantId,
            'order_status_update',
            $phone,
            $orderVariables,
            'en',
            'customer'
        );
    }

    private function getCustomerTableMessage($newTable, $previousTable): string
    {
        if ($previousTable) {
            return "Your table has been changed from {$previousTable->table_code} to {$newTable->table_code}";
        }
        
        return "Table {$newTable->table_code} has been assigned to your order";
    }

    private function formatPhoneNumber(?string $phoneCode, ?string $phoneNumber): string
    {
        if (!$phoneNumber) {
            return '';
        }

        $phoneCode = $phoneCode ?: '91'; // Default to India
        return $phoneCode . $phoneNumber;
    }
}
