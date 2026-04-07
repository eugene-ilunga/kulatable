<?php

namespace Modules\RestApi\Providers;

use App\Events\NewOrderCreated;
use App\Events\OrderCancelled;
use App\Events\OrderUpdated;
use Livewire\Livewire;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\RestApi\Listeners\SendOrderAssignedToPartnerNotification;
use Modules\RestApi\Listeners\SendOrderCancelledToPartnerNotification;
use Modules\RestApi\Listeners\SendOrderReadyForPickupNotification;
use Modules\RestApi\Support\Installer;
use Modules\RestApi\Livewire\SuperAdmin\Setting as SuperAdminSetting;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ApplicationIntegrationServiceProvider extends ServiceProvider
{
    use PathNamespace;

    // Module folder name
    protected string $name = 'RestApi';
    // Primary namespace key (matches module name)
    protected string $nameLower = 'restapi';
    // Legacy alias for backward compatibility (old module name)
    protected string $legacyAlias = 'applicationintegration';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerDocsTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerRoutes();
        Installer::run();

        $this->registerEventListeners();

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);
        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->legacyAlias);

        Livewire::component($this->nameLower . '::super-admin.setting', SuperAdminSetting::class);
        Livewire::component($this->name . '::super-admin.setting', SuperAdminSetting::class);
        Livewire::component($this->legacyAlias . '::super-admin.setting', SuperAdminSetting::class);
    }

    public function register(): void {}

    protected function registerEventListeners(): void
    {
        Event::listen(NewOrderCreated::class, SendOrderAssignedToPartnerNotification::class);
        Event::listen(OrderUpdated::class, SendOrderAssignedToPartnerNotification::class);
        Event::listen(OrderCancelled::class, SendOrderCancelledToPartnerNotification::class);
        Event::listen(OrderUpdated::class, SendOrderReadyForPickupNotification::class);
    }

    protected function registerRoutes(): void
    {
        $apiRoutes = module_path($this->name, 'Routes/api.php');
        if (file_exists($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }

        $webRoutes = module_path($this->name, 'Routes/web.php');
        if (file_exists($webRoutes)) {
            $this->loadRoutesFrom($webRoutes);
        }
    }

    public function registerTranslations(): void
    {
        $langPath = function_exists('lang_path')
            ? lang_path('modules/' . $this->legacyAlias)
            : resource_path('lang/modules/' . $this->legacyAlias);

        $sourceLang = module_path($this->name, 'Resources/lang');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->legacyAlias);
            $this->loadJsonTranslationsFrom($langPath);
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom($sourceLang, $this->legacyAlias);
            $this->loadJsonTranslationsFrom($sourceLang);
            $this->loadTranslationsFrom($sourceLang, $this->nameLower);
            $this->loadJsonTranslationsFrom($sourceLang);
        }
    }

    public function registerDocsTranslations(): void
    {
        $alias = $this->legacyAlias . '-docs';
        $langPath = function_exists('lang_path')
            ? lang_path('modules/' . $alias)
            : resource_path('lang/modules/' . $alias);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $alias);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'Resources/lang'), $alias);
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
        // Publish under primary name
        $viewPath = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'Resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower . '-module-views']);

        // Load views under both namespaces (restapi:: and applicationintegration::)
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
        $this->loadViewsFrom($sourcePath, $this->legacyAlias);
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
