<?php

namespace Modules\RestApi\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderUpdated;
use Modules\RestApi\Entities\DeliveryPartnerDeviceToken;
use Modules\RestApi\Services\DeliveryPartnerNotificationService;
use Modules\RestApi\Services\FirebaseNotificationService;

class SendOrderReadyForPickupNotification
{
    protected FirebaseNotificationService $fcm;

    protected DeliveryPartnerNotificationService $notificationLogger;

    public function __construct(
        FirebaseNotificationService $fcm,
        DeliveryPartnerNotificationService $notificationLogger
    )
    {
        $this->fcm = $fcm;
        $this->notificationLogger = $notificationLogger;
    }

    /**
     * Handle the event: when order status is READY_FOR_PICKUP and has delivery executive, send FCM.
     */
    public function handle(OrderUpdated $event): void
    {
        $order = $event->order;

        if ($order->order_status !== OrderStatus::FOOD_READY) {
            return;
        }

        if (! $order->delivery_executive_id) {
            return;
        }

        $tokens = DeliveryPartnerDeviceToken::where('delivery_executive_id', $order->delivery_executive_id)
            ->where('status', 'active')
            ->pluck('fcm_token')
            ->all();

        if (empty($tokens)) {
            return;
        }

        $orderNumber = $order->show_formatted_order_number ?? (string)$order->order_number ?? '';

        $title = __('restapi::app.order_ready_for_pickup_title');
        $body = __('restapi::app.order_ready_for_pickup_body', ['order_number' => $orderNumber]);
        $payload = [
            'type' => 'order_ready_for_pickup',
            'order_id' => (string)$order->id,
            'order_uuid' => $order->uuid ?? '',
            'order_number' => $orderNumber,
        ];

        $sent = $this->fcm->sendToTokens(
            $tokens,
            $title,
            $body,
            $payload
        );

        $this->notificationLogger->save($order, 'order_ready_for_pickup', $title, $body, $payload, $sent);
    }

}
