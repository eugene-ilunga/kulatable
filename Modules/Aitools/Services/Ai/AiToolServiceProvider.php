<?php

namespace Modules\Aitools\Services\Ai;

use Modules\Aitools\Services\Ai\Tools\SalesTool;
use Modules\Aitools\Services\Ai\Tools\TopItemsTool;
use Modules\Aitools\Services\Ai\Tools\OrdersTool;
use Modules\Aitools\Services\Ai\Tools\KotDelaysTool;
use Modules\Aitools\Services\Ai\Tools\InventoryUsageTool;
use Modules\Aitools\Services\Ai\Tools\ItemReportTool;
use Modules\Aitools\Services\Ai\Tools\CategoryReportTool;
use Modules\Aitools\Services\Ai\Tools\TaxReportTool;
use Modules\Aitools\Services\Ai\Tools\CancelledOrderReportTool;
use Modules\Aitools\Services\Ai\Tools\RefundReportTool;
use Modules\Aitools\Services\Ai\Tools\DeliveryAppReportTool;
use Modules\Aitools\Services\Ai\Tools\ReservationTool;

class AiToolServiceProvider
{
    /**
     * Register all tools with the registry
     */
    public static function registerTools(ToolRegistry $registry): void
    {
        $salesTool = new SalesTool();
        $topItemsTool = new TopItemsTool();
        $ordersTool = new OrdersTool();
        $kotDelaysTool = new KotDelaysTool();
        $inventoryTool = new InventoryUsageTool();
        $itemReportTool = new ItemReportTool();
        $categoryReportTool = new CategoryReportTool();
        $taxReportTool = new TaxReportTool();
        $cancelledOrderReportTool = new CancelledOrderReportTool();
        $refundReportTool = new RefundReportTool();
        $deliveryAppReportTool = new DeliveryAppReportTool();
        $reservationTool = new ReservationTool();

        // Register get_sales_by_day
        $registry->register('get_sales_by_day', [
            'description' => 'Get sales data grouped by day. Returns gross sales, net sales, orders count, tax total, and discount total for each day.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'required' => [],
        ], [$salesTool, 'getSalesByDay']);

        // Register get_top_items
        $registry->register('get_top_items', [
            'description' => 'Get top selling menu items by quantity sold. Returns item name, quantity sold, and revenue.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Number of items to return (max 50)',
                ],
            ],
            'required' => [],
        ], [$topItemsTool, 'getTopItems']);

        // Register get_orders
        $registry->register('get_orders', [
            'description' => 'Get list of orders with details. Returns order number, date, table, customer, total, and status.',
            'parameters' => [
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by order status (draft, kot, billed, paid, canceled, payment_due)',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of orders to return (max 100)',
                ],
            ],
            'required' => [],
        ], [$ordersTool, 'getOrders']);

        // Register get_kot_delays
        $registry->register('get_kot_delays', [
            'description' => 'Get KOT (Kitchen Order Ticket) delay analysis. Returns delay times for each KOT and summary statistics (average delay, 90th percentile delay).',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'required' => [],
        ], [$kotDelaysTool, 'getKotDelays']);

        // Register get_inventory_usage
        $registry->register('get_inventory_usage', [
            'description' => 'Get inventory usage from order items. Returns item name, quantity used, unit, and estimated cost.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'required' => [],
        ], [$inventoryTool, 'getInventoryUsage']);

        // Register get_item_report
        $registry->register('get_item_report', [
            'description' => 'Get item report data showing sales performance by menu item. Returns item name, category, price, quantity sold, and total revenue. Use this for questions about item sales, best selling items, or item performance.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Number of items to return (max 50)',
                ],
            ],
            'required' => [],
        ], [$itemReportTool, 'getItemReport']);

        // Register get_category_report
        $registry->register('get_category_report', [
            'description' => 'Get category report data showing sales performance by item category. Returns category name, quantity sold, and total revenue. Use this for questions about category sales or category performance.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'required' => [],
        ], [$categoryReportTool, 'getCategoryReport']);

        // Register get_tax_report
        $registry->register('get_tax_report', [
            'description' => 'Get tax report data showing tax breakdown by tax type. Returns tax name, tax percent, total tax amount, orders count, total revenue, and total orders. Use this for questions about taxes, tax collection, or tax breakdown.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'required' => [],
        ], [$taxReportTool, 'getTaxReport']);

        // Register get_cancelled_order_report
        $registry->register('get_cancelled_order_report', [
            'description' => 'Get cancelled order report data. Returns cancelled orders with order number, cancel time, cancel reason, cancelled by, total amount, customer, and table. Use this for questions about cancelled orders, cancellation reasons, or cancellation analysis.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Number of orders to return (max 50)',
                ],
            ],
            'required' => [],
        ], [$cancelledOrderReportTool, 'getCancelledOrderReport']);

        // Register get_refund_report
        $registry->register('get_refund_report', [
            'description' => 'Get refund report data. Returns refunds with order number, refund type, amount, processed date, refund reason, and processed by. Use this for questions about refunds, refund amounts, or refund analysis.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Number of refunds to return (max 50)',
                ],
            ],
            'required' => [],
        ], [$refundReportTool, 'getRefundReport']);

        // Register get_delivery_app_report
        $registry->register('get_delivery_app_report', [
            'description' => 'Get delivery app report data showing performance by delivery platform. Returns delivery app name, total orders, total revenue, delivery fees, average order value, commission, and net revenue. Use this for questions about delivery apps, delivery performance, or delivery commissions.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'required' => [],
        ], [$deliveryAppReportTool, 'getDeliveryAppReport']);

        // Register get_reservations
        $registry->register('get_reservations', [
            'description' => 'Get list of table reservations. Returns reservation date/time, table, customer, party size, status, slot type (Breakfast/Lunch/Dinner), and special requests. Use this for questions about reservations, upcoming reservations, reservation status, or reservation details.',
            'parameters' => [
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by reservation status (Pending, Confirmed, Checked_In, Cancelled, No_Show)',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
                'slot_type' => [
                    'type' => 'string',
                    'description' => 'Filter by slot type (Breakfast, Lunch, Dinner)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of reservations to return (max 100)',
                ],
            ],
            'required' => [],
        ], [$reservationTool, 'getReservations']);

        // Register get_reservation_stats
        $registry->register('get_reservation_stats', [
            'description' => 'Get reservation statistics and summary. Returns total reservations, counts by status (confirmed, checked_in, cancelled, no_show), total guests, and average party size. Use this for questions about reservation statistics, reservation trends, or reservation summaries.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'required' => [],
        ], [$reservationTool, 'getReservationStats']);
    }
}
