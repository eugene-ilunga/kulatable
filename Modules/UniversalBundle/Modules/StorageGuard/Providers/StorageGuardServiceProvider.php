<?php

namespace Modules\StorageGuard\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Modules\StorageGuard\Http\Middleware\EnsureStorageDirectories;
use Modules\StorageGuard\Services\PathEnsurer;
use Modules\StorageGuard\Support\SafeFilesystem;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StorageGuardServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'StorageGuard';
    protected string $nameLower = 'storageguard';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();

        Cache::forget('custom_module_plugins');

        if (function_exists('module_enabled') && module_enabled($this->name)) {
            // Ensure fresh instances pick up SafeFilesystem + extension.
            $this->app->forgetInstance('cache');
            $this->app->forgetInstance('cache.store');

            $router = $this->app['router'];
            $router->aliasMiddleware('storageguard.ensure', EnsureStorageDirectories::class);
            $this->app->booted(function () use ($router) {
                $router->pushMiddlewareToGroup('web', EnsureStorageDirectories::class);
            });

            $this->app->make(PathEnsurer::class)->ensureBasePaths();

            // Ensure the file cache store uses the SafeFilesystem even if it was resolved earlier.
            $this->app->afterResolving('cache.store', function ($store, $app) {
                if ($store instanceof \Illuminate\Cache\FileStore) {
                    $fs = $app->make(Filesystem::class);
                    $ref = new \ReflectionClass($store);
                    if ($ref->hasProperty('files')) {
                        $prop = $ref->getProperty('files');
                        $prop->setAccessible(true);
                        $prop->setValue($store, $fs);
                    }
                }
            });
        }

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);
    }

    public function register(): void
    {


        $this->app->singleton(Filesystem::class, function () {
            return new SafeFilesystem();
        });

        $this->app->alias(Filesystem::class, 'files');
        $this->app->forgetInstance('files');
        $this->app->forgetInstance(Filesystem::class);

        // Force the file cache driver to use SafeFilesystem while keeping Laravel semantics.
        $this->app->extend('cache', function ($cacheManager, $app) {
            $cacheManager->extend('file', function ($app) {
                $fs = $app->make(Filesystem::class);
                $path = $app['config']['cache.stores.file.path'] ?? storage_path('framework/cache/data');
                $store = new \Illuminate\Cache\FileStore($fs, $path);

                return new \Illuminate\Cache\Repository($store);
            });

            return $cacheManager;
        });
    }

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

    protected function registerRoutes(): void
    {
        if (function_exists('module_enabled') && module_enabled($this->name)) {
            $routePath = module_path($this->name, 'Routes/web.php');
            if (file_exists($routePath)) {
                $this->loadRoutesFrom($routePath);
            }
        }
    }
}
