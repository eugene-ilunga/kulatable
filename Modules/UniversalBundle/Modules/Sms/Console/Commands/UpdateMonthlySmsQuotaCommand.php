<?php
namespace Modules\Sms\Console\Commands;
use App\Models\Restaurant;
use App\Models\Package;
use App\Models\GlobalSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
class UpdateMonthlySmsQuotaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:update-monthly-quota';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update monthly SMS quota for restaurants based on their subscription dates';
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $this->info("Starting monthly SMS quota update at {$today->format('Y-m-d H:i:s')}");
        // Fetch all active subscriptions with related data
        $subscriptions = GlobalSubscription::with(['restaurant.package'])
            ->where('subscription_status', 'active')
            ->whereNotNull('subscribed_on_date')
            ->get();
        $updatedCount = 0;
        $skippedCount = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $restaurant = $subscription->restaurant;
                // Skip if restaurant or package missing
                if (!$restaurant || !$restaurant->package) {
                    $this->warn("Skipping subscription ID {$subscription->id}: missing restaurant or package");
                    $skippedCount++;
                    continue;
                }
                $package = $restaurant->package;
                $subscribedDate = Carbon::parse($subscription->subscribed_on_date)->startOfDay();
                $nextResetDate = $subscribedDate->copy();

                // Keep adding one month until the nextResetDate >= today
                while ($nextResetDate->lessThan($today)) {
                    $nextResetDate->addMonth();
                }
                // Only reset if today matches the monthly anniversary
                if ($nextResetDate->isSameDay($today)) {
                    $this->info("Updating SMS quota for Restaurant ID {$restaurant->id}");
                    $newTotalSms = $this->calculateNewSmsQuota($restaurant, $package);
                    // Skip if unlimited
                    if ($newTotalSms === -1) {
                        $this->warn("Restaurant ID {$restaurant->id} has unlimited SMS, skipping.");
                        $skippedCount++;
                        continue;
                    }
                    // Update restaurant SMS quota
                    $restaurant->update([
                        'total_sms' => $newTotalSms,
                        'count_sms' => 0, // reset usage
                    ]);
                    $this->info("→ Updated: total_sms = {$newTotalSms}, count_sms = 0");
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Error updating subscription ID {$subscription->id}: " . $e->getMessage());
                $skippedCount++;
            }
        }
        $this->info("Monthly SMS quota update complete!");
        $this->info("Total updated: {$updatedCount}, Total skipped: {$skippedCount}");
        Log::info('Monthly SMS quota update completed', [
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'timestamp' => now(),
        ]);
        return Command::SUCCESS;
    }
    /**
     * Calculate new SMS quota for a restaurant based on package settings
     *
     * @param Restaurant $restaurant
     * @param Package $package
     * @return int
     */
    private function calculateNewSmsQuota(Restaurant $restaurant, Package $package): int
    {
        $packageSmsCount = $package->sms_count ?? 0;
        $currentTotal = $restaurant->total_sms ?? 0;
        // Skip if unlimited values are present
        if ($packageSmsCount == -1 || $currentTotal == -1) {
            return -1; // Return -1 to indicate unlimited
        }
        // Carry forward enabled
        if ($package->carry_forward_sms) {
            return $currentTotal + $packageSmsCount;
        }
        // Carry forward disabled → reset to package value
        return $packageSmsCount;
    }
}