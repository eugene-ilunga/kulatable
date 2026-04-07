<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TopItemsTool
{
    /**
     * Get top selling items
     */
    public function getTopItems(array $args): array
    {
        try {
            $dateFrom = $args['date_from'] ?? now()->subDays(7)->toDateString();
            $dateTo = $args['date_to'] ?? now()->toDateString();
            $limit = min($args['limit'] ?? 10, 50); // Cap at 50
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

            // Get top items
            $items = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                ->where('orders.branch_id', $branchId)
                ->whereBetween('orders.date_time', [$from, $to])
                ->whereIn('orders.status', ['paid', 'billed'])
                ->select(
                    'menu_items.id as item_id',
                    'menu_items.item_name',
                    DB::raw('SUM(order_items.quantity) as qty_sold'),
                    DB::raw('SUM(order_items.amount) as revenue')
                )
                ->groupBy('menu_items.id', 'menu_items.item_name')
                ->orderByDesc('qty_sold')
                ->limit($limit)
                ->get();

            return $items->map(function ($item) {
                return [
                    'item_id' => (int) $item->item_id,
                    'item_name' => $item->item_name,
                    'qty_sold' => (int) $item->qty_sold,
                    'revenue' => (float) $item->revenue,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

