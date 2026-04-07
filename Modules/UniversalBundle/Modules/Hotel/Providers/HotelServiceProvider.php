<?php

namespace Modules\Hotel\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use Modules\Hotel\Observers\OrderObserver;
use Illuminate\Support\Facades\Config;
use Modules\Hotel\Console\ActivateModuleCommand;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Events\NewBranchCreatedEvent;
use Modules\Hotel\Listeners\OrdertypeCreatedListener;

class HotelServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Hotel';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'hotel';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Hotel', 'Database/Migrations'));

        Event::listen(NewBranchCreatedEvent::class, OrdertypeCreatedListener::class);
        
        // Register Order Observer for room service integration
        if (module_enabled('Hotel')) {
            Order::observe(OrderObserver::class);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
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
        });
    }
    

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('Hotel', 'Config/config.php') => config_path('hotel.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('Hotel', 'Config/config.php'),
            'hotel'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/hotel');

        $sourcePath = module_path('Hotel', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/hotel';
        }, Config::get('view.paths')), [$sourcePath]), 'hotel');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/hotel');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'hotel');
        } else {
            $this->loadTranslationsFrom(module_path('Hotel', 'Resources/lang'), 'hotel');
        }
    }

    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }

}
