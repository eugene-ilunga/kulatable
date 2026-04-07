<?php

namespace Modules\Webhooks\Listeners;

use Modules\Webhooks\Events\WebhookEventOccurred;
use Modules\Webhooks\Support\EventPayloadFactory;

class BroadcastWebhookEvent
{
    /**
     * Handle the event and broadcast it to connected Flutter clients.
     */
    public function handle(object $event): void
    {
        // Convert event to webhook payload format
        $payload = EventPayloadFactory::from($event);
        
        if (!$payload) {
            return; // Event not supported for webhooks
        }

        [$eventName, $restaurantId, $branchId, $data, $source] = $payload;

        // Only broadcast if we have a restaurant ID
        if (!$restaurantId) {
            return;
        }

        // Broadcast the event to connected Flutter clients
        event(new WebhookEventOccurred(
            $eventName,
            $restaurantId,
            $branchId,
            $data
        ));
    }
}
