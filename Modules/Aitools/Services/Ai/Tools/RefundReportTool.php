<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RefundReportTool
{
    /**
     * Get refund report data
     */
    public function getRefundReport(array $args): array
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

            // Get refunds
            $refunds = Refund::with(['payment.order', 'refundReason', 'processedBy'])
                ->where('branch_id', $branchId)
                ->where('status', 'processed')
                ->whereBetween('processed_at', [$from, $to])
                ->orderBy('processed_at', 'desc')
                ->limit($limit)
                ->get();

            $reportData = $refunds->map(function ($refund) {
                return [
                    'refund_id' => $refund->id,
                    'order_number' => $refund->payment && $refund->payment->order ? $refund->payment->order->order_number : 'N/A',
                    'refund_type' => $refund->refund_type,
                    'amount' => (float) $refund->amount,
                    'processed_at' => $refund->processed_at ? Carbon::parse($refund->processed_at)->toDateTimeString() : null,
                    'refund_reason' => $refund->refundReason ? $refund->refundReason->reason : 'N/A',
                    'processed_by' => $refund->processedBy ? $refund->processedBy->name : 'N/A',
                ];
            })->toArray();

            $summary = [
                'total_refunds' => $refunds->count(),
                'total_refund_amount' => round($refunds->sum('amount'), 2),
            ];

            return [
                'summary' => $summary,
                'refunds' => $reportData,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

