<?php

namespace Modules\Whatsapp\Services;

use Illuminate\Support\Facades\URL;

/**
 * Maps old notification types to consolidated templates
 * and formats variables for consolidated template structure
 * 
 * IMPORTANT: Meta WhatsApp API rules:
 * - Header variables start from {{1}}
 * - Body variables start from {{1}} (separate scope)
 * - Footer cannot have variables (must be static text)
 */
class WhatsAppTemplateMapper
{
    /**
     * Get the base URL for the application
     */
    protected static function getBaseUrl(): string
    {
        return rtrim(config('app.url'), '/');
    }

    /**
     * Generate order view URL for button
     * Uses actual site base URL from config
     */
    protected static function getOrderUrl($orderId, $restaurantHash = null): string
    {
        $baseUrl = self::getBaseUrl();
        
        // If restaurant hash is provided, use restaurant-specific route for customers
        if ($restaurantHash) {
            // Check if Subdomain module is enabled
            $isSubdomainEnabled = function_exists('module_enabled') && module_enabled('Subdomain');
            
            if ($isSubdomainEnabled) {
                // For subdomain, use root URL with current_order parameter
                $url = "{$baseUrl}/?current_order={$orderId}";
            } else {
                // Use my-orders route with current_order query parameter to show specific order detail
                // This will take customers to their orders page and show the specific order
                $url = "{$baseUrl}/restaurant/my-orders/{$restaurantHash}?current_order={$orderId}";
            }
            
            
            return $url;
        }
        
        // Otherwise, use order ID route (for admin/internal use)
        $adminUrl = "{$baseUrl}/orders/{$orderId}";
        return $adminUrl;
    }

    /**
     * Generate reservation/bookings view URL for button
     * Uses actual site base URL from config
     */
    protected static function getReservationUrl($restaurantHash): string
    {
        $baseUrl = self::getBaseUrl();
        return "{$baseUrl}/restaurant/my-bookings/{$restaurantHash}";
    }
    /**
     * Map old notification type to consolidated template name
     */
    public static function getConsolidatedTemplateName(string $notificationType): string
    {
        $mapping = [
            // Order notifications → order_notifications
            'order_confirmation' => 'order_notifications',
            'order_status_update' => 'order_notifications',
            'order_cancelled' => 'order_notifications',
            'order_bill_invoice' => 'order_notifications',
            
            // Payment notifications → payment_notification
            'payment_confirmation' => 'payment_notification',
            'payment_reminder' => 'payment_notification',
            
            // Reservation notifications → reservation_notification
            'reservation_confirmation' => 'reservation_notification',
            'reservation_reminder' => 'reservation_notification',
            'reservation_status_update' => 'reservation_notification',
            'reservation_followup' => 'reservation_notification',
            
            // New order alert (already consolidated)
            'new_order_alert' => 'new_order_alert',
            
            // Delivery notifications → delivery_notification
            'delivery_assignment' => 'delivery_notification',
            'order_ready_for_pickup' => 'delivery_notification',
            'delivery_completion_confirmation' => 'delivery_notification',
            
            // Kitchen notifications → kitchen_notification
            'kitchen_notification' => 'kitchen_notification',
            'new_kot_notification' => 'kitchen_notification',
            'order_ready_to_serve' => 'kitchen_notification',
            'order_modification_alert' => 'kitchen_notification',
            
            // Staff notifications → staff_notification
            'payment_request_alert' => 'staff_notification',
            'table_assignment' => 'staff_notification',
            'table_status_change' => 'staff_notification',
            'waiter_request_acknowledgment' => 'staff_notification',
            'waiter_request' => 'staff_notification',
            'notify_waiter' => 'staff_notification',
            'order_cancellation_alert' => 'staff_notification',
            
            // Reports → sales_report
            'daily_sales_report' => 'sales_report',
            'weekly_sales_report' => 'sales_report',
            'monthly_sales_report' => 'sales_report',
            
            // Operations and inventory (already consolidated)
            'low_inventory_alert' => 'inventory_alert',
        ];

        return $mapping[$notificationType] ?? $notificationType;
    }

