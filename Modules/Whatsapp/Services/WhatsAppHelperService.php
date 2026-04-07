<?php

namespace Modules\Whatsapp\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WhatsAppHelperService
{
    /** @var array<int, string> */
    private static array $currencySymbolByRestaurant = [];

    private static function currencySymbolForRestaurant(int $restaurantId): string
    {
        if (! array_key_exists($restaurantId, self::$currencySymbolByRestaurant)) {
            self::$currencySymbolByRestaurant[$restaurantId] = \App\Models\Restaurant::query()
                ->whereKey($restaurantId)
                ->with('currency')
                ->first()
                ?->currency
                ?->currency_symbol ?? '';
        }

        return self::$currencySymbolByRestaurant[$restaurantId];
    }

    /**
     * Get users by role IDs for a restaurant.
     *
     * @param int $restaurantId
     * @param array $roleIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByRoles(int $restaurantId, array $roleIds): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($roleIds)) {
            return collect([]);
        }

        $users = User::where('restaurant_id', $restaurantId)
            ->whereNotNull('phone_number')
            ->whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('id', $roleIds);
            })
            ->get();

        return $users;
    }

    /**
     * Format daily sales report data.
     *
     * @param int $restaurantId
     * @param Carbon $date
     * @return array
     */
    public function formatDailySalesReport(int $restaurantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $orders = Order::whereHas('branch', function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->whereBetween('date_time', [$startOfDay, $endOfDay])
            ->whereNotIn('status', ['draft', 'canceled'])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum(function($order) {
            return (float) ($order->total ?? $order->sub_total ?? 0);
        });
        $totalTax = $orders->sum('total_tax_amount') ?? 0;
        $totalDiscount = $orders->sum('discount_amount') ?? 0;
        $netRevenue = $totalRevenue - $totalDiscount;

        $currency = self::currencySymbolForRestaurant($restaurantId);

        return [
            'date' => $date->format('d M, Y'),
            'total_orders' => $totalOrders,
            'total_revenue' => $currency . number_format($totalRevenue, 2),
            'net_revenue' => $currency . number_format($netRevenue, 2),
            'total_tax' => $currency . number_format($totalTax, 2),
            'total_discount' => $currency . number_format($totalDiscount, 2),
        ];
    }

    /**
     * Format weekly sales report data.
     *
     * @param int $restaurantId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function formatWeeklySalesReport(int $restaurantId, Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::whereHas('branch', function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->whereBetween('date_time', [$startDate, $endDate])
            ->whereNotIn('status', ['draft', 'canceled'])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum(function($order) {
            return (float) ($order->total ?? $order->sub_total ?? 0);
        });
        $totalTax = $orders->sum('total_tax_amount') ?? 0;
        $totalDiscount = $orders->sum('discount_amount') ?? 0;
        $netRevenue = $totalRevenue - $totalDiscount;

        $currency = self::currencySymbolForRestaurant($restaurantId);

        return [
            'period' => $startDate->format('d M') . ' - ' . $endDate->format('d M, Y'),
            'total_orders' => $totalOrders,
            'total_revenue' => $currency . number_format($totalRevenue, 2),
            'net_revenue' => $currency . number_format($netRevenue, 2),
            'total_tax' => $currency . number_format($totalTax, 2),
            'total_discount' => $currency . number_format($totalDiscount, 2),
        ];
    }

    /**
     * Format monthly sales report data.
     *
     * @param int $restaurantId
     * @param Carbon $date
     * @return array
     */
    public function formatMonthlySalesReport(int $restaurantId, Carbon $date): array
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $orders = Order::whereHas('branch', function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->whereBetween('date_time', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['draft', 'canceled'])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum(function($order) {
            return (float) ($order->total ?? $order->sub_total ?? 0);
        });
        $totalTax = $orders->sum('total_tax_amount') ?? 0;
        $totalDiscount = $orders->sum('discount_amount') ?? 0;
        $netRevenue = $totalRevenue - $totalDiscount;

        $currency = self::currencySymbolForRestaurant($restaurantId);

        return [
            'month' => $date->format('F Y'),
            'total_orders' => $totalOrders,
            'total_revenue' => $currency . number_format($totalRevenue, 2),
            'net_revenue' => $currency . number_format($netRevenue, 2),
            'total_tax' => $currency . number_format($totalTax, 2),
            'total_discount' => $currency . number_format($totalDiscount, 2),
        ];
    }

    /**
     * Format low inventory alert data.
     *
     * @param int $restaurantId
     * @return array
     */
    public function formatLowInventoryAlert(int $restaurantId): array
    {
        // Check if Inventory module is enabled
        if (!module_enabled('Inventory')) {
            return [
                'item_count' => 0,
                'item_names' => __('whatsapp::app.noItems'),
            ];
        }

        // Get all branch IDs for this restaurant
        $branchIds = \App\Models\Branch::where('restaurant_id', $restaurantId)->pluck('id')->toArray();
        
        if (empty($branchIds)) {
            return [
                'item_count' => 0,
                'item_names' => __('whatsapp::app.noItems'),
            ];
        }
        
        // Get items where stock <= threshold across all branches for the restaurant
        // Low stock = total stock quantity <= threshold_quantity
        $lowStockItems = DB::table('inventory_stocks')
            ->join('inventory_items', 'inventory_stocks.inventory_item_id', '=', 'inventory_items.id')
            ->whereIn('inventory_stocks.branch_id', $branchIds)
            ->select(
                'inventory_items.id', 
                'inventory_items.name', 
                DB::raw('SUM(inventory_stocks.quantity) as total_quantity'), 
                'inventory_items.threshold_quantity'
            )
            ->groupBy('inventory_items.id', 'inventory_items.name', 'inventory_items.threshold_quantity')
            ->havingRaw('SUM(inventory_stocks.quantity) <= inventory_items.threshold_quantity')
            ->havingRaw('SUM(inventory_stocks.quantity) >= 0') // Include items with 0 stock (out of stock)
            ->get();

        $itemCount = $lowStockItems->count();
        $itemNames = $lowStockItems->take(5)->pluck('name')->implode(', ');
        if ($lowStockItems->count() > 5) {
            $itemNames .= ' and ' . ($lowStockItems->count() - 5) . ' more';
        }

        return [
            'item_count' => $itemCount,
            'item_names' => $itemNames ?: __('whatsapp::app.noItems'),
        ];
    }

    /**
     * Format daily operations summary.
     *
     * @param int $restaurantId
     * @param Carbon $date
     * @return array
     */
    public function formatDailyOperationsSummary(int $restaurantId, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $orders = Order::whereHas('branch', function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->whereBetween('date_time', [$startOfDay, $endOfDay])
            ->where('status', 'paid')
            ->get();

        $reservations = Reservation::whereHas('branch', function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->whereBetween('reservation_date_time', [$startOfDay, $endOfDay])
            ->count();

        // Use 'total' field instead of 'total_amount' for accurate revenue calculation
        $totalRevenue = $orders->sum(function($order) {
            return (float) ($order->total ?? $order->sub_total ?? 0);
        });
        $currency = self::currencySymbolForRestaurant($restaurantId);

        return [
            'date' => $date->format('d M, Y'),
            'total_orders' => $orders->count(),
            'total_reservations' => $reservations,
            'total_revenue' => $currency . number_format($totalRevenue, 2),
        ];
    }
}

