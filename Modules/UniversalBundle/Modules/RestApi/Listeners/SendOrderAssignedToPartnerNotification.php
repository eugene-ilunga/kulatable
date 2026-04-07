<?php

namespace Modules\RestApi\Listeners;

use App\Events\NewOrderCreated;
use App\Events\OrderUpdated;
use Modules\RestApi\Entities\DeliveryPartnerDeviceToken;
use Modules\RestApi\Entities\DeliveryPartnerNotification;
use Modules\RestApi\Services\DeliveryPartnerNotificationService;
use Modules\RestApi\Services\FirebaseNotificationService;

class SendOrderAssignedToPartnerNotification
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

    public function handle(NewOrderCreated|OrderUpdated $event): void
    {
        $order = $event->order;
        $order->loadMissing(['orderType', 'deliveryExecutive']);

        $slugOrType = strtolower((string)($order->order_type ?? $order->orderType?->slug ?? $order->orderType?->type ?? ''));
        if ($slugOrType !== 'delivery') {
            return;
        }

        // For updates, only notify when delivery executive is actually changed.
        if ($event instanceof OrderUpdated) {
            $changes = $order->getChanges();
            if (! array_key_exists('delivery_executive_id', $changes)) {
                return;
            }

            $previousExecutiveId = (int)($order->getOriginal('delivery_executive_id') ?? 0);
            $currentExecutiveId = (int)($changes['delivery_executive_id'] ?? 0);

            if ($previousExecutiveId === $currentExecutiveId) {
                return;
            }
        }

        if (! $order->delivery_executive_id || ! $order->deliveryExecutive) {
            return;
        }

        $alreadySentForExecutive = DeliveryPartnerNotification::query()
            ->where('order_id', $order->id)
            ->where('delivery_executive_id', $order->delivery_executive_id)
            ->where('notification_type', 'order_assigned')
            ->exists();

        if ($alreadySentForExecutive) {
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
        $title = __('restapi::app.order_assigned_title');
        $body = __('restapi::app.order_assigned_body', ['order_number' => $orderNumber]);
        $payload = [
            'type' => 'order_assigned',
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

        $this->notificationLogger->save($order, 'order_assigned', $title, $body, $payload, $sent);
    }

}
