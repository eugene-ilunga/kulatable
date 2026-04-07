<?php

namespace Modules\MultiPOS\Listeners;

use Modules\MultiPOS\Events\PosMachineRegistrationRequested;
use Modules\MultiPOS\Notifications\PosMachineRegistrationRequest;
use App\Models\User;
use App\Scopes\BranchScope;
use App\Http\Controllers\DashboardController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendPosMachineRegistrationNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PosMachineRegistrationRequested $event): void
    {
        $posMachine = $event->posMachine;
        $restaurant = $posMachine->branch->restaurant;

        // Get all admin users for this restaurant
        $adminUsers = User::role('Admin_' . $restaurant->id)
            ->where('restaurant_id', $restaurant->id)
            ->withoutGlobalScope(BranchScope::class)
            ->get();

        if ($adminUsers->isEmpty()) {
            return;
        }

        try {
            // Send email and database notifications
            foreach ($adminUsers as $admin) {
                try {
                    $notification = new PosMachineRegistrationRequest($posMachine);
                    $admin->notify($notification);

                } catch (\Exception $e) {
                }
            }

            // Send push notifications
            try {
                $pushNotification = new DashboardController();
                $pushUsersIds = [$adminUsers->pluck('id')->toArray()];
                $settingsUrl = route('settings.index') . '?tab=multipos';

                $pushNotification->sendPushNotifications(
                    $pushUsersIds,
                    __('multipos::messages.notifications.pos_request.push_title'),
                    __('multipos::messages.notifications.pos_request.push_message', [
                        'alias' => $posMachine->alias ?? __('multipos::messages.table.no_alias'),
                        'branch' => $posMachine->branch->name
                    ]),
                    $settingsUrl
                );

            } catch (\Exception $e) {
            }
        } catch (\Exception $e) {
        }
    }
}
