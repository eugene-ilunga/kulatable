<?php

namespace Modules\RestApi\Services;

use App\Models\Order;
use Modules\RestApi\Entities\DeliveryPartnerNotification;

class DeliveryPartnerNotificationService
{
    /**
     * Persist a delivery partner notification log in database.
     *
     * @param  array<string, mixed> $data
     */

    public function save(
        Order $order,
        string $notificationType,
        string $title,
        string $body,
        array $data = [],
        bool $isSent = false
    ): DeliveryPartnerNotification
    {
        $order->loadMissing('deliveryExecutive');

        return DeliveryPartnerNotification::create([
            'delivery_executive_id' => $order->delivery_executive_id,
            'delivery_executive_code' => $order->deliveryExecutive?->unique_code,
            'order_id' => $order->id,
            'notification_type' => $notificationType,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'is_sent' => $isSent,
            'sent_at' => $isSent ? now() : null,
        ]);
    }

}

