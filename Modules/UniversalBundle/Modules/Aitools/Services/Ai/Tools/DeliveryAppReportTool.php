<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Order;
use App\Models\DeliveryPlatform;
use App\Models\OrderType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeliveryAppReportTool
{
    /**
     * Get delivery app report data
     */
    public function getDeliveryAppReport(array $args): array
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

            $deliveryOrderTypes = OrderType::where('slug', 'delivery')->first();
            if (!$deliveryOrderTypes) {
                return ['error' => 'Delivery order type not found'];
            }

            $deliveryApps = DeliveryPlatform::all();

            // Get aggregated data grouped by delivery app
            $deliveryAppStats = Order::select(
                    'delivery_app_id',
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(sub_total) as total_revenue'),
                    DB::raw('SUM(delivery_fee) as total_delivery_fees'),
                    DB::raw('AVG(sub_total) as avg_order_value')
                )
                ->where('branch_id', $branchId)
                ->whereBetween('date_time', [$from, $to])
                ->where('status', 'paid')
                ->where('order_type_id', $deliveryOrderTypes->id)
                ->groupBy('delivery_app_id')
                ->get();

            $reportData = $deliveryAppStats->map(function ($stat) use ($deliveryApps) {
                $deliveryApp = $deliveryApps->firstWhere('id', $stat->delivery_app_id);

                if (!$deliveryApp && $stat->delivery_app_id === null) {
                    return [
                        'delivery_app_name' => 'Direct Delivery',
                        'total_orders' => (int) $stat->total_orders,
                        'total_revenue' => (float) $stat->total_revenue,
                        'total_delivery_fees' => (float) $stat->total_delivery_fees,
                        'avg_order_value' => (float) $stat->avg_order_value,
                        'commission' => 0,
                        'net_revenue' => (float) $stat->total_revenue,
                    ];
                }

                if (!$deliveryApp) {
                    return null;
                }

                $commission = 0;
                if ($deliveryApp->commission_type === 'percent') {
                    $commission = ($stat->total_revenue * $deliveryApp->commission_value) / 100;
                } else {
                    $commission = $deliveryApp->commission_value * $stat->total_orders;
                }

                return [
                    'delivery_app_name' => $deliveryApp->name,
                    'total_orders' => (int) $stat->total_orders,
                    'total_revenue' => (float) $stat->total_revenue,
                    'total_delivery_fees' => (float) $stat->total_delivery_fees,
                    'avg_order_value' => (float) $stat->avg_order_value,
                    'commission' => round($commission, 2),
                    'net_revenue' => round($stat->total_revenue - $commission, 2),
                ];
            })->filter()->values()->toArray();

            return $reportData;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

