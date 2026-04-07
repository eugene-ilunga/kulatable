<?php

namespace Modules\Sms\Console;

use App\Models\Restaurant;
use Illuminate\Console\Command;
use Modules\Sms\Entities\SmsSetting;
use Illuminate\Support\Facades\Artisan;
use Modules\Sms\Entities\SmsGlobalSetting;
use Modules\Sms\Entities\SmsNotificationSetting;

class ActivateModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'sms:activate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all the module settings of sms module';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            Artisan::call('module:migrate Sms');
        } catch (\Exception $e) {
            // Silent exception
        }

        $restaurants = Restaurant::with('branches')->get();

        foreach ($restaurants as $restaurant) {
            // Create SMS Notification Settings if they don't exist
            $existingSettings = SmsNotificationSetting::where('restaurant_id', $restaurant->id)->exists();
            
            if (!$existingSettings) {
                $smsNotificationTypes = [
                    [
                        'type' => 'reservation_confirmed',
                        'send_sms' => 'no',
                        'restaurant_id' => $restaurant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'type' => 'order_bill_sent',
                        'send_sms' => 'no',
                        'restaurant_id' => $restaurant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'type' => 'send_otp',
                        'send_sms' => 'no',
                        'restaurant_id' => $restaurant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];

                SmsNotificationSetting::insert($smsNotificationTypes);
            }
        }

        $this->info('SMS Module activated successfully!');
    }
}
