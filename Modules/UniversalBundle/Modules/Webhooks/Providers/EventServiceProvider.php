<?php

namespace Modules\Webhooks\Providers;

use App\Events\NewOrderCreated;
use App\Events\OrderUpdated;
use App\Events\ReservationConfirmationSent;
use App\Events\ReservationReceived;
use App\Events\OrderSuccessEvent;
use App\Events\SendOrderBillEvent;
use App\Events\NewRestaurantCreatedEvent;
use App\Events\PaymentSuccess;
use App\Events\PaymentFailed;
use App\Events\OrderCancelled;
use App\Events\PrintJobCreated;
use App\Events\KotUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Webhooks\Listeners\SendWebhookNotifications;
use Modules\Webhooks\Listeners\PaymentWebhookListener;
use Modules\Webhooks\Listeners\BroadcastWebhookEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        NewOrderCreated::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        OrderUpdated::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        ReservationReceived::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        ReservationConfirmationSent::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        OrderSuccessEvent::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        SendOrderBillEvent::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        NewRestaurantCreatedEvent::class => [
            SendWebhookNotifications::class,
        ],
        PaymentSuccess::class => [
            PaymentWebhookListener::class,
        ],
        PaymentFailed::class => [
            PaymentWebhookListener::class,
        ],
        OrderCancelled::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        PrintJobCreated::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
        KotUpdated::class => [
            SendWebhookNotifications::class,
            BroadcastWebhookEvent::class,
        ],
    ];
}
