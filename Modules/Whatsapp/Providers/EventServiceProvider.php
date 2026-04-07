<?php

namespace Modules\Whatsapp\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        // Order Events
        \App\Events\NewOrderCreated::class => [
            \Modules\Whatsapp\Listeners\SendOrderConfirmationListener::class,
        ],
        \App\Events\OrderUpdated::class => [
            \Modules\Whatsapp\Listeners\SendOrderStatusUpdateListener::class,
        ],
        \App\Events\OrderCancelled::class => [
            \Modules\Whatsapp\Listeners\SendOrderCancelledListener::class,
        ],
        \App\Events\SendOrderBillEvent::class => [
            \Modules\Whatsapp\Listeners\SendOrderBillListener::class,
            \Modules\Whatsapp\Listeners\SendKitchenNotificationOnBillListener::class,
        ],
        \App\Events\OrderTableAssigned::class => [
            \Modules\Whatsapp\Listeners\SendTableAssignmentListener::class,
        ],
        \App\Events\OrderWaiterAssigned::class => [
            \Modules\Whatsapp\Listeners\SendWaiterAssignmentListener::class,
        ],
        
        // Reservation Events
        \App\Events\ReservationReceived::class => [
            \Modules\Whatsapp\Listeners\SendReservationReceivedListener::class,
        ],
        \App\Events\ReservationConfirmationSent::class => [
            \Modules\Whatsapp\Listeners\SendReservationConfirmationListener::class,
        ],
        \App\Events\ReservationStatusUpdated::class => [
            \Modules\Whatsapp\Listeners\SendReservationStatusUpdateListener::class,
        ],
        \App\Events\ReservationTableAssigned::class => [
            \Modules\Whatsapp\Listeners\SendReservationTableAssignedListener::class,
        ],

        // Waiter Events
        \App\Events\ActiveWaiterRequestCreatedEvent::class => [
            \Modules\Whatsapp\Listeners\SendWaiterRequestListener::class,
        ],
        \App\Events\NotifyWaiter::class => [
            \Modules\Whatsapp\Listeners\SendNotifyWaiterListener::class,
        ],

        // KOT Events
        \App\Events\KotUpdated::class => [
            \Modules\Whatsapp\Listeners\SendKotNotificationListener::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Only register listeners if module is enabled
        try {
            if (Module::has('Whatsapp') && Module::isEnabled('Whatsapp')) {
                parent::boot();
            }
        } catch (\Exception $e) {
            // If Module facade is not available, still try to boot
            // The listeners will check module status themselves
            parent::boot();
        }
    }

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
