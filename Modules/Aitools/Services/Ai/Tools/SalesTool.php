<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Order;
use App\Models\OrderTax;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesTool
{
    /**
     * Get sales data by day
     */
    public function getSalesByDay(array $args): array
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

            // Get orders with totals
            // Gross Sales = Revenue before discounts (sub_total + taxes)
            // Net Sales = Final revenue after discounts (total)
            $orders = Order::where('branch_id', $branchId)
                ->whereBetween('date_time', [$from, $to])
                ->whereIn('status', ['paid', 'billed'])
                ->select(
                    DB::raw('DATE(date_time) as date'),
                    DB::raw('COUNT(*) as orders_count'),
                    DB::raw('COALESCE(SUM(sub_total), 0) + COALESCE(SUM(total_tax_amount), 0) as gross_sales'),
                    DB::raw('COALESCE(SUM(total), 0) as net_sales'),
                    DB::raw('COALESCE(SUM(total_tax_amount), 0) as tax_total'),
                    DB::raw('COALESCE(SUM(discount_amount), 0) as discount_total')
                )
                ->groupBy(DB::raw('DATE(date_time)'))
                ->orderBy('date')
                ->get();

            return $orders->map(function ($order) {
                // Access properties directly - DB raw selects create dynamic properties
                return [
                    'date' => (string) ($order->date ?? ''),
                    'gross_sales' => (float) ($order->gross_sales ?? 0),
                    'net_sales' => (float) ($order->net_sales ?? 0),
                    'orders_count' => (int) ($order->orders_count ?? 0),
                    'tax_total' => (float) ($order->tax_total ?? 0),
                    'discount_total' => (float) ($order->discount_total ?? 0),
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

