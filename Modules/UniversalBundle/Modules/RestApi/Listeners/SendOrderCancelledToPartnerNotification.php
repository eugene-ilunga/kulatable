<?php

namespace Modules\RestApi\Listeners;

use App\Events\OrderCancelled;
use Modules\RestApi\Entities\DeliveryPartnerDeviceToken;
use Modules\RestApi\Services\DeliveryPartnerNotificationService;
use Modules\RestApi\Services\FirebaseNotificationService;

class SendOrderCancelledToPartnerNotification
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
     * Handle the event: if order has a delivery executive, send "Order cancelled" FCM.
     */
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;

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
        $title = __('restapi::app.order_cancelled_title');
        $body = __('restapi::app.order_cancelled_body', ['order_number' => $orderNumber]);
        $payload = [
            'type' => 'order_cancelled',
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

        $this->notificationLogger->save($order, 'order_cancelled', $title, $body, $payload, $sent);
    }

}
