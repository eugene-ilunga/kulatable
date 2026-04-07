<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrdersTool
{
    /**
     * Get orders list
     */
    public function getOrders(array $args): array
    {
        try {
            $status = $args['status'] ?? null;
            $dateFrom = $args['date_from'] ?? now()->subDays(7)->toDateString();
            $dateTo = $args['date_to'] ?? now()->toDateString();
            $limit = min($args['limit'] ?? 50, 100); // Cap at 100
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

            $query = Order::where('branch_id', $branchId)
                ->whereBetween('date_time', [$from, $to]);

            if ($status) {
                $query->where('status', $status);
            }

            $orders = $query->with(['table', 'customer'])
                ->orderByDesc('date_time')
                ->limit($limit)
                ->get();

            return $orders->map(function ($order) {
                // Ensure order_number is displayed as integer (remove decimals)
                $orderNumber = $order->order_number;
                if (is_numeric($orderNumber)) {
                    $orderNumber = (string)(int)(float)$orderNumber;
                }
                
                return [
                    'order_id' => (int) $order->id,
                    'order_no' => $orderNumber,
                    'date' => $order->date_time->format('M d, Y'),
                    'date_time' => $order->date_time->toDateTimeString(),
                    'table' => $order->table ? $order->table->table_code : null,
                    'customer' => $order->customer ? $order->customer->name : null,
                    'total' => round((float) $order->total, 2),
                    'status' => $order->status,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

