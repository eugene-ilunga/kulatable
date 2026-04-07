<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\SendOrderBillEvent;
use App\Models\Kot;
use Illuminate\Support\Facades\Log;
use Modules\Whatsapp\Jobs\SendKotNotificationJob;

class SendKitchenNotificationOnBillListener
{
    /**
     * Handle the event.
     */
    public function handle(SendOrderBillEvent $event): void
    {
        try {
            $order = $event->order;

            // Check if WhatsApp module is in restaurant's package
            $restaurantId = $order->branch->restaurant_id ?? null;
            if ($restaurantId && function_exists('restaurant_modules')) {
                $restaurant = $order->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        return;
                    }
                }
            }

            // Get all KOTs for this order
            $kots = Kot::where('order_id', $order->id)->get();

            if ($kots->isEmpty()) {
                Log::info("WhatsApp Bill Notification: No KOTs found for order #{$order->id}");
                return;
            }

            // Send kitchen notification for each KOT
            foreach ($kots as $kot) {
                // Check if KOT has items
                $totalItemsCount = $kot->items()->count();
                if ($totalItemsCount === 0) {
                    continue; // Skip KOTs without items
                }

                // Prevent duplicate notifications within short time window
                $jobDispatchedKey = 'kot_notification_job_dispatched_' . $kot->id;

                if (cache()->has($jobDispatchedKey)) {
                    $lastDispatched = cache()->get($jobDispatchedKey . '_time');
                    if ($lastDispatched && now()->diffInSeconds($lastDispatched) < 10) {
                        continue; // Too soon, skip
                    }
                }

                // Mark job as dispatched
                cache()->put($jobDispatchedKey, true, 300);
                cache()->put($jobDispatchedKey . '_time', now(), 300);

                // Dispatch a job synchronously to process the notification immediately
                SendKotNotificationJob::dispatch($kot->id)->delay(now()->addSeconds(8));
            }

        } catch (\Exception $e) {
        }
    }
}
