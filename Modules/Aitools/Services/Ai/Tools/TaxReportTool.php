<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Order;
use App\Models\Tax;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxReportTool
{
    /**
     * Get tax report data
     */
    public function getTaxReport(array $args): array
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

            // Get orders with tax data
            $orders = Order::with(['items', 'taxes.tax'])
                ->where('branch_id', $branchId)
                ->where('status', 'paid')
                ->whereBetween('date_time', [$from, $to])
                ->get();

            $taxBreakdown = [];
            $totalTax = 0;
            $totalRevenue = 0;

            foreach ($orders as $order) {
                $totalRevenue += (float) ($order->total ?? 0);
                $orderTax = 0;

                // Process item-level taxes
                foreach ($order->items as $item) {
                    $orderTax += (float) ($item->tax_amount ?? 0);
                }

                // Process order-level taxes
                foreach ($order->taxes as $orderTaxRelation) {
                    $tax = $orderTaxRelation->tax;
                    if (!$tax) continue;

                    $subtotal = (float) ($order->sub_total ?? 0);
                    $discountAmount = (float) ($order->discount_amount ?? 0);
                    $taxableAmount = $subtotal - $discountAmount;
                    $taxAmount = round(($tax->tax_percent / 100) * $taxableAmount, 2);
                    $orderTax += $taxAmount;

                    $taxName = $tax->tax_name;
                    if (!isset($taxBreakdown[$taxName])) {
                        $taxBreakdown[$taxName] = [
                            'tax_name' => $taxName,
                            'tax_percent' => (float) $tax->tax_percent,
                            'total_amount' => 0,
                            'orders_count' => 0,
                        ];
                    }
                    $taxBreakdown[$taxName]['total_amount'] += $taxAmount;
                }

                // Fallback to total_tax_amount
                if ($orderTax == 0 && isset($order->total_tax_amount) && $order->total_tax_amount > 0) {
                    $orderTax = (float) $order->total_tax_amount;
                }

                $totalTax += $orderTax;
            }

            // Count orders per tax
            foreach ($taxBreakdown as $taxName => &$taxData) {
                $taxData['orders_count'] = $orders->count(); // Simplified count
            }

            return [
                'tax_breakdown' => array_values($taxBreakdown),
                'total_tax' => round($totalTax, 2),
                'total_revenue' => round($totalRevenue, 2),
                'total_orders' => $orders->count(),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

