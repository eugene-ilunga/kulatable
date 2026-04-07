<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Scopes\AvailableMenuItemScope;

class ItemReportTool
{
    /**
     * Get item report data
     */
    public function getItemReport(array $args): array
    {
        try {
            $dateFrom = $args['date_from'] ?? now()->subDays(7)->toDateString();
            $dateTo = $args['date_to'] ?? now()->toDateString();
            $limit = min($args['limit'] ?? 20, 50);
            $branchId = branch()->id ?? null;

            if (!$branchId) {
                return ['error' => 'No branch context available'];
            }

            // Validate dates
            try {
                $from = Carbon::parse($dateFrom)->startOfDay();
                $to = Carbon::parse($dateTo)->endOfDay();
            } catch (\Exception $e) {
                return ['error' => 'Invalid date format. Use YYYY-MM-DD'];
            }

            // Get items with sales data
            $items = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
                ->with(['orders' => function ($q) use ($from, $to, $branchId) {
                    return $q->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->whereBetween('orders.date_time', [$from, $to])
                        ->where('orders.status', 'paid')
                        ->where('orders.branch_id', $branchId);
                }, 'category'])
                ->withCount('variations')
                ->get();

            $reportData = $items->map(function ($item) {
                if ($item->variations_count > 0) {
                    $item->variations->each(function ($variation) use ($item) {
                        $variation->quantity_sold = $item->orders->where('menu_item_variation_id', $variation->id)->sum('quantity') ?? 0;
                        $variation->total_revenue = $variation->price * $variation->quantity_sold;
                    });
                    $quantitySold = $item->variations->sum('quantity_sold');
                    $totalRevenue = $item->variations->sum('total_revenue');
                } else {
                    $quantitySold = $item->orders->sum('quantity');
                    $totalRevenue = $item->price * $quantitySold;
                }

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'category_name' => $item->category ? $item->category->category_name : null,
                    'price' => (float) $item->price,
                    'quantity_sold' => (int) $quantitySold,
                    'total_revenue' => (float) $totalRevenue,
                ];
            })
            ->filter(function ($item) {
                return $item['quantity_sold'] > 0; // Only return items with sales
            })
            ->sortByDesc('total_revenue')
            ->take($limit)
            ->values()
            ->toArray();

            return $reportData;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

