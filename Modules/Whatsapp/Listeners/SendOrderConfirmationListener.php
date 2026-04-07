<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\NewOrderCreated;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationListener
{
    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(NewOrderCreated $event): void
    {
        try {
            $order = $event->order;
            $restaurantId = $order->branch->restaurant_id ?? null;

            // Idempotency guard: ensure we only process this order once, even if NewOrderCreated
            // is dispatched multiple times from different parts of the app (POS, Cart, Observer, etc.).
            $cacheKey = 'whatsapp_order_confirmation_handled_' . $order->id;
            if (cache()->has($cacheKey)) {
                return; // Already processed, skip silently
            }
            // Mark as handled for a short window; enough to dedupe multiple dispatches for same order.
            cache()->put($cacheKey, true, now()->addMinutes(15));

            Log::info("WhatsApp Order #{$order->id} ({$order->show_formatted_order_number}): Processing notifications");

            if (!$restaurantId) {
                Log::warning('WhatsApp Order Confirmation Listener: No restaurant ID found', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // Check if WhatsApp module is in restaurant's package
            if (function_exists('restaurant_modules')) {
                $restaurant = \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        Log::info('WhatsApp Order Confirmation Listener: Skipping - WhatsApp module not in restaurant package', [
                            'order_id' => $order->id,
                            'restaurant_id' => $restaurantId,
                        ]);
                        return;
                    }
                }
            }

            // Check if notification is enabled for customer
            // Check both old notification type (order_confirmation) and consolidated (order_notification)
            $customerPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where(function($query) {
                    $query->where('notification_type', 'order_notifications')
                        ->orWhere('notification_type', 'order_confirmation');
                })
                ->where('recipient_type', 'customer')
                ->where('is_enabled', true)
                ->first();

            // Get customer phone number (combine phone_code and phone, no + sign)
            $customerPhone = null;
            if ($order->customer && $order->customer->phone) {
                if ($order->customer->phone_code) {
                    $customerPhone = $order->customer->phone_code . $order->customer->phone;
                } else {
                    $customerPhone = $order->customer->phone;
                }
            }

            if ($customerPreference && $customerPhone) {

                $variables = $this->getOrderVariables($order);

                // Use order_confirmation as the notification type (will be mapped to order_notifications by the service)
                $result = $this->notificationService->send(
                    $restaurantId,
                    'order_confirmation',
                    $customerPhone,
                    $variables,
                    'en',
                    'customer'
                );

            } else {
                $reason = !$customerPreference ? 'Notification not enabled' : 
                        (!$order->customer ? 'No customer assigned' : 'Customer has no phone number');
                Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping customer notification - {$reason}");
            }

            // Check if notification is enabled for admin/staff
            $adminPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where('notification_type', 'new_order_alert')
                ->where('recipient_type', 'admin')
                ->where('is_enabled', true)
                ->first();

            if ($adminPreference) {
                // Get admin users with phone numbers (remove branch scope to get all admins for restaurant)
                $admins = \App\Models\User::withoutGlobalScope(\App\Scopes\BranchScope::class)
                    ->role('Admin_' . $restaurantId)
                    ->where('restaurant_id', $restaurantId)
                    ->whereNotNull('phone_number')
                    ->get();

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
                        Log::warning("WhatsApp Order #{$order->id}: Admin '{$admin->name}' has no phone number");
                        continue;
                    }

                    // For new_order_alert, we need to include admin name in variables
                    $variables = $this->getOrderAlertVariables($order, $admin->name, $order->id);
                    
                    // Check if admin phone is same as customer phone (might cause confusion)
                    $customerPhoneForComparison = null;
                    if ($order->customer && $order->customer->phone) {
                        if ($order->customer->phone_code) {
                            $customerPhoneForComparison = $order->customer->phone_code . $order->customer->phone;
                        } else {
                            $customerPhoneForComparison = $order->customer->phone;
                        }
                    }
                    
                    $result = $this->notificationService->send(
                        $restaurantId,
                        'new_order_alert',
                        $adminPhone,
                        $variables,
                        'en',
                        'admin'
                    );

                }
            } else {
                Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping admin notification - not enabled");
            }

            // Check if notification is enabled for assigned waiter/staff on new orders
            $waiterPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where('notification_type', 'new_order_alert')
                ->where('recipient_type', 'staff')
                ->where('is_enabled', true)
                ->first();

            if ($waiterPreference) {
                $waiter = $order->waiter;
                $waiterPhone = \Modules\Whatsapp\Services\WhatsAppPhoneResolver::fromUser($waiter);

                if (!$waiter) {
                    Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping waiter notification - no waiter assigned");
                } elseif (!$waiterPhone) {
                    Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping waiter notification - waiter '{$waiter->name}' has no phone number");
                } else {
                    $variables = $this->getOrderAlertVariables($order, $waiter->name, $order->id);

                    $this->notificationService->send(
                        $restaurantId,
                        'new_order_alert',
                        $waiterPhone,
                        $variables,
                        'en',
                        'staff'
                    );
                }
            } else {
                Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping waiter notification - preference not enabled");
            }

            // Check if notification is enabled for delivery executives (only for delivery orders)
            $orderTypeName = $this->getOrderTypeName($order);
            $isDeliveryOrder = ($order->order_type === 'delivery') || 
                            (strtolower($orderTypeName) === 'delivery');
            
            if ($isDeliveryOrder) {
                $deliveryPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                    ->where('notification_type', 'new_order_alert')
                    ->where('recipient_type', 'delivery')
                    ->where('is_enabled', true)
                    ->first();

                if ($deliveryPreference) {
                    // Only notify the assigned delivery executive, not all available ones
                    $assignedExecutive = null;
                    if ($order->delivery_executive_id) {
                        $assignedExecutive = \App\Models\DeliveryExecutive::find($order->delivery_executive_id);
                    }

                    if (!$assignedExecutive) {
                        Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping delivery notification - no delivery executive assigned");
                    } else {

                        $executive = $assignedExecutive;
                        
                        // Get delivery executive phone number (combine phone_code and phone, no + sign)
                        $executivePhone = null;
                        if ($executive->phone) {
                            if ($executive->phone_code) {
                                $executivePhone = $executive->phone_code . $executive->phone;
                            } else {
                                $executivePhone = $executive->phone;
                            }
                        }
                        
                        // Clean phone number: remove all non-numeric characters
                        if ($executivePhone) {
                            $executivePhone = preg_replace('/[^0-9]/', '', $executivePhone);
                        }

                        if (!$executivePhone) {
                            Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping delivery notification - delivery executive '{$executive->name}' has no phone number");
                        } else {
                            // For new_order_alert, we need to include executive name in variables
                            $variables = $this->getOrderAlertVariables($order, $executive->name, $order->id);

                            $result = $this->notificationService->send(
                                $restaurantId,
                                'new_order_alert',
                                $executivePhone,
                                $variables,
                                'en',
                                'delivery'
                            );
                            // Final result logged by WhatsAppNotificationService
                        }
                    }
                } else {
                    Log::info("WhatsApp Order #{$order->id}: ⏭️ Skipping delivery notification - preference not enabled");
                }
            }

            // Kitchen staff will receive kitchen_notification when KOT is created.
            // Assigned waiters can also receive new_order_alert when the staff preference is enabled.

        } catch (\Exception $e) {
            Log::error('WhatsApp Order Confirmation Listener Error: ' . $e->getMessage(), [
                'order_id' => $event->order->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Resolve order type name via order_type_id from order_types table.
     * Falls back to 'N/A' if not found.
     */
    protected function getOrderTypeName($order): string
    {
        // Prefer the Eloquent relationship, falling back to a direct lookup without global scopes.
        $orderType = null;

        if ($order->relationLoaded('orderType')) {
            $orderType = $order->orderType;
        } else {
            $orderTypeRelation = $order->orderType();
            // Remove any global scopes when resolving, in case branch scopes interfere
            $orderType = $orderTypeRelation->withoutGlobalScopes()->first();
        }

        if (!$orderType) {
            return 'N/A';
        }

        // Try the most descriptive name fields in order
        if (!empty($orderType->order_type_name)) {
            return $orderType->order_type_name;
        }

        if (!empty($orderType->type)) {
            return $orderType->type;
        }

        return !empty($orderType->name) ? $orderType->name : 'N/A';
    }

    /**
     * Calculate preparation time or estimated delivery time for the order.
     * Matches the logic used on the customer site.
     */
    protected function getEstimatedTime($order): string
    {
        // For delivery orders, use estimated_eta_max if available
        if ($order->order_type === 'delivery' && !is_null($order->estimated_eta_max)) {
            return $order->estimated_eta_max . ' minutes';
        }

        // For other orders, get the maximum preparation time from order items
        $maxPreparationTime = null;
        
        // Always load items with menuItem relationship to ensure we have preparation_time
        $items = $order->items()->with('menuItem')->get();

        if ($items && $items->isNotEmpty()) {
            $preparationTimes = [];
            foreach ($items as $item) {
                // Handle cases where menuItem might be null
                if (!$item->menuItem) {
                    Log::warning("WhatsApp Order #{$order->id}: Item #{$item->id} has no menuItem", [
                        'item_id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                    ]);
                    continue;
                }
                // Get preparation time as integer (no rounding)
                $prepTime = $item->menuItem->preparation_time ?? 0;
                $prepTime = (int) $prepTime;
                if ($prepTime > 0) {
                    $preparationTimes[] = $prepTime;
                }
            }
            
            if (!empty($preparationTimes)) {
                $maxPreparationTime = max($preparationTimes);
                Log::info("WhatsApp Order #{$order->id}: Calculated preparation time", [
                    'max_preparation_time' => $maxPreparationTime,
                    'all_preparation_times' => $preparationTimes,
                    'items_count' => $items->count(),
                ]);
            } else {
                Log::warning("WhatsApp Order #{$order->id}: No valid preparation times found", [
                    'items_count' => $items->count(),
                ]);
            }
        } else {
            Log::warning("WhatsApp Order #{$order->id}: No items found for preparation time calculation");
        }

        if ($maxPreparationTime && $maxPreparationTime > 0) {
            // Return exact value without rounding
            return (string) $maxPreparationTime . ' minutes';
        }

        // Fallback to default
        Log::warning("WhatsApp Order #{$order->id}: Using fallback preparation time (30 minutes)");
        return '30 minutes';
    }

    protected function getOrderVariables($order): array
    {
        $customerName = $order->customer->name ?? 'Customer';
        $orderNumber = $order->show_formatted_order_number ?? 'N/A';
        $orderType = $this->getOrderTypeName($order);
        // Get total amount with GST/taxes included - always use total field (includes all taxes, charges, discounts)
        $totalAmount = $this->getOrderTotal($order);
        $currency = $order->branch->restaurant->currency->currency_symbol ?? '';
        $estimatedTime = $this->getEstimatedTime($order);
        $restaurantName = $order->branch->restaurant->name ?? '';
        $contactNumber = $order->branch->restaurant->contact_number ?? '';

        $restaurantHash = $order->branch->restaurant->hash ?? null;

        return [
            $customerName,        // [0] Customer name
            $orderNumber,         // [1] Order number
            $orderType,           // [2] Order type
            $currency . number_format($totalAmount, 2), // [3] Total amount (with GST/taxes)
            $estimatedTime,       // [4] Estimated time
            $restaurantName,      // [5] Restaurant name
            $contactNumber,       // [6] Contact number
            $order->id ?? null,   // [7] Order ID (for button URL)
            $restaurantHash,      // [8] Restaurant hash (for button URL)
        ];
    }

    protected function getOrderAlertVariables($order, string $recipientName = 'Admin', ?int $orderId = null): array
    {
        // For new_order_alert template: [recipient_name, message_context, order_number, order_type, customer_name, amount]
        $orderNumber = $order->show_formatted_order_number ?? 'N/A';
        $orderType = $this->getOrderTypeName($order);
        // Get total amount with GST/taxes included - always use total field (includes all taxes, charges, discounts)
        $totalAmount = $this->getOrderTotal($order);
        $currency = $order->branch->restaurant->currency->currency_symbol ?? '';
        $customerName = $order->customer->name ?? 'Guest';
        $restaurantHash = $order->branch->restaurant->hash ?? null;

        return [
            $recipientName, // {{1}} - Recipient name
            'New', // {{2}} - Message context
            $orderNumber, // {{3}} - Order number
            $orderType, // {{4}} - Order type
            $customerName, // {{5}} - Customer name
            $currency . number_format($totalAmount, 2), // {{6}} - Amount (with GST/taxes)
            $orderId ?? $order->id ?? null, // [7] Order ID for button URL
            $restaurantHash, // [8] Restaurant hash for button URL
        ];
    }

    /**
     * Get order total amount with GST/taxes included.
     * Always returns the final total amount that customer sees (includes taxes, charges, discounts).
     * Never returns subtotal - always shows the complete total amount.
     */
    protected function getOrderTotal($order): float
    {
        // If total exists and is greater than 0, use it (this includes all taxes, charges, discounts)
        if (isset($order->total) && $order->total > 0) {
            return (float) $order->total;
        }

        // If total is 0 or null, calculate it from components to get the complete total with GST
        $subTotal = (float) ($order->sub_total ?? 0);
        $taxAmount = (float) ($order->total_tax_amount ?? 0);
        $discountAmount = (float) ($order->discount_amount ?? 0);
        $deliveryFee = (float) ($order->delivery_fee ?? 0);
        $tipAmount = (float) ($order->tip_amount ?? 0);

        // Calculate complete total: subtotal + taxes - discount + delivery fee + tip
        // This gives us the final amount customer pays (with GST included)
        $calculatedTotal = $subTotal + $taxAmount - $discountAmount + $deliveryFee + $tipAmount;

        // Always return calculated total (even if 0) - never return just subtotal
        return max(0, $calculatedTotal);
    }
}
