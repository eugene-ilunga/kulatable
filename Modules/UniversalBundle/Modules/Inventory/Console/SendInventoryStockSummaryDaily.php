<?php

namespace Modules\Inventory\Console;

use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Inventory\Entities\BatchStock;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\InventorySetting;
use Modules\Inventory\Notifications\DailyStockSummary;

class SendInventoryStockSummaryDaily extends Command
{
    protected $signature = 'inventory:send-stock-summary-daily';

    protected $description = 'Send inventory stock summary emails daily per restaurant';

    public function handle()
    {
        $this->info('Sending daily inventory stock summary emails...');

        $restaurants = Restaurant::select('id', 'name', 'timezone')->with('branches')->get();

        if ($restaurants->isEmpty()) {
            $this->warn('No restaurants found.');

            return Command::SUCCESS;
        }

        foreach ($restaurants as $restaurant) {
            $settings = InventorySetting::where('restaurant_id', $restaurant->id)->first();

            if (! $settings || ! $settings->send_stock_summary_email) {
                $this->info("Skipping restaurant {$restaurant->name} (ID: {$restaurant->id}) - stock summary email disabled.");
                continue;
            }

            $tz = $restaurant->timezone ?? config('app.timezone');
            $nowTz = Carbon::now($tz);

            $this->info("Checking restaurant: {$restaurant->name} (ID: {$restaurant->id})");
            $this->info("  Current time (TZ: {$tz}): {$nowTz->format('Y-m-d H:i:s')}");

            $cacheKey = 'inventory_stock_summary_sent_'.$restaurant->id.'_'.$nowTz->toDateString();

            if (cache()->has($cacheKey)) {
                $this->warn('  Skipping: Already sent today (cached flag)');
                continue;
            }

            $this->info('  Sending stock summary email...');

            try {
                $this->sendForRestaurant($restaurant);
                cache()->put($cacheKey, true, $nowTz->copy()->endOfDay());
                $this->info('  ✓ Email sent successfully');
            } catch (\Exception $e) {
                $this->error('  ✗ Error sending email: '.$e->getMessage());
                $this->error('  Stack trace: '.$e->getTraceAsString());
            }
        }

        return Command::SUCCESS;
    }

    private function sendForRestaurant(Restaurant $restaurant): void
    {
        $notifiable = Restaurant::restaurantAdmin($restaurant) ?? $restaurant->users()->first();

        if (! $notifiable) {
            $this->error("  ✗ No notifiable user found for restaurant ID: {$restaurant->id}");

            return;
        }

        if (empty($notifiable->email)) {
            $this->error("  ✗ User '{$notifiable->name}' (ID: {$notifiable->id}) has no email address");

            return;
        }

        $summary = $this->buildSummary($restaurant);

        $totalAttentionItems = $summary['totals']['low_stock']
            + $summary['totals']['out_of_stock']
            + $summary['totals']['expiring_batches'];

        if ($totalAttentionItems === 0) {
            $this->info('  No low stock, out-of-stock, or expiring items for this restaurant; skipping email.');

            return;
        }

        $this->info("  Notifying user: {$notifiable->name} ({$notifiable->email})");

        try {
            $notifiable->notify(new DailyStockSummary($restaurant, $summary));
        } catch (\Exception $e) {
            $this->error('  ✗ Notification failed: '.$e->getMessage());
            throw $e;
        }
    }

    private function buildSummary(Restaurant $restaurant): array
    {
        $branches = $restaurant->branches()->get();

        if ($branches->isEmpty()) {
            return [
                'branches' => [],
                'totals' => [
                    'low_stock' => 0,
                    'out_of_stock' => 0,
                    'expiring_batches' => 0,
                ],
                'warning_days' => config('inventory.batch_expiry_warning_days', 3),
            ];
        }

        $branchIds = $branches->pluck('id');

        $summary = [
            'branches' => [],
            'totals' => [
                'low_stock' => 0,
                'out_of_stock' => 0,
                'expiring_batches' => 0,
            ],
            'warning_days' => config('inventory.batch_expiry_warning_days', 3),
        ];

        foreach ($branches as $branch) {
            $summary['branches'][$branch->id] = [
                'branch' => $branch,
                'low_stock' => [],
                'out_of_stock' => [],
                'expiring_batches' => [],
            ];
        }

        $items = InventoryItem::with(['unit', 'category', 'stocks'])
            ->whereIn('branch_id', $branchIds)
            ->get();

        foreach ($items as $item) {
            $branchId = $item->branch_id;
            $currentStock = $item->current_stock;

            if (! isset($summary['branches'][$branchId])) {
                continue;
            }

            if ($currentStock <= 0) {
                $summary['branches'][$branchId]['out_of_stock'][] = $item;
                $summary['totals']['out_of_stock']++;
            } elseif ($currentStock <= $item->threshold_quantity) {
                $summary['branches'][$branchId]['low_stock'][] = $item;
                $summary['totals']['low_stock']++;
            }
        }

        $warningDays = $summary['warning_days'] ?? 3;
        $now = now();
        $cutoff = (clone $now)->addDays($warningDays);

        $batches = BatchStock::with('batchRecipe')
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$now->toDateString(), $cutoff->toDateString()])
            ->get();

        foreach ($batches as $batch) {
            if ($batch->remaining_quantity <= 0) {
                continue;
            }

            $branchId = $batch->branch_id;

            if (! isset($summary['branches'][$branchId])) {
                continue;
            }

            $summary['branches'][$branchId]['expiring_batches'][] = $batch;
            $summary['totals']['expiring_batches']++;
        }

        return $summary;
    }
}

