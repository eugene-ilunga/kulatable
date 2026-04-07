<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\ItemCategory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoryReportTool
{
    /**
     * Get category report data
     */
    public function getCategoryReport(array $args): array
    {
        try {
            $dateFrom = $args['date_from'] ?? now()->subDays(7)->toDateString();
            $dateTo = $args['date_to'] ?? now()->toDateString();
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

            // Get categories with sales data
            $categories = ItemCategory::with(['orders' => function ($q) use ($from, $to, $branchId) {
                return $q->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.status', 'paid')
                    ->whereBetween('orders.date_time', [$from, $to])
                    ->where('orders.branch_id', $branchId);
            }])->get();

            $reportData = $categories->map(function ($category) {
                $quantitySold = $category->orders->sum('quantity') ?? 0;
                $totalRevenue = $category->orders->sum('amount') ?? 0;

                return [
                    'category_id' => $category->id,
                    'category_name' => $category->category_name,
                    'quantity_sold' => (int) $quantitySold,
                    'total_revenue' => (float) $totalRevenue,
                ];
            })
            ->filter(function ($category) {
                return $category['quantity_sold'] > 0;
            })
            ->sortByDesc('total_revenue')
            ->values()
            ->toArray();

            return $reportData;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

