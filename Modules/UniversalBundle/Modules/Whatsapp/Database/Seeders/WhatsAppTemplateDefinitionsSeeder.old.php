<?php

namespace Modules\Whatsapp\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Whatsapp\Entities\WhatsAppTemplateDefinition;

class WhatsAppTemplateDefinitionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Customer Notifications (11)
            [
                'notification_type' => 'order_confirmation',
                'template_name' => 'Order Confirmation',
                'category' => 'customer',
                'description' => 'New order confirmation when customer places an order',
                'template_json' => json_encode([
                    'name' => 'order_confirmation',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Order Confirmed'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your order #{{2}} has been confirmed! Order type: {{3}}. Total amount: {{4}}. Estimated time: {{5}}. Thank you for choosing {{6}}!'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'For queries, contact {{7}}'
                        ],
                        [
                            'type' => 'BUTTONS',
                            'buttons' => [
                                [
                                    'type' => 'QUICK_REPLY',
                                    'text' => 'Track Order'
                                ],
                                [
                                    'type' => 'QUICK_REPLY',
                                    'text' => 'Contact Support'
                                ]
                            ]
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Order number', 'Order type', 'Total amount', 'Estimated time', 'Restaurant name', 'Restaurant contact'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'order_status_update',
                'template_name' => 'Order Status Update',
                'category' => 'customer',
                'description' => 'Single template for all order status changes (confirmed/preparing/ready/delivered)',
                'template_json' => json_encode([
                    'name' => 'order_status_update',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Order Update'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your order #{{2}} status: {{3}}. {{4}}. {{5}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Restaurant: {{6}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Order number', 'Status', 'Status message', 'Estimated time', 'Restaurant name'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'order_cancelled',
                'template_name' => 'Order Cancelled',
                'category' => 'customer',
                'description' => 'Order cancellation notification',
                'template_json' => json_encode([
                    'name' => 'order_cancelled',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Order Cancelled'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your order #{{2}} has been cancelled. Reason: {{3}}. {{4}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Contact: {{5}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Order number', 'Cancellation reason', 'Refund status', 'Restaurant contact'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'order_bill_invoice',
                'template_name' => 'Order Bill Invoice',
                'category' => 'customer',
                'description' => 'Send bill/invoice after order completion',
                'template_json' => json_encode([
                    'name' => 'order_bill_invoice',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Your Bill'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your bill for order #{{2}} is ready. Amount: {{3}}. Payment method: {{4}}. {{5}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Thank you!'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Order number', 'Bill amount', 'Payment method', 'Bill link'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'payment_confirmation',
                'template_name' => 'Payment Confirmation',
                'category' => 'customer',
                'description' => 'Payment confirmation after successful payment',
                'template_json' => json_encode([
                    'name' => 'payment_confirmation',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Payment Confirmed'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, payment of {{2}} for order #{{3}} has been confirmed. Transaction ID: {{4}}. Payment method: {{5}}. Date: {{6}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Thank you!'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Payment amount', 'Order number', 'Transaction ID', 'Payment method', 'Payment date'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'payment_reminder',
                'template_name' => 'Payment Reminder',
                'category' => 'customer',
                'description' => 'Reminder for pending/due payments',
                'template_json' => json_encode([
                    'name' => 'payment_reminder',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Payment Reminder'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, you have a pending payment of {{2}} for order #{{3}}. Due date: {{4}}. {{5}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Contact: {{6}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Due amount', 'Order number', 'Due date', 'Payment link', 'Restaurant contact'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'reservation_confirmation',
                'template_name' => 'Reservation Confirmation',
                'category' => 'customer',
                'description' => 'Reservation confirmation when table is booked',
                'template_json' => json_encode([
                    'name' => 'reservation_confirmation',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Reservation Confirmed'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your reservation is confirmed! Date: {{2}}, Time: {{3}}, Guests: {{4}}, Table: {{5}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Restaurant: {{6}} | Contact: {{7}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Reservation date', 'Reservation time', 'Number of guests', 'Table number', 'Restaurant name', 'Restaurant contact'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'reservation_reminder',
                'template_name' => 'Reservation Reminder',
                'category' => 'customer',
                'description' => 'Reminder before reservation time',
                'template_json' => json_encode([
                    'name' => 'reservation_reminder',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Reservation Reminder'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, reminder: Your reservation is on {{2}} at {{3}} for {{4}} guests. {{5}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Restaurant: {{6}} | Contact: {{7}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Reservation date', 'Reservation time', 'Number of guests', 'Time until reservation', 'Restaurant name', 'Restaurant contact'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'reservation_status_update',
                'template_name' => 'Reservation Status Update',
                'category' => 'customer',
                'description' => 'Single template for reservation status changes',
                'template_json' => json_encode([
                    'name' => 'reservation_status_update',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Reservation Update'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your reservation #{{2}} status: {{3}}. {{4}}'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Contact: {{5}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Reservation ID', 'Status', 'Status message', 'Restaurant contact'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'waiter_request_acknowledgment',
                'template_name' => 'Waiter Request Acknowledgment',
                'category' => 'customer',
                'description' => 'Acknowledge waiter request from customer',
                'template_json' => json_encode([
                    'name' => 'waiter_request_acknowledgment',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Request Received'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your waiter request for table {{2}} has been received. Estimated wait time: {{3}} minutes.'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Restaurant: {{4}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Table number', 'Estimated wait time', 'Restaurant name'
                ],
                'is_active' => true,
            ],
            [
                'notification_type' => 'table_ready_notification',
                'template_name' => 'Table Ready Notification',
                'category' => 'customer',
                'description' => 'Notify when reserved table becomes available',
                'template_json' => json_encode([
                    'name' => 'table_ready_notification',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        [
                            'type' => 'HEADER',
                            'format' => 'TEXT',
                            'text' => 'Table Ready'
                        ],
                        [
                            'type' => 'BODY',
                            'text' => 'Hello {{1}}, your table {{2}} is ready! Reservation time: {{3}}. Please proceed to the restaurant.'
                        ],
                        [
                            'type' => 'FOOTER',
                            'text' => 'Restaurant: {{4}} | Contact: {{5}}'
                        ]
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => [
                    'Customer name', 'Table number', 'Reservation time', 'Restaurant name', 'Restaurant contact'
                ],
                'is_active' => true,
            ],
            // Admin Notifications (5)
            [
                'notification_type' => 'new_order_alert',
                'template_name' => 'New Order Alert',
                'category' => 'admin',
                'description' => 'Alert admin when new order is placed',
                'template_json' => json_encode([
                    'name' => 'new_order_alert',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'New Order'],
                        ['type' => 'BODY', 'text' => 'New order received! Order #{{1}}, Type: {{2}}, Customer: {{3}}, Amount: {{4}}, Time: {{5}}'],
                        ['type' => 'FOOTER', 'text' => 'Branch: {{6}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Order number', 'Order type', 'Customer name', 'Total amount', 'Order time', 'Branch name'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'sales_report',
                'template_name' => 'Sales Report',
                'category' => 'admin',
                'description' => 'Daily/weekly/monthly sales reports',
                'template_json' => json_encode([
                    'name' => 'sales_report',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Sales Report'],
                        ['type' => 'BODY', 'text' => '{{1}} Sales Report for {{2}}. Total Sales: {{3}}, Total Orders: {{4}}, Average Order: {{5}}'],
                        ['type' => 'FOOTER', 'text' => 'Restaurant: {{6}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Report period', 'Date/Date range', 'Total sales', 'Total orders', 'Average order value', 'Restaurant name'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'low_inventory_alert',
                'template_name' => 'Low Inventory Alert',
                'category' => 'admin',
                'description' => 'Alert when inventory items are running low',
                'template_json' => json_encode([
                    'name' => 'low_inventory_alert',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Low Stock Alert'],
                        ['type' => 'BODY', 'text' => 'Low inventory alert! Item: {{1}}, Current stock: {{2}}, Minimum: {{3}}, Branch: {{4}}'],
                        ['type' => 'FOOTER', 'text' => 'Please restock soon']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Item name', 'Current stock', 'Minimum threshold', 'Branch name'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'subscription_expiry_reminder',
                'template_name' => 'Subscription Expiry Reminder',
                'category' => 'admin',
                'description' => 'Reminder before subscription expires',
                'template_json' => json_encode([
                    'name' => 'subscription_expiry_reminder',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Subscription Reminder'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, your subscription for plan {{2}} expires on {{3}}. Days remaining: {{4}}. {{5}}'],
                        ['type' => 'FOOTER', 'text' => 'Contact support: {{6}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Restaurant name', 'Current plan', 'Expiry date', 'Days remaining', 'Renewal link', 'Support contact'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'new_restaurant_signup',
                'template_name' => 'New Restaurant Signup',
                'category' => 'admin',
                'description' => 'Notify superadmin when new restaurant registers',
                'template_json' => json_encode([
                    'name' => 'new_restaurant_signup',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'New Signup'],
                        ['type' => 'BODY', 'text' => 'New restaurant signup! Name: {{1}}, Owner: {{2}}, Email: {{3}}, Phone: {{4}}, Plan: {{5}}, Date: {{6}}'],
                        ['type' => 'FOOTER', 'text' => 'Review in admin panel']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Restaurant name', 'Owner name', 'Email', 'Phone', 'Plan selected', 'Signup date'],
                'is_active' => true,
            ],
            // Staff Notifications (9)
            [
                'notification_type' => 'new_kot_notification',
                'template_name' => 'New KOT Notification',
                'category' => 'staff',
                'description' => 'Notify kitchen when new KOT is created',
                'template_json' => json_encode([
                    'name' => 'new_kot_notification',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'New KOT'],
                        ['type' => 'BODY', 'text' => 'New KOT #{{1}} for order #{{2}}. Table: {{3}}, Type: {{4}}, Items: {{5}}, Time: {{6}}'],
                        ['type' => 'FOOTER', 'text' => 'Priority: {{7}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['KOT number', 'Order number', 'Table number', 'Order type', 'Items list', 'Order time', 'Priority'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'order_modification_alert',
                'template_name' => 'Order Modification Alert',
                'category' => 'staff',
                'description' => 'Alert kitchen when order is modified',
                'template_json' => json_encode([
                    'name' => 'order_modification_alert',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Order Modified'],
                        ['type' => 'BODY', 'text' => 'KOT #{{1}}, Order #{{2}} has been modified. Change: {{3}}. Updated items: {{4}}'],
                        ['type' => 'FOOTER', 'text' => 'Time: {{5}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['KOT number', 'Order number', 'Modification type', 'Changes details', 'Modification time'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'order_cancellation_alert',
                'template_name' => 'Order Cancellation Alert',
                'category' => 'staff',
                'description' => 'Alert kitchen when order is cancelled',
                'template_json' => json_encode([
                    'name' => 'order_cancellation_alert',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Order Cancelled'],
                        ['type' => 'BODY', 'text' => 'KOT #{{1}}, Order #{{2}} has been cancelled. Reason: {{3}}, Items: {{4}}'],
                        ['type' => 'FOOTER', 'text' => 'Time: {{5}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['KOT number', 'Order number', 'Cancellation reason', 'Cancelled items', 'Cancellation time'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'waiter_request',
                'template_name' => 'Waiter Request',
                'category' => 'staff',
                'description' => 'Notify waiter when customer requests service',
                'template_json' => json_encode([
                    'name' => 'waiter_request',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Waiter Request'],
                        ['type' => 'BODY', 'text' => 'Waiter request! Table: {{1}}, Customer: {{2}}, Request type: {{3}}, Time: {{4}}'],
                        ['type' => 'FOOTER', 'text' => 'Branch: {{5}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Table number', 'Customer name', 'Request type', 'Request time', 'Branch name'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'table_assignment',
                'template_name' => 'Table Assignment',
                'category' => 'staff',
                'description' => 'Notify waiter when assigned to a table',
                'template_json' => json_encode([
                    'name' => 'table_assignment',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Table Assigned'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, you have been assigned to table {{2}}. Customer: {{3}}, Guests: {{4}}, Time: {{5}}'],
                        ['type' => 'FOOTER', 'text' => 'Notes: {{6}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Waiter name', 'Table number', 'Customer name', 'Number of guests', 'Assignment time', 'Special notes'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'order_ready_to_serve',
                'template_name' => 'Order Ready to Serve',
                'category' => 'staff',
                'description' => 'Notify waiter when order is ready to serve',
                'template_json' => json_encode([
                    'name' => 'order_ready_to_serve',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Order Ready'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, order #{{2}} for table {{3}} is ready to serve. Items: {{4}}, Time: {{5}}'],
                        ['type' => 'FOOTER', 'text' => 'Notes: {{6}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Waiter name', 'Order number', 'Table number', 'Items ready', 'Ready time', 'Special instructions'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'payment_request_alert',
                'template_name' => 'Payment Request Alert',
                'category' => 'staff',
                'description' => 'Alert waiter when customer requests bill',
                'template_json' => json_encode([
                    'name' => 'payment_request_alert',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Payment Request'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, payment requested for table {{2}}. Order #{{3}}, Customer: {{4}}, Time: {{5}}'],
                        ['type' => 'FOOTER', 'text' => 'Please prepare bill']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Waiter name', 'Table number', 'Order number', 'Customer name', 'Request time'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'table_status_change',
                'template_name' => 'Table Status Change',
                'category' => 'staff',
                'description' => 'Notify waiter about table status changes',
                'template_json' => json_encode([
                    'name' => 'table_status_change',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Table Status'],
                        ['type' => 'BODY', 'text' => 'Table {{1}} status changed from {{2}} to {{3}}. Time: {{4}}'],
                        ['type' => 'FOOTER', 'text' => 'Notes: {{5}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Table number', 'Previous status', 'New status', 'Change time', 'Notes'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'daily_operations_summary',
                'template_name' => 'Daily Operations Summary',
                'category' => 'staff',
                'description' => 'End-of-day operations summary for managers',
                'template_json' => json_encode([
                    'name' => 'daily_operations_summary',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Daily Summary'],
                        ['type' => 'BODY', 'text' => 'Daily summary for {{1}} on {{2}}. Orders: {{3}}, Revenue: {{4}}, Staff: {{5}}, Peak: {{6}}'],
                        ['type' => 'FOOTER', 'text' => 'Issues: {{7}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Branch name', 'Date', 'Total orders', 'Total revenue', 'Staff on duty', 'Peak hours', 'Issues summary'],
                'is_active' => true,
            ],
            // Delivery Notifications (3)
            [
                'notification_type' => 'delivery_assignment',
                'template_name' => 'Delivery Assignment',
                'category' => 'delivery',
                'description' => 'Notify delivery executive when assigned a delivery',
                'template_json' => json_encode([
                    'name' => 'delivery_assignment',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'New Delivery'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, new delivery assigned! Order #{{2}}, Customer: {{3}}, Phone: {{4}}, Pickup: {{5}}, Delivery: {{6}}, Amount: {{7}}'],
                        ['type' => 'FOOTER', 'text' => 'ETA: {{8}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Delivery executive name', 'Order number', 'Customer name', 'Customer phone', 'Pickup address', 'Delivery address', 'Total amount', 'Estimated time'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'order_ready_for_pickup',
                'template_name' => 'Order Ready for Pickup',
                'category' => 'delivery',
                'description' => 'Notify delivery executive when order is ready for pickup',
                'template_json' => json_encode([
                    'name' => 'order_ready_for_pickup',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Ready for Pickup'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, order #{{2}} is ready for pickup! Location: {{3}}, Customer: {{4}}, Delivery: {{5}}, Time: {{6}}'],
                        ['type' => 'FOOTER', 'text' => 'Contact: {{7}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Delivery executive name', 'Order number', 'Pickup address', 'Customer name', 'Delivery address', 'Ready time', 'Contact number'],
                'is_active' => true,
            ],
            [
                'notification_type' => 'delivery_completion_confirmation',
                'template_name' => 'Delivery Completion Confirmation',
                'category' => 'delivery',
                'description' => 'Confirm successful delivery completion',
                'template_json' => json_encode([
                    'name' => 'delivery_completion_confirmation',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Delivery Completed'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, delivery for order #{{2}} completed successfully! Customer: {{3}}, Time: {{4}}, Address: {{5}}, Payment: {{6}}'],
                        ['type' => 'FOOTER', 'text' => 'Next: {{7}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Delivery executive name', 'Order number', 'Customer name', 'Delivery time', 'Delivery address', 'Payment status', 'Next delivery'],
                'is_active' => true,
            ],
            // Automated Reminders (1)
            [
                'notification_type' => 'reservation_followup',
                'template_name' => 'Reservation Follow-up',
                'category' => 'automated',
                'description' => 'Follow-up after reservation completion',
                'template_json' => json_encode([
                    'name' => 'reservation_followup',
                    'language' => 'en',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Thank You'],
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, thank you for visiting us on {{2}}! We hope you enjoyed your experience. {{3}}'],
                        ['type' => 'FOOTER', 'text' => 'Restaurant: {{4}}']
                    ]
                ], JSON_PRETTY_PRINT),
                'sample_variables' => ['Customer name', 'Reservation date', 'Feedback link/Discount offer', 'Restaurant name'],
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
            $this->command->info('WhatsApp template definitions seeded successfully!');
        }
    }
}

