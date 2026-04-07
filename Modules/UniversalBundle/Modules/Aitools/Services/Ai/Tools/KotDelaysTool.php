<?php

namespace Modules\Aitools\Services\Ai\Tools;

use App\Models\Kot;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KotDelaysTool
{
    /**
     * Get KOT delays analysis
     */
    public function getKotDelays(array $args): array
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

            // Get KOTs with delays
            $kots = Kot::where('branch_id', $branchId)
                ->whereBetween('created_at', [$from, $to])
                ->whereIn('status', ['food_ready', 'served'])
                ->with(['kotPlace', 'order'])
                ->get();

            $delays = [];
            $delayMinutes = [];

            foreach ($kots as $kot) {
                $createdAt = Carbon::parse($kot->created_at);
                $completedAt = $kot->updated_at; // Assuming updated_at is when status changed

                if ($kot->status === 'served' || $kot->status === 'food_ready') {
                    $delay = $createdAt->diffInMinutes($completedAt);
                    $delayMinutes[] = $delay;

                    $delays[] = [
                        'kot_id' => $kot->id,
                        'order_id' => $kot->order_id,
                        'created_at' => $createdAt->toDateTimeString(),
                        'completed_at' => $completedAt->toDateTimeString(),
                        'delay_minutes' => $delay,
                        'kitchen_name' => $kot->kotPlace ? $kot->kotPlace->name : 'Unknown',
                    ];
                }
            }

            // Calculate statistics
            $avgDelay = count($delayMinutes) > 0 ? array_sum($delayMinutes) / count($delayMinutes) : 0;
            sort($delayMinutes);
            $p90Index = (int) (count($delayMinutes) * 0.9);
            $p90Delay = isset($delayMinutes[$p90Index]) ? $delayMinutes[$p90Index] : 0;

            return [
                'delays' => $delays,
                'summary' => [
                    'avg_delay' => round($avgDelay, 2),
                    'p90_delay' => $p90Delay,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}

