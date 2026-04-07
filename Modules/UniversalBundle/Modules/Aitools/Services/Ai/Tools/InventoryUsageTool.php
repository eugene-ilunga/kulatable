<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryUsageTool
{
    /**
     * Get inventory usage from order items
     */
    public function getInventoryUsage(array $args): array
    {
        try {
            $dateFrom = $args['date_from'] ?? now()->subDays(7)->toDateString();
            $dateTo = $args['date_to'] ?? now()->toDateString();
            $branchId = branch()->id ?? null;

            if (!$branchId) {
                return [
                    'error' => 'No branch context available',
                ];
            }

            // Validate dates
            try {
                $from = Carbon::parse($dateFrom)->startOfDay();
                $to = Carbon::parse($dateTo)->endOfDay();
            } catch (\Exception $e) {
                return [
                    'error' => 'Invalid date format. Use YYYY-MM-DD',
                ];
            }

            // Get inventory usage from order items
            // Note: This is a simplified version. In a real system, you'd track actual inventory consumption
            $usage = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->where('orders.branch_id', $branchId)
                ->whereBetween('orders.date_time', [$from, $to])
                ->whereIn('orders.status', ['paid', 'billed'])
                ->select(
                    'menu_items.id as item_id',
                    'menu_items.item_name',
                    DB::raw('SUM(order_items.quantity) as qty_used'),
                    DB::raw('AVG(order_items.price) as avg_price')
                )
                ->groupBy('menu_items.id', 'menu_items.item_name')
                ->orderByDesc('qty_used')
                ->get();

            return $usage->map(function ($item) {
                // Estimate cost (simplified - in real system, use actual inventory cost)
                $estimatedCost = (float) $item->avg_price * 0.3; // Assume 30% cost ratio

                return [
                    'item_id' => (int) $item->item_id,
                    'item_name' => $item->item_name,
                    'qty_used' => (int) $item->qty_used,
                    'unit' => 'pcs', // Default unit
                    'estimated_cost' => round($estimatedCost * $item->qty_used, 2),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

