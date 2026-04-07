<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancelledOrderReportTool
{
    /**
     * Get cancelled order report data
     */
    public function getCancelledOrderReport(array $args): array
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

            // Get cancelled orders
            $orders = Order::with(['customer', 'cancelReason', 'cancelledBy', 'table'])
                ->where('branch_id', $branchId)
                ->where('status', 'canceled')
                ->where('order_status', 'cancelled')
                ->whereBetween('cancel_time', [$from, $to])
                ->orderBy('cancel_time', 'desc')
                ->limit($limit)
                ->get();

            $reportData = $orders->map(function ($order) {
                return [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'cancel_time' => $order->cancel_time ? Carbon::parse($order->cancel_time)->toDateTimeString() : null,
                    'cancel_reason' => $order->cancelReason ? $order->cancelReason->reason : ($order->cancel_reason_text ?? 'N/A'),
                    'cancelled_by' => $order->cancelledBy ? $order->cancelledBy->name : 'N/A',
                    'total' => (float) ($order->total ?? 0),
                    'customer_name' => $order->customer ? $order->customer->name : null,
                    'table_code' => $order->table ? $order->table->table_code : null,
                ];
            })->toArray();

            $summary = [
                'total_cancelled_orders' => $orders->count(),
                'total_cancelled_amount' => round($orders->sum('total'), 2),
            ];

            return [
                'summary' => $summary,
                'orders' => $reportData,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

