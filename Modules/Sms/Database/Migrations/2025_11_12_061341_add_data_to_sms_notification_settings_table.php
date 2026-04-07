<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $restaurants = DB::table('restaurants')->get();
        foreach ($restaurants as $restaurant) {
            $types = ['reservation_confirmed', 'order_bill_sent', 'send_otp'];
            foreach ($types as $type) {
                $exists = DB::table('sms_notification_settings')
                    ->where('restaurant_id', $restaurant->id)
                    ->where('type', $type)
                    ->exists();
                if (! $exists) {
                    DB::table('sms_notification_settings')->insert([
                        'type' => $type,
                        'send_sms' => 'no',
                        'restaurant_id' => $restaurant->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sms_notification_settings')
            ->whereIn('type', ['reservation_confirmed', 'order_bill_sent', 'send_otp'])
            ->delete();
    }
};