    /**
     * Format variables for consolidated order_notifications template
     * Returns: ['header' => [value], 'body' => [values...]]
     */
    public static function formatOrderNotification(string $notificationType, array $originalVariables): array
    {
        switch ($notificationType) {
            case 'order_confirmation':
                // Original order identifier may be like "Order #22" – we only want the numeric part for the template
                $rawOrderIdentifier = $originalVariables[1] ?? 'N/A';
                $orderNumber = $rawOrderIdentifier;
                if (is_string($rawOrderIdentifier) && preg_match('/(\d+)/', $rawOrderIdentifier, $matches)) {
                    $orderNumber = $matches[1];
                }
                // order_notifications template has a button with dynamic order ID
                // Use order ID (index 7) if available, otherwise extract from order number
                $orderId = $originalVariables[7] ?? null;
                if (empty($orderId) && is_numeric($orderNumber)) {
                    $orderId = (int) $orderNumber;
                }
                $restaurantHash = $originalVariables[8] ?? null; // Restaurant hash for URL (index 8)
                
                // Construct full URL with site base URL - ensure orderId is numeric and valid
                if (empty($orderId) || !is_numeric($orderId)) {
                    // If orderId is not valid, try to extract from order number
                    if (is_numeric($orderNumber)) {
                        $orderId = (int) $orderNumber;
                    } else {
                        \Illuminate\Support\Facades\Log::warning('WhatsApp Template Mapper: Invalid order ID for URL', [
                            'order_id' => $orderId,
                            'order_number' => $orderNumber,
                            'original_variables' => $originalVariables,
                        ]);
                        $orderId = null;
                    }
                }
                
                // Template button URL is: https://tabletrack.test/restaurant/my-orders/{{1}}
                // So we need to pass just the hash and query parameter part
                // Format: {hash}?current_order={orderId}
                $buttonUrlValue = null;
                if ($restaurantHash && $orderId) {
                    $buttonUrlValue = "{$restaurantHash}?current_order={$orderId}";
                } elseif ($restaurantHash) {
                    $buttonUrlValue = $restaurantHash;
                } elseif ($orderId) {
                    // Fallback if no hash: just pass order ID
                    $buttonUrlValue = (string) $orderId;
                }
                
                return [
                    'header' => [], // Static header "Order Alert"
                    'body' => [
                        $originalVariables[0] ?? __('whatsapp::app.defaultCustomer'), // Customer name
                        __('whatsapp::app.yourOrderHasBeenConfirmed'), // Message
                        $orderNumber, // Order number (numeric only, used with "#" in template text)
                        // Single-line details (no newlines inside variable to satisfy Meta API)
                        __('whatsapp::app.orderTypeLabel') . ': ' . ($originalVariables[2] ?? __('whatsapp::app.notAvailable')) . '. ' . __('whatsapp::app.totalAmountLabel') . ': ' . ($originalVariables[3] ?? __('whatsapp::app.notAvailable')), // Details block
                        __('whatsapp::app.estimatedTimeLabel') . ': ' . ($originalVariables[4] ?? __('whatsapp::app.notAvailable')), // Additional information block
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $buttonUrlValue ?? '', // Hash and query parameter to replace {{1}} in template
                                ],
                            ],
                        ],
                    ],
                ];

            case 'order_status_update':
                // Variables format: [customer_name, message, order_number, order_details, estimated_time, restaurant_name, contact_number, order_id, restaurant_hash]
                $orderNumber = $originalVariables[2] ?? 'N/A';
                // Extract numeric part from order number (e.g., "Order #25" -> "25")
                $orderNumberNumeric = $orderNumber;
                if (is_string($orderNumber) && preg_match('/(\d+)/', $orderNumber, $matches)) {
                    $orderNumberNumeric = $matches[1];
                }
                // order_notifications template has a button with dynamic order ID
                $orderId = $originalVariables[7] ?? $orderNumberNumeric; // Order ID for button URL
                $restaurantHash = $originalVariables[8] ?? null; // Restaurant hash for URL
                
                // Template button URL is: https://tabletrack.test/restaurant/my-orders/{{1}}
                // So we need to pass just the hash and query parameter part
                $buttonUrlValue = null;
                if ($restaurantHash && $orderId) {
                    $buttonUrlValue = "{$restaurantHash}?current_order={$orderId}";
                } elseif ($restaurantHash) {
                    $buttonUrlValue = $restaurantHash;
                } elseif ($orderId) {
                    $buttonUrlValue = (string) $orderId;
                }
                
                return [
                    'header' => [], // No header in order_notifications template
                    'body' => [
                        $originalVariables[0] ?? __('whatsapp::app.defaultCustomer'), // Customer name
                        $originalVariables[1] ?? 'order status updated', // Message (e.g., "order status updated to Order Confirmed")
                        $orderNumberNumeric, // Order number (numeric part only)
                        $originalVariables[3] ?? 'N/A', // Order details (type + amount)
                        __('whatsapp::app.estimatedTimeLabel') . ': ' . ($originalVariables[4] ?? __('whatsapp::app.notAvailable')), // Estimated time with label
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $buttonUrlValue ?? '', // Hash and query parameter to replace {{1}} in template
                                ],
                            ],
                        ],
                    ],
                ];

            case 'order_cancelled':
                $orderNumber = $originalVariables[1] ?? 'N/A';
                // Extract numeric part from order number
                $orderNumberNumeric = $orderNumber;
                if (is_string($orderNumber) && preg_match('/(\d+)/', $orderNumber, $matches)) {
                    $orderNumberNumeric = $matches[1];
                }
                $orderId = $originalVariables[4] ?? $orderNumberNumeric; // Order ID for button URL
                $restaurantHash = $originalVariables[5] ?? null; // Restaurant hash for URL
                
                // Template button URL is: https://tabletrack.test/restaurant/my-orders/{{1}}
                // So we need to pass just the hash and query parameter part
                $buttonUrlValue = null;
                if ($restaurantHash && $orderId) {
                    $buttonUrlValue = "{$restaurantHash}?current_order={$orderId}";
                } elseif ($restaurantHash) {
                    $buttonUrlValue = $restaurantHash;
                } elseif ($orderId) {
                    $buttonUrlValue = (string) $orderId;
                }
                
                // order_notifications template has a button with dynamic order ID
                return [
                    'header' => [], // Static header "Order Alert"
                    'body' => [
                        $originalVariables[0] ?? __('whatsapp::app.defaultCustomer'), // Customer name
                        __('whatsapp::app.orderHasBeenCancelled'), // Message
                        $orderNumberNumeric, // Order number
                        __('whatsapp::app.reasonLabel') . ': ' . ($originalVariables[2] ?? __('whatsapp::app.notAvailable')), // Details 1
                        __('whatsapp::app.refund') . ': ' . ($originalVariables[3] ?? __('whatsapp::app.pending')), // Details 2
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $buttonUrlValue ?? '', // Hash and query parameter to replace {{1}} in template
                                ],
                            ],
                        ],
                    ],
                ];

            case 'order_bill_invoice':
                $orderNumber = $originalVariables[1] ?? 'N/A';
                // Extract numeric part from order number
                $orderNumberNumeric = $orderNumber;
                if (is_string($orderNumber) && preg_match('/(\d+)/', $orderNumber, $matches)) {
                    $orderNumberNumeric = $matches[1];
                }
                $orderId = $originalVariables[4] ?? $orderNumberNumeric; // Order ID for button URL
                $restaurantHash = $originalVariables[5] ?? null; // Restaurant hash for URL
                
                // Template button URL is: https://tabletrack.test/restaurant/my-orders/{{1}}
                // So we need to pass just the hash and query parameter part
                $buttonUrlValue = null;
                if ($restaurantHash && $orderId) {
                    $buttonUrlValue = "{$restaurantHash}?current_order={$orderId}";
                } elseif ($restaurantHash) {
                    $buttonUrlValue = $restaurantHash;
                } elseif ($orderId) {
                    $buttonUrlValue = (string) $orderId;
                }
                
                // order_notifications template has a button with dynamic order ID
                return [
                    'header' => [], // Static header "Order Alert"
                    'body' => [
                        $originalVariables[0] ?? __('whatsapp::app.defaultCustomer'), // Customer name
                        __('whatsapp::app.yourBillIsReady'), // Message
                        $orderNumberNumeric, // Order number
                        __('whatsapp::app.amountLabel') . ': ' . ($originalVariables[2] ?? __('whatsapp::app.notAvailable')), // Details 1
                        __('whatsapp::app.paymentMethodLabel') . ': ' . ($originalVariables[3] ?? __('whatsapp::app.notAvailable')), // Details 2
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $buttonUrlValue ?? '', // Hash and query parameter to replace {{1}} in template
                                ],
                            ],
                        ],
                    ],
                ];

            default:
                return ['header' => [], 'body' => $originalVariables];
        }
    }

    /**
     * Format variables for consolidated payment_notification template
     */
    public static function formatPaymentNotification(string $notificationType, array $originalVariables): array
    {
        switch ($notificationType) {
            case 'payment_confirmation':
                // Variables format: [customer_name, amount, order_number, transaction_id, payment_method, date, order_type, order_id, restaurant_hash]
                $rawOrderIdentifier = $originalVariables[2] ?? 'N/A';
                // Extract numeric part from order number (e.g., "Order #9" -> "9")
                // Template already has # before {{3}}, so we only send the numeric part
                $orderNumber = $rawOrderIdentifier;
                if (is_string($rawOrderIdentifier) && preg_match('/(\d+)/', $rawOrderIdentifier, $matches)) {
                    $orderNumber = $matches[1]; // Just the number, no # prefix (template adds it)
                }
                $orderId = $originalVariables[7] ?? $orderNumber; // Order ID for button URL
                $restaurantHash = $originalVariables[8] ?? null; // Restaurant hash for URL
                
                // Construct full URL with site base URL
                $orderUrl = self::getOrderUrl($orderId, $restaurantHash);
                
                return [
                    'header' => [], // Static header "Payment Notification"
                    'body' => [
                        $originalVariables[0] ?? 'Customer', // {{1}} - Customer name
                        'Payment', // {{2}} - Message type
                        $orderNumber, // {{3}} - Order number (numeric only, template adds #)
                        $originalVariables[6] ?? 'N/A', // {{4}} - Order type
                        $originalVariables[0] ?? 'Customer', // {{5}} - Customer name
                        $originalVariables[1] ?? 'N/A', // {{6}} - Total amount
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $orderUrl, // Full URL with site base URL
                                ],
                            ],
                        ],
                    ],
                ];

            case 'payment_reminder':
                // Variables format: [customer_name, amount, order_number, due_date, payment_link, order_type, order_id, restaurant_hash]
                $rawOrderIdentifier = $originalVariables[2] ?? 'N/A';
                // Extract numeric part from order number (e.g., "Order #9" -> "9")
                $orderNumber = $rawOrderIdentifier;
                if (is_string($rawOrderIdentifier) && preg_match('/(\d+)/', $rawOrderIdentifier, $matches)) {
                    $orderNumber = $matches[1];
                }
                // For body, use the formatted order number with # prefix
                $formattedOrderNumber = $rawOrderIdentifier;
                if (is_string($rawOrderIdentifier) && preg_match('/(\d+)/', $rawOrderIdentifier, $matches)) {
                    $formattedOrderNumber = '#' . $matches[1];
                }
                $orderId = $originalVariables[6] ?? $orderNumber; // Order ID for button URL
                $restaurantHash = $originalVariables[7] ?? null; // Restaurant hash for URL
                
                // Construct full URL with site base URL
                $orderUrl = self::getOrderUrl($orderId, $restaurantHash);
                
                return [
                    'header' => [], // Static header "Payment Notification"
                    'body' => [
                        $originalVariables[0] ?? 'Customer', // {{1}} - Customer name
                        'Pending payment', // {{2}} - Message type
                        $formattedOrderNumber, // {{3}} - Order number (with #)
                        $originalVariables[5] ?? 'N/A', // {{4}} - Order type
                        $originalVariables[0] ?? 'Customer', // {{5}} - Customer name
                        $originalVariables[1] ?? 'N/A', // {{6}} - Total amount
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $orderUrl, // Full URL with site base URL
                                ],
                            ],
                        ],
                    ],
                ];

            default:
                return ['header' => [], 'body' => $originalVariables];
        }
    }

    /**
     * Format variables for consolidated reservation_notification template
     * 
     * Template structure:
     * Header: Status: {{1}}
     * Body: Hello {{1}}, We are pleased to confirm that {{2}} for {{3}} guests. 
     *       Your reservation has been scheduled for the date of {{4}} at {{5}}.
     *       Additional details: {{6}}.
     * 
     * Expected variables from listener:
     * [0] = Customer name
     * [1] = Date
     * [2] = Time
     * [3] = Party size
     * [4] = Table name
     * [5] = Restaurant name
     * [6] = Branch name
     * [7] = Contact number
     * [8] = Restaurant hash
     * [9] = Status (optional, for status update notifications)
     */
    public static function formatReservationNotification(string $notificationType, array $originalVariables): array
    {
        // Extract variables
        $customerName = $originalVariables[0] ?? 'Customer';
        $date = $originalVariables[1] ?? 'N/A';
        $time = $originalVariables[2] ?? 'N/A';
        $partySize = $originalVariables[3] ?? 'N/A';
        $tableName = $originalVariables[4] ?? __('whatsapp::app.notAssigned');
        $restaurantName = $originalVariables[5] ?? '';
        $branchName = $originalVariables[6] ?? '';
        $restaurantHash = $originalVariables[8] ?? null;
        $actualStatus = $originalVariables[9] ?? null; // Status from status update listener
        
        // Determine status and message type
        // If actual status is provided (from status update), use it; otherwise use notification type
        if ($actualStatus) {
            // Use actual status from reservation
            $status = match($actualStatus) {
                'Cancelled' => 'Reservation Cancelled',
                'Pending' => 'Reservation Pending',
                'Confirmed' => 'Reservation Confirmed',
                default => 'Reservation Updated',
            };
            
            $messageType = match($actualStatus) {
                'Cancelled' => 'your reservation status has been cancelled',
                'Pending' => 'your reservation status has been set to pending',
                'Confirmed' => 'your reservation status has been confirmed',
                default => 'your reservation status has been updated',
            };
        } else {
            // Use notification type to determine status
            $status = match($notificationType) {
                'reservation_confirmation' => 'Reservation Confirmed',
                'reservation_reminder' => 'Reservation Reminder',
                'reservation_status_update' => 'Reservation Updated',
                'reservation_followup' => 'Thank You',
                default => 'Reservation Confirmed',
            };
            
            $messageType = match($notificationType) {
                'reservation_confirmation' => 'your reservation is confirmed',
                'reservation_reminder' => 'your reservation is on',
                'reservation_status_update' => 'your reservation status has been updated',
                'reservation_followup' => 'thank you for visiting',
                default => 'your reservation is confirmed',
            };
        }
        
        // Build additional details - only include table if assigned, don't include status
        $additionalDetails = [];
        // Filter out status values and table assignment messages from table name
        $statusValues = ['cancelled', 'pending', 'confirmed', 'table assigned', 'table changed', 'not assigned'];
        if ($tableName !== __('whatsapp::app.notAssigned') && !in_array(strtolower(trim($tableName)), $statusValues)) {
            // Only add table if it's actually a table name, not a status or assignment message
            $additionalDetails[] = "Table: {$tableName}";
        }
        if ($restaurantName) {
            $additionalDetails[] = "Restaurant: {$restaurantName}";
        }
        if ($branchName) {
            $additionalDetails[] = "Branch: {$branchName}";
        }
        $additionalDetailsText = !empty($additionalDetails) ? implode(', ', $additionalDetails) : 'No additional details';
        
        return [
            'header' => [$status], // {{1}} in Header - Status
            'body' => [
                $customerName,        // {{1}} in Body - Customer name
                $messageType,         // {{2}} in Body - Message type (your reservation status has been confirmed/cancelled/pending)
                $partySize,            // {{3}} in Body - Party size
                $date,                 // {{4}} in Body - Date
                $time,                  // {{5}} in Body - Time
                $additionalDetailsText, // {{6}} in Body - Additional details
            ],
            'buttons' => $restaurantHash ? [
                [
                    'index' => 0,
                    'sub_type' => 'url',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => (string) $restaurantHash,
                        ],
                    ],
                ],
            ] : [],
        ];
    }

    /**
     * Format variables for consolidated new_order_alert template
     */
    public static function formatNewOrderAlert(string $recipientType, array $originalVariables): array
    {
        // Variables format: [recipient_name, message_context, order_number, order_type, customer_name, amount, order_id]
        $recipientName = $originalVariables[0] ?? 'User';
        $messageContext = $recipientType === 'customer' ? __('whatsapp::app.defaultYour') : __('whatsapp::app.defaultNew');
        $orderNumber = $originalVariables[2] ?? 'N/A';
        $orderType = $originalVariables[3] ?? 'N/A';
                $customerName = $recipientType === 'customer' ? __('whatsapp::app.defaultYou') : ($originalVariables[4] ?? __('whatsapp::app.defaultGuest'));
        $amount = $originalVariables[5] ?? 'N/A';
        $orderId = $originalVariables[6] ?? null;
        
        // Use order ID for URL if available, otherwise extract numeric part from order number
        $orderNumberForUrl = (string) ($orderId ?? '');
        if (empty($orderNumberForUrl) && preg_match('/(\d+)/', $orderNumber, $matches)) {
            $orderNumberForUrl = $matches[1];
        }
        
        // Get restaurant hash from variables if available (index 7)
        $restaurantHash = $originalVariables[7] ?? null;
        
        // Construct full URL with site base URL
        $orderUrl = self::getOrderUrl($orderNumberForUrl, $restaurantHash);
        
        return [
            'header' => [], // Static header "New Order"
            'body' => [
                $recipientName, // {{1}} - Recipient name
                $messageContext, // {{2}} - Message context (New/Your)
                $orderNumber, // {{3}} - Order number
                $orderType, // {{4}} - Order type
                $customerName, // {{5}} - Customer name
                $amount, // {{6}} - Amount
            ],
            'buttons' => [
                [
                    'index' => 0,
                    'sub_type' => 'url',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $orderUrl, // Full URL with site base URL
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Format variables for consolidated delivery_notification template
     * Template format:
     * Hello {{1}},
     * We are notifying you that {{2}} for order #{{3}}.
     * Customer name: {{4}},
     * Customer phone number {{5}},
     * Customer address and amount: {{6}}.
     */
    public static function formatDeliveryNotification(string $notificationType, array $originalVariables): array
    {
        // Extract numeric part from order number for {{3}}
        $rawOrderIdentifier = $originalVariables[1] ?? 'N/A';
        $orderNumber = $rawOrderIdentifier;
        if (is_string($rawOrderIdentifier) && preg_match('/(\d+)/', $rawOrderIdentifier, $matches)) {
            $orderNumber = $matches[1];
        }

        switch ($notificationType) {
            case 'delivery_assignment':
                // Variables format from listener: [executive_name, order_number, customer_name, customer_phone, pickup_address, delivery_address, amount, estimated_time]
                return [
                    'header' => [], // No header in new template
                    'body' => [
                        $originalVariables[0] ?? 'Delivery Executive', // {{1}} - Delivery executive name
                        'new delivery assigned', // {{2}} - Message
                        $orderNumber, // {{3}} - Order number (numeric part)
                        $originalVariables[2] ?? 'Customer', // {{4}} - Customer name
                        $originalVariables[3] ?? 'N/A', // {{5}} - Customer phone number
                        ($originalVariables[5] ?? 'N/A') . ', Amount: ' . ($originalVariables[6] ?? 'N/A'), // {{6}} - Address and amount
                    ],
                ];

            case 'order_ready_for_pickup':
                // Variables format from listener: [executive_name, order_number, pickup_address, customer_name, delivery_address, time, customer_phone, amount]
                return [
                    'header' => [], // No header in new template
                    'body' => [
                        $originalVariables[0] ?? 'Delivery Executive', // {{1}} - Delivery executive name
                        'order is ready for pickup', // {{2}} - Message
                        $orderNumber, // {{3}} - Order number (numeric part)
                        $originalVariables[3] ?? 'Customer', // {{4}} - Customer name
                        $originalVariables[6] ?? 'N/A', // {{5}} - Customer phone number
                        ($originalVariables[4] ?? 'N/A') . ', Amount: ' . ($originalVariables[7] ?? 'N/A'), // {{6}} - Delivery address and amount
                    ],
                ];

            case 'delivery_completion_confirmation':
                // Variables format from listener: [executive_name, order_number, customer_name, time, delivery_address_and_amount, payment_status]
                return [
                    'header' => [], // No header in new template
                    'body' => [
                        $originalVariables[0] ?? 'Delivery Executive', // {{1}} - Delivery executive name
                        'delivery completed successfully', // {{2}} - Message
                        $orderNumber, // {{3}} - Order number (numeric part)
                        $originalVariables[2] ?? 'Customer', // {{4}} - Customer name
                        'N/A', // {{5}} - Customer phone (not provided in original variables)
                        ($originalVariables[4] ?? 'N/A') . ', Payment: ' . ($originalVariables[5] ?? 'N/A'), // {{6}} - Address, amount and payment status
                    ],
                ];

            default:
                return ['header' => [], 'body' => $originalVariables];
        }
    }

    /**
     * Format variables for consolidated kitchen_notification template
     * Template format:
     * Hello {{1}},
     * We have received {{2}} with order number {{3}}.
     * Table number: {{4}}.
     * Order type: {{5}}.
     * List of items that need to be prepared: {{6}}.
     * Please prepare all items accordingly and ensure timely service.
     * We appreciate your hard work.
     */
    public static function formatKitchenNotification(string $notificationType, array $originalVariables): array
    {
        $rawOrderIdentifier = $originalVariables[1] ?? 'N/A';
        $orderNumberForUrl = $rawOrderIdentifier;
        if (is_string($rawOrderIdentifier) && preg_match('/(\d+)/', $rawOrderIdentifier, $matches)) {
            $orderNumberForUrl = $matches[1];
        }

        switch ($notificationType) {
            case 'new_kot_notification':
            case 'kitchen_notification':
                return [
                    'body' => [
                        'Chef',
                        'New KOT',
                        $originalVariables[1] ?? 'N/A',
                        $originalVariables[2] ?? 'N/A',
                        $originalVariables[3] ?? 'N/A',
                        $originalVariables[4] ?? 'N/A',
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => (string) $orderNumberForUrl,
                                ],
                            ],
                        ],
                    ],
                ];

            case 'order_ready_to_serve':
                return [
                    'body' => [
                        $originalVariables[0] ?? 'Waiter',
                        'Order ready to serve',
                        $originalVariables[1] ?? 'N/A',
                        $originalVariables[2] ?? 'N/A',
                        $originalVariables[6] ?? 'N/A',
                        $originalVariables[3] ?? 'N/A',
                    ],
                    'buttons' => [
                        [
                            'index' => 0,
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => (string) $orderNumberForUrl,
                                ],
                            ],
                        ],
                    ],
                ];

            default:
                return ['header' => [], 'body' => $originalVariables];
        }
    }

    /**
     * Format variables for consolidated staff_notification template
     * 
     * Template Structure (Shortened):
     * Header: "Status: {{1}}" (notification type)
     * Body: "Hello {{1}}, we are sending you this notification regarding {{2}} for {{3}}. Here is the important detail: {{4}}. Please take necessary action. Thank you!"
     * 
     * Variables:
     * - {{1}} in Header: Notification type/status
     * - {{1}} in Body: Staff name
     * - {{2}} in Body: Notification type
     * - {{3}} in Body: Target (table number/reservation number)
     * - {{4}} in Body: Details (single detail)
     */
    public static function formatStaffNotification(string $notificationType, array $originalVariables): array
    {
        // Variables expected: [header_status, staff_name, notification_type, target, details]
        // We only use first 4 variables (removed variables 5 and 6)
        return [
            'header' => [$originalVariables[0] ?? 'Notification'], // {{1}} in Header - Status/Type
            'body' => [
                $originalVariables[1] ?? 'Staff', // {{1}} in Body - Staff name
                $originalVariables[2] ?? 'Notification', // {{2}} in Body - Notification type
                $originalVariables[3] ?? 'N/A', // {{3}} in Body - Target (table/reservation)
                $originalVariables[4] ?? 'N/A', // {{4}} in Body - Details (single detail)
                // Variables 5 and 6 removed as per user request
            ],
        ];
    }

    /**
     * Format variables for consolidated sales_report template
     * 
     * New Template Structure:
     * Header: "Sales Report" (no variables)
     * Body:
     * Here is your comprehensive {{1}} Sales Report for the reporting period of {{2}}.
     * Total number of orders processed: {{3}}.
     * Total revenue generated: {{4}}.
     * Net revenue after all deductions: {{5}}.
     * Combined tax and discount details are as follows: {{6}}.
     * Click on the 'View Report' button below for the full report.
     */
    public static function formatSalesReport(string $notificationType, array $originalVariables): array
    {
        $periodType = match($notificationType) {
            'daily_sales_report' => 'Daily',
            'weekly_sales_report' => 'Weekly',
            'monthly_sales_report' => 'Monthly',
            default => 'Daily',
        };

        // Note: If the Meta template button URL is static (no {{1}} placeholder),
        // we should NOT send button parameters. The button will use the static URL from Meta.
        // If the template has a dynamic URL like "https://yourdomain.com/reports/{{1}}",
        // then we would send the parameter value.
        // For now, assuming static URL, so no button parameters needed.
        
        return [
            'header' => [], // Document media will be added by WhatsAppService (not a variable)
            'body' => [
                $periodType, // {{1}} - Period type (Daily/Weekly/Monthly)
                $originalVariables[0] ?? __('whatsapp::app.notAvailable'), // {{2}} - Reporting period (date/range/month)
                $originalVariables[1] ?? '0', // {{3}} - Total orders processed
                $originalVariables[2] ?? 'N/A', // {{4}} - Total revenue generated
                $originalVariables[3] ?? 'N/A', // {{5}} - Net revenue after deductions
                __('whatsapp::app.taxLabel') . ': ' . ($originalVariables[4] ?? __('whatsapp::app.notAvailable')) . ', ' . __('whatsapp::app.discountLabel') . ': ' . ($originalVariables[5] ?? __('whatsapp::app.notAvailable')), // {{6}} - Combined tax and discount
            ],
            // Button is static in Meta template, so no parameters needed
            'buttons' => [],
        ];
    }

    /**
     * Format variables for operations_summary template
     * Template format:
     * Here is the daily summary for branch {{1}} for the date of {{2}}.
     * Total number of orders processed today: {{3}}
     * Total revenue generated: {{4}}
     * Total number of reservations: {{5}}
     * Combined staff on duty: {{6}}.
     * The end-of-day summary has been completed successfully!
     */
    public static function formatOperationsSummary(array $originalVariables): array
    {
        return [
            'header' => [], // No header in new template
            'body' => [
                $originalVariables[0] ?? __('whatsapp::app.notAvailable'), // {{1}} - Branch name
                $originalVariables[1] ?? 'N/A', // {{2}} - Date
                $originalVariables[2] ?? '0', // {{3}} - Total orders
                $originalVariables[3] ?? 'N/A', // {{4}} - Total revenue
                $originalVariables[4] ?? '0', // {{5}} - Total reservations
                $originalVariables[5] ?? '0', // {{6}} - Combined staff on duty
            ],
            // Button is static "View Report" with sales report URL - no variables needed
        ];
    }

    /**
     * Format variables for inventory_alert template
     * 
     * Template structure:
     * Body: Low stock alert! {{1}} items are below threshold. Items: {{2}}. Restaurant: {{3}}
     * 
     * Expected variables from command/listener:
     * [0] = Item count
     * [1] = Item names
     * [2] = Branch name
     *
     * Older callers may still prepend recipient name. In that case, ignore it and
     * normalize to the 3 parameters expected by the approved template.
     */
    public static function formatInventoryAlert(array $originalVariables): array
    {
        $hasRecipientName = count($originalVariables) >= 4
            && isset($originalVariables[0])
            && is_string($originalVariables[0])
            && !is_numeric($originalVariables[0]);

        $offset = $hasRecipientName ? 1 : 0;

        return [
            'header' => [],
            'body' => [
                $originalVariables[$offset] ?? '0',
                $originalVariables[$offset + 1] ?? __('whatsapp::app.noItems'),
                $originalVariables[$offset + 2] ?? __('whatsapp::app.notAvailable'),
            ],
        ];
    }
}
