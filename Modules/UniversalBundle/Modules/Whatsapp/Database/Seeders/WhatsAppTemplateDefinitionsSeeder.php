<?php

namespace Modules\Whatsapp\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Whatsapp\Entities\WhatsAppTemplateDefinition;

/**
 * Consolidated WhatsApp Template Definitions
 * Reduced from 29 templates to 10 consolidated templates using dynamic variables
 */
class WhatsAppTemplateDefinitionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // 1. Order Notification (Consolidates: order_confirmation, order_status_update, order_cancelled, order_bill_invoice)
            [
                'notification_type' => 'order_notifications',
                'template_name' => 'Order Notification',
                'category' => 'customer',
                'description' => 'Unified template for all order-related notifications (confirmed, status update)',
                'template_json' => json_encode([
                    'name' => 'order_notifications',
                    'language' => 'en',
                    'category' => 'MARKETING',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Order Alert'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => "Hello {{1}}, \n\nWe would like to inform you that {{2}}\n\nOrder number #{{3}}.\n\nOrder details:\n\n{{4}}\n\nAdditional information:\n\n{{5}}.\n\nThank you for your order."
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Thank you for choosing us!'
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Order',
                                    'url' => 'https://example.com/order/{{1}}',
                                    'example' => ['https://example.com/order/12345']
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Customer name',
                    'Body 2: Main message (e.g., "your order has been confirmed", "order status updated to", "order has been cancelled", "your bill is ready")',
                    'Body 3: Order number',
                    'Body 4: Details line 1 (Order type/Status/Reason/Amount)',
                    'Body 5: Details line 2 (Estimated time/Additional info/Refund status/Payment method)'
                ],
                'is_active' => true,
            ],

            // 2. Payment Notification (Consolidates: payment_confirmation, payment_reminder)
            [
                'notification_type' => 'payment_notification',
                'template_name' => 'Payment Notification',
                'category' => 'customer',
                'description' => 'Unified template for payment confirmation and payment reminders',
                'template_json' => json_encode([
                    'name' => 'payment_notification',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Payment Notification'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => "Hello {{1}},\n\n{{2}} for order: #{{3}} has been successfully received.\n\nOrder type: {{4}},\n\nCustomer name: {{5}},\n\nTotal amount: {{6}}.\n\nThank you for choosing our services!"
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Thank you!'
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Order',
                                    'url' => 'https://yourdomain.com/order/{{1}}',
                                    'example' => ['https://yourdomain.com/order/123']
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Customer name',
                    'Body 2: Message type (Payment/Pending payment)',
                    'Body 3: Order number',
                    'Body 4: Order type',
                    'Body 5: Customer name',
                    'Body 6: Total amount',
                    'Button URL: Order number (for View Order button)'
                ],
                'is_active' => true,
            ],

            // 3. Reservation Notification (Consolidates: reservation_confirmation, reservation_status_update, reservation_followup)
            [
                'notification_type' => 'reservation_notification',
                'template_name' => 'Reservation Notification',
                'category' => 'customer',
                'description' => 'Unified template for reservation confirmation, status updates (Confirmed, Cancelled, Pending), and followup messages',
                'template_json' => json_encode([
                    'name' => 'reservation_notification',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Status: {{1}}'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, we are pleased to confirm that {{2}} for a party of {{3}} guests. Your reservation has been scheduled for the date of {{4}} at the time of {{5}}. Here are some additional important details regarding your reservation: {{6}}. We are excited to welcome you and look forward to providing you with an excellent dining experience!'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'We look forward to serving you!'
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Booking',
                                    'url' => rtrim(config('app.url'), '/') . '/restaurant/my-bookings/',
                                    'example' => [rtrim(config('app.url'), '/') . '/restaurant/my-bookings/demo-restaurant']
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Header: Header text (Reservation Confirmed/Reservation Cancelled/Reservation Pending/Thank You)',
                    'Body 1: Customer name',
                    'Body 2: Message type (your reservation is confirmed/your reservation status has been confirmed/cancelled/set to pending/thank you for visiting)',
                    'Body 3: Number of guests',
                    'Body 4: Date',
                    'Body 5: Time',
                    'Body 6: Additional details (Table number/Status/Time until reservation/Feedback link/Restaurant name)',
                    'Button URL: Restaurant hash/slug (for View Booking button)'
                ],
                'is_active' => true,
            ],

            // 4. New Order Alert (Consolidates: new_order_alert for admin/staff)
            [
                'notification_type' => 'new_order_alert',
                'template_name' => 'New Order Alert',
                'category' => 'all',
                'description' => 'Unified template for new order alerts to admin',
                'template_json' => json_encode([
                    'name' => 'new_order_alert',
                    'language' => 'en',
                    'category' => 'MARKETING',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'New Order'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => "Hello {{1}},\n\n{{2}} order with order number {{3}} has been successfully received.\n\nThe order type: {{4}}.\n\nCustomer name: {{5}}.\n\nAmount for this order is {{6}}.\n\nThank you for choosing our services!"
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Thank you!'
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Order',
                                    'url' => 'https://yourdomain.com/order/{{1}}',
                                    'example' => ['https://yourdomain.com/order/123']
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Recipient name (Admin name/Customer name/Staff name)',
                    'Body 2: Message context (New/Your)',
                    'Body 3: Order number',
                    'Body 4: Order type',
                    'Body 5: Customer name (or "You" for customer)',
                    'Body 6: Amount',
                    'Button URL: Order number (for View Order button)'
                ],
                'is_active' => true,
            ],

            // 5. Delivery Notification (Consolidates: delivery_assignment, order_ready_for_pickup, delivery_completion_confirmation)
            [
                'notification_type' => 'delivery_notification',
                'template_name' => 'Delivery Notification',
                'category' => 'delivery',
                'description' => 'Unified template for delivery-related notifications (assignment, ready for pickup, completion)',
                'template_json' => json_encode([
                    'name' => 'delivery_notification',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'BODY',
                            'text' => "Hello {{1}},\n\nWe are notifying you that {{2}} for order #{{3}}.\n\nCustomer name: {{4}},\n\nCustomer phone number {{5}},\n\nCustomer address and amount: {{6}}.\n\nPlease proceed with the delivery process as soon as possible.\n\nThank you for your dedication!"
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Delivery executive name',
                    'Body 2: Message (e.g., "order is ready for pickup", "new delivery assigned")',
                    'Body 3: Order number (numeric part, used with # in template)',
                    'Body 4: Customer name',
                    'Body 5: Customer phone number',
                    'Body 6: Customer address and amount (combined)'
                ],
                'is_active' => true,
            ],

            // 6. Kitchen Notification (Consolidates: new_kot_notification, order_ready_to_serve, order_modification_alert)
            [
                'notification_type' => 'kitchen_notification',
                'template_name' => 'Kitchen Notification',
                'category' => 'staff',
                'description' => 'Unified template for kitchen-related notifications (new KOT items to prepare).',
                'template_json' => json_encode([
                    'name' => 'kitchen_notification',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'BODY',
                            'text' => "Hello {{1}},\n\n\n\nWe have received {{2}} with order number {{3}}.\n\nTable number: {{4}}.\n\nOrder type: {{5}}.\n\nList of items that need to be prepared: {{6}}.\n\nPlease prepare all items accordingly and ensure timely service.\n\nWe appreciate your hard work."
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Order',
                                    'url' => 'https://yourdomain.com/order/{{1}}',
                                    'example' => ['https://yourdomain.com/order/123']
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Staff name (Chef/Waiter)',
                    'Body 2: Notification type (New KOT/Order ready to serve/Order has been modified)',
                    'Body 3: Order number',
                    'Body 4: Table number',
                    'Body 5: Order type',
                    'Body 6: List of items that need to be prepared',
                    'Button URL: Order number (numeric part, for View Order button)'
                ],
                'is_active' => true,
            ],

            // 7. Staff Notification (Consolidates: payment_request_alert, table_assignment, table_status_change, waiter_request_acknowledgment, notify_waiter)
            [
                'notification_type' => 'staff_notification',
                'template_name' => 'Staff Notification',
                'category' => 'staff',
                'description' => 'Unified template for staff-related notifications (table assignment, waiter request)',
                'template_json' => json_encode([
                    'name' => 'staff_notification',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Status: {{1}}'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => "Hello {{1}},\n\nWe are sending you this notification regarding {{2}} for {{3}}.\n\nHere is the important detail: \n{{4}}.\n\n Please take necessary action. Thank you!"
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Thank you!'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Header: Header text (Payment Request/Table Assigned/Table Status/Waiter Request)',
                    'Body 1: Staff name',
                    'Body 2: Notification type (payment requested/table assigned/table status changed/waiter request received)',
                    'Body 3: Target (table number/reservation number)',
                    'Body 4: Details (single detail)'
                ],
                'is_active' => true,
            ],

            // 8. Sales Report (Consolidates: daily_sales_report, weekly_sales_report, monthly_sales_report)
            [
                'notification_type' => 'sales_report',
                'template_name' => 'Sales Report',
                'category' => 'staff',
                'description' => 'Unified template for all sales reports (daily, weekly, monthly)',
                'template_json' => json_encode([
                    'name' => 'sales_report',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'BODY',
                            'text' => 'Here is your comprehensive {{1}} Sales Report for the reporting period of {{2}}. The total number of orders processed during this period is {{3}}, the total revenue generated is {{4}}, the net revenue after all deductions is {{5}}, and here are the combined tax and discount details: {{6}}. This report has been generated successfully and is ready for your review and analysis!'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Generated automatically'
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Report',
                                    'url' => rtrim(config('app.url'), '/') . '/reports/sales-report',
                                    'example' => [rtrim(config('app.url'), '/') . '/reports/sales-report']
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Period type (Daily/Weekly/Monthly)',
                    'Body 2: Period (Date/Date Range/Month)',
                    'Body 3: Total orders',
                    'Body 4: Total revenue',
                    'Body 5: Net revenue',
                    'Body 6: Tax and Discount (combined)',
                    'Button URL: Sales report URL (static, no variables)'
                ],
                'is_active' => true,
            ],

            // 9. Operations Summary
            [
                'notification_type' => 'operations_summary',
                'template_name' => 'Daily Operations Summary',
                'category' => 'staff',
                'description' => 'End-of-day operations summary for admin',
                'template_json' => json_encode([
                    'name' => 'operations_summary',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'DOCUMENT'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Here is the daily operations summary for branch {{1}} on the date of {{2}}. The total number of orders processed today is {{3}}, the total revenue generated for today is {{4}}, the total number of reservations handled today is {{5}}, and here are the combined staff on duty and peak hours information: {{6}}. The end of day summary has been completed successfully and is ready for review!'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'End of day summary'
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'URL',
                                    'text' => 'View Report',
                                    'url' => 'https://yourdomain.com/reports/sales-report',
                                    'example' => ['https://yourdomain.com/reports/sales-report']
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Branch name',
                    'Body 2: Date',
                    'Body 3: Total orders',
                    'Body 4: Total revenue',
                    'Body 5: Total reservations',
                    'Body 6: Combined staff on duty and peak hours',
                    'Button URL: Sales report URL (static, no variables)'
                ],
                'is_active' => true,
            ],

            // 10. Inventory Alert
            [
                'notification_type' => 'inventory_alert',
                'template_name' => 'Low Stock Alert',
                'category' => 'staff',
                'description' => 'Alert when inventory items are below threshold',
                'template_json' => json_encode([
                    'name' => 'inventory_alert',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Low Stock Alert'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'We are sending you this important low stock alert notification. There are currently {{1}} items that have fallen below the minimum threshold level. Here is the complete list of items that need immediate attention: {{2}}. This alert is for restaurant location: {{3}}. Please take immediate action to restock these items as soon as possible to avoid any service disruptions. Thank you for your prompt attention to this matter!'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Please restock soon'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Body 1: Item count',
                    'Body 2: Item names',
                    'Body 3: Restaurant name'
                ],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            WhatsAppTemplateDefinition::updateOrCreate(
                ['notification_type' => $template['notification_type']],
                $template
            );
        }

        if ($this->command) {
            $this->command->info('Consolidated WhatsApp template definitions seeded successfully! (10 templates instead of 29)');
        }
    }
}
