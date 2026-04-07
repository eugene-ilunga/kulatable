<?php

namespace Modules\Sms\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use Modules\Sms\Http\Traits\SmsSettingTrait;
use Illuminate\Support\Facades\Schema;
use Modules\Sms\Listeners\SmsReservationConfirmationListener;
use App\Events\ReservationConfirmationSent;
use Illuminate\Support\Facades\Event;
use Modules\Sms\Listeners\SendOrderBillListener;
use App\Events\SendOrderBillEvent;
use Illuminate\Support\Facades\Notification;
use Modules\Sms\Channels\Msg91Channel;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Sms\Listeners\SendOtpListener;
use App\Events\SendOtpEvent;
use Modules\Sms\Console\Commands\UpdateMonthlySmsQuotaCommand;
use Modules\Sms\Console\ActivateModuleCommand;
use Illuminate\Console\Scheduling\Schedule;
use App\Events\NewRestaurantCreatedEvent;
use Modules\Sms\Listeners\CreateSmsOnRestaurantCreatedListener;

class SmsServiceProvider extends ServiceProvider
{
    use PathNamespace;
    use SmsSettingTrait;

    protected string $name = 'Sms';

    protected string $nameLower = 'sms';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Sms', 'Database/Migrations'));

        Event::listen(NewRestaurantCreatedEvent::class, CreateSmsOnRestaurantCreatedListener::class);
        Event::listen(ReservationConfirmationSent::class, SmsReservationConfirmationListener::class);
        Event::listen(SendOrderBillEvent::class, SendOrderBillListener::class);
        Event::listen(SendOtpEvent::class, SendOtpListener::class);

        try {
            if (Schema::hasTable('sms_global_settings')) {
                $this->setConfig();
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        // Removed MSG91 package registration
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            UpdateMonthlySmsQuotaCommand::class,
            ActivateModuleCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('sms:update-monthly-quota')->dailyAt('00:01');
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/sms');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'sms');
        } else {
            $this->loadTranslationsFrom(module_path('Sms', 'Resources/lang'), 'sms');
        }
    }

    protected function registerConfig()
    {
        $this->publishes([
            module_path('Sms', 'Config/config.php') => config_path('sms.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('Sms', 'Config/config.php'),
            'sms'
        );
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/sms');

        $sourcePath = module_path('Sms', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/sms';
        }, Config::get('view.paths')), [$sourcePath]), 'sms');
    }

    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
