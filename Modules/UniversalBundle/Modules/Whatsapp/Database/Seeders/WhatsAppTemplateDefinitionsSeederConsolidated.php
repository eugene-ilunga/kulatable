<?php

namespace Modules\Whatsapp\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Whatsapp\Entities\WhatsAppTemplateDefinition;

/**
 * Consolidated WhatsApp Template Definitions
 * Reduced from 29 templates to 10 consolidated templates using dynamic variables
 */
class WhatsAppTemplateDefinitionsSeederConsolidated extends Seeder
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
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => '{{1}}'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{2}}, {{3}} for order #{{4}}. {{5}}. {{6}}. {{7}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => '{{8}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Header text (Order Confirmed/Order Update/Order Cancelled/Your Bill)',
                    'Customer name',
                    'Main message (e.g., "your order has been confirmed", "order status updated to", "order has been cancelled", "your bill is ready")',
                    'Order number',
                    'Details line 1 (Order type/Status/Reason/Amount)',
                    'Details line 2 (Estimated time/Additional info/Refund status/Payment method)',
                    'Details line 3 (Restaurant name/Contact info)',
                    'Footer (Contact info/Thank you message)'
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
                            'text' => '{{1}}'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{2}}, {{3}} for order #{{4}}. Amount: {{5}}. {{6}}. {{7}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => '{{8}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Header text (Payment Confirmed/Payment Reminder)',
                    'Customer name',
                    'Message type (payment confirmed/pending payment)',
                    'Order number',
                    'Amount',
                    'Transaction details or Due date',
                    'Payment link or Transaction ID',
                    'Contact info'
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
                            'text' => '{{1}}'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{2}}, {{3}} for {{4}} guests. Date: {{5}}, Time: {{6}}. {{7}}. {{8}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => '{{9}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Header text (Reservation Confirmed/Reservation Cancelled/Reservation Pending/Thank You)',
                    'Customer name',
                    'Message type (your reservation is confirmed/your reservation status has been confirmed/cancelled/set to pending/thank you for visiting)',
                    'Number of guests',
                    'Date',
                    'Time',
                    'Additional details (Table number/Status/Time until reservation/Feedback link)',
                    'Additional info (Restaurant name/Contact)',
                    'Footer (Contact info/Restaurant name)'
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
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'New Order'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, {{2}} order #{{3}} received! Type: {{4}}, Customer: {{5}}, Amount: {{6}}, Time: {{7}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => '{{8}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Recipient name (Admin name/Customer name/Staff name)',
                    'Message context (New/Your)',
                    'Order number',
                    'Order type',
                    'Customer name (or "You" for customer)',
                    'Amount',
                    'Order time',
                    'Additional info (Table number/Contact)'
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
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => '{{1}}'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{2}}, {{3}} for order #{{4}}. Customer: {{5}}, Phone: {{6}}, {{7}} Address: {{8}}, Amount: {{9}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => '{{10}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Header text (New Delivery/Ready for Pickup/Delivery Completed)',
                    'Recipient name (Delivery executive/Customer)',
                    'Message type (new delivery assigned/order is ready for pickup/delivery completed successfully)',
                    'Order number',
                    'Customer name',
                    'Customer phone',
                    'Location type (Pickup/Delivery)',
                    'Address',
                    'Amount',
                    'Additional info (ETA/Payment status/Next delivery/Contact)'
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
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => '{{1}}'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{2}}, {{3}} #{{4}} for order #{{5}}. Table: {{6}}, Type: {{7}}, Items: {{8}}, Time: {{9}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => '{{10}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Header text (New KOT/Order Ready/Order Modified)',
                    'Staff name (Chef/Waiter)',
                    'Notification type (New KOT/Order ready to serve/Order has been modified)',
                    'KOT number or Order number',
                    'Order number',
                    'Table number',
                    'Order type',
                    'Items list',
                    'Time',
                    'Additional info (Priority/Notes/Change description)'
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
                            'text' => "Hello {{1}}, \n\nWe are sending you this notification regarding {{2}} for {{3}}. \n\nHere is the important detail: \n\n{{4}}.\n\n Please take necessary action. Thank you!"
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
                    'Branch name',
                    'Date',
                    'Total orders',
                    'Total revenue',
                    'Total reservations',
                    'Staff on duty and Peak hours (combined)'
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
                            'text' => 'Low stock alert! {{1}} items are below threshold. Items: {{2}}. Restaurant: {{3}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Please restock soon'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Item count',
                    'Item names',
                    'Restaurant name'
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
