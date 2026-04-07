<?php

namespace Modules\Webhooks\Support;

use Illuminate\Notifications\Events\NotificationSent;

class NotificationPayloadFactory
{
    /**
        * @return array{0:string,1:int|null,2:int|null,3:array,4:string}|null
        */
    public static function from(object $event): ?array
    {
        // Basic notification wiring: if notification has restaurant_id/branch_id
        if ($event instanceof NotificationSent) {
            $notifiable = $event->notifiable;
            $restaurantId = $notifiable->restaurant_id ?? null;
            $branchId = $notifiable->branch_id ?? null;

            return [
                'notification.created',
                $restaurantId,
                $branchId,
                [
                    'id' => method_exists($event->notification, 'id') ? $event->notification->id : null,
                    'type' => get_class($event->notification),
                    'channel' => $event->channel ?? null,
                    'notifiable_id' => $notifiable->id ?? null,
                    'created_at' => now()->toIso8601String(),
                ],
                'Notification',
            ];
        }

        return null;
    }
}
