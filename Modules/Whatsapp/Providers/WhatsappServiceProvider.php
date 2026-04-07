<?php

namespace Modules\Whatsapp\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use Nwidart\Modules\Facades\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class WhatsappServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Whatsapp';

    protected string $nameLower = 'whatsapp';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        // Only register if module is enabled
        // Use Module facade directly to avoid issues during service provider boot
        try {
            if (!Module::has('Whatsapp') || !Module::isEnabled('Whatsapp')) {
                return;
            }
        } catch (\Exception $e) {
            // If Module facade is not available yet, skip registration
            return;
        }

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Always register RouteServiceProvider for webhook routes (webhooks need to work even if module is disabled)
        $this->app->register(RouteServiceProvider::class);

        // Only register EventServiceProvider if module is enabled
        try {
            if (Module::has('Whatsapp') && Module::isEnabled('Whatsapp')) {
                $this->app->register(EventServiceProvider::class);
            }
        } catch (\Exception $e) {
            // If Module facade is not available yet, try to register anyway
            // The EventServiceProvider will handle checking if module is enabled
            try {
                $this->app->register(EventServiceProvider::class);
            } catch (\Exception $e2) {
                // If registration still fails, skip silently
            }
        }
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Whatsapp\Console\ActivateModuleCommand::class,
            \Modules\Whatsapp\Console\Commands\ProcessAutomatedSchedulesCommand::class,
            \Modules\Whatsapp\Console\Commands\ProcessReportSchedulesCommand::class,
            \Modules\Whatsapp\Console\Commands\ProcessPaymentRemindersCommand::class,
            \Modules\Whatsapp\Console\Commands\ProcessReservationRemindersCommand::class,
            \Modules\Whatsapp\Console\Commands\ProcessReservationFollowupsCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // WhatsApp scheduled messages and reports
        // These are registered here instead of routes/console.php to keep module code self-contained
        $this->app->booted(function () {
            Schedule::command('whatsapp:process-automated-schedules')->everyFiveMinutes();
            Schedule::command('whatsapp:process-report-schedules')->everyFiveMinutes();

            // Payment reminders - run daily at scheduled time
            Schedule::command('whatsapp:process-payment-reminders')->daily();

            // Reservation reminders - run hourly to catch reservations 2 hours before (checks schedule time but processes all matching reservations)
            Schedule::command('whatsapp:process-reservation-reminders')->hourly();

            // Reservation follow-ups - run daily at scheduled time
            Schedule::command('whatsapp:process-reservation-followups')->daily();
        });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'Resources/lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'Resources/lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower . '.' . $config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'Resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);

        // Register Livewire components
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('whatsapp::superadmin.whatsapp-settings', \Modules\Whatsapp\Livewire\Superadmin\WhatsAppSettings::class);
            \Livewire\Livewire::component('whatsapp::restaurant.whatsapp-notification-settings', \Modules\Whatsapp\Livewire\Restaurant\WhatsAppNotificationSettings::class);
            // Register with expected naming convention for custom_module_plugins pattern
            \Livewire\Livewire::component('whatsapp::restaurant.setting', \Modules\Whatsapp\Livewire\Restaurant\WhatsAppNotificationSettings::class);
            // Register for superadmin custom_module_plugins pattern
            \Livewire\Livewire::component('whatsapp::super-admin.setting', \Modules\Whatsapp\Livewire\Superadmin\WhatsAppSettings::class);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->nameLower)) {
                $paths[] = $path . '/modules/' . $this->nameLower;
            }
        }

        return $paths;
    }
}
