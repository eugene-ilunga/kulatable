<?php

namespace Modules\FontControl\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Livewire\Livewire;
use Modules\FontControl\Http\Middleware\ApplyFontPreferences;
use Modules\FontControl\Services\TableQrRegenerator;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FontControlServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'FontControl';

    protected string $nameLower = 'fontcontrol';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
        // Schema setup moved to migrations - no longer checking on every request
        $this->ensureCacheDirectories();

        // Ensure the module appears in the cached custom module list

        if (function_exists('module_enabled') && module_enabled($this->name)) {
            $router = $this->app['router'];
            $router->aliasMiddleware('fontcontrol.inject', ApplyFontPreferences::class);
            $this->app->booted(function () use ($router) {
                $router->pushMiddlewareToGroup('web', ApplyFontPreferences::class);
            });

            // ⚠️ QR regeneration disabled on boot to prevent unnecessary regeneration
            // QR codes will regenerate ONLY when:
            // 1. Settings are changed via FontControl UI (calls forceRun())
            // 2. Manually triggered via artisan command

            // TableQrRegenerator::runOnce();
        }

        Livewire::component('fontcontrol::restaurant.setting', \Modules\FontControl\Livewire\Restaurant\Setting::class);
        Livewire::component('FontControl::restaurant.setting', \Modules\FontControl\Livewire\Restaurant\Setting::class);
        Livewire::component('FontControl::super-admin.setting', \Modules\FontControl\Livewire\Restaurant\Setting::class);
        Livewire::component('fontcontrol::super-admin.setting', \Modules\FontControl\Livewire\Restaurant\Setting::class);

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    /**
     * Register the service provider.
     */
    public function register(): void {}

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

                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
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

    /**
     * Prevent cache write failures by ensuring cache path exists.
     */
    private function ensureCacheDirectories(): void
    {
        $paths = [
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
        ];

        foreach ($paths as $path) {
            try {
                File::ensureDirectoryExists($path, 0775, true);
            } catch (\Throwable $e) {
                // ignore; StorageGuard middleware may also create these
            }
        }
    }
}
