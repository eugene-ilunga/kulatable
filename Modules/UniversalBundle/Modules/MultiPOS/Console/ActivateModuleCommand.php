<?php

namespace Modules\MultiPOS\Console;

use App\Models\NotificationSetting;
use App\Models\Restaurant;
use Illuminate\Console\Command;

class ActivateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'multipos:activate';

    /**
     * The console command description.
     */
    protected $description = 'Activate multi pos module.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Add POS machine request notification settings for all existing restaurants
            $this->addPosMachineRequestNotificationSettings();
        } catch (\Exception $e) {
            $this->error('Failed to activate multi pos module: ' . $e->getMessage());
        }
    }

    /**
     * Add POS machine request notification settings for all restaurants
     */
    public static function addPosMachineRequestNotificationSettings(): void
    {
        $checkCount = Restaurant::count();

        if ($checkCount > 0) {
            $restaurants = Restaurant::select('id')->get();

            foreach ($restaurants as $restaurant) {
                // Check if notification setting already exists to avoid duplicates
                $existing = NotificationSetting::where('type', 'pos_machine_request')
                    ->where('restaurant_id', $restaurant->id)
                    ->first();

                if (!$existing) {
                    NotificationSetting::create([
                        'type' => 'pos_machine_request',
                        'send_email' => 1,
                        'restaurant_id' => $restaurant->id
                    ]);
                }
            }
        }
    }
}
