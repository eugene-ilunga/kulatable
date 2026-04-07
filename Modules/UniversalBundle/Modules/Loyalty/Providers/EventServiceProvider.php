<?php

namespace Modules\Loyalty\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Nwidart\Modules\Facades\Module;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \App\Events\SendNewOrderReceived::class => [
            \Modules\Loyalty\Listeners\EarnPointsOnOrderCompletionListener::class,
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
            if (Module::has('Loyalty') && Module::isEnabled('Loyalty')) {
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
