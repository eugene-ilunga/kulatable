<?php

namespace Modules\Webhooks\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Webhooks\Livewire\SuperAdmin\Sandbox;
use Modules\Webhooks\Livewire\Admin\WebhookManager;
use Modules\Webhooks\Livewire\SuperAdmin\Dashboard;
use Modules\Webhooks\Livewire\SuperAdmin\DeliveryDetail;
use Modules\Webhooks\Livewire\SuperAdmin\Setting;
use Modules\Webhooks\Livewire\SuperAdmin\RoutingMatrix;
use Modules\Webhooks\Livewire\SuperAdmin\PackageDefaults;
use Modules\Webhooks\Livewire\SuperAdmin\SystemWebhooks;
use Modules\Webhooks\Livewire\Restaurant\Setting as RestaurantSetting;
use Modules\Webhooks\Policies\WebhookPolicy;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\Webhooks\Console\Commands\ProvisionPackageWebhooks;

class WebhooksServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Webhooks';
    protected string $nameLower = 'webhooks';

    public function boot(): void
    {
        $this->registerViewOverrides();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));

        // Ensure module list cache reflects the new module
        Cache::forget('custom_module_plugins');

        Blade::componentNamespace(config('modules.namespace') . '\\' . $this->name . '\\View\\Components', $this->nameLower);

        // Register Livewire components only when class exists to avoid autoload issues during install
        if (class_exists(Sandbox::class)) {
            Livewire::component($this->nameLower . '::super-admin.sandbox', Sandbox::class);
            Livewire::component($this->name . '::super-admin.sandbox', Sandbox::class);
        }
        if (class_exists(WebhookManager::class)) {
            Livewire::component($this->nameLower . '::admin.webhook-manager', WebhookManager::class);
            Livewire::component($this->name . '::admin.webhook-manager', WebhookManager::class);
        }
        if (class_exists(Dashboard::class)) {
            Livewire::component($this->nameLower . '::super-admin.dashboard', Dashboard::class);
            Livewire::component($this->name . '::super-admin.dashboard', Dashboard::class);
        }
        if (class_exists(DeliveryDetail::class)) {
            Livewire::component($this->nameLower . '::super-admin.delivery-detail', DeliveryDetail::class);
            Livewire::component($this->name . '::super-admin.delivery-detail', DeliveryDetail::class);
        }
        if (class_exists(Setting::class)) {
            Livewire::component($this->nameLower . '::super-admin.setting', Setting::class);
            Livewire::component($this->name . '::super-admin.setting', Setting::class);
        }
        if (class_exists(RoutingMatrix::class)) {
            Livewire::component($this->nameLower . '::super-admin.routing-matrix', RoutingMatrix::class);
            Livewire::component($this->name . '::super-admin.routing-matrix', RoutingMatrix::class);
        }
        if (class_exists(PackageDefaults::class)) {
            Livewire::component($this->nameLower . '::super-admin.package-defaults', PackageDefaults::class);
            Livewire::component($this->name . '::super-admin.package-defaults', PackageDefaults::class);
        }
        if (class_exists(SystemWebhooks::class)) {
            Livewire::component($this->nameLower . '::super-admin.system-webhooks', SystemWebhooks::class);
            Livewire::component($this->name . '::super-admin.system-webhooks', SystemWebhooks::class);
        }
        if (class_exists(RestaurantSetting::class)) {
            Livewire::component($this->nameLower . '::restaurant.setting', RestaurantSetting::class);
            Livewire::component($this->name . '::restaurant.setting', RestaurantSetting::class);
        }

        $this->registerPolicies();

        // Ensure the restaurant settings component registration wins after all providers boot.
        $this->app->booted(function () {
            if (class_exists(RestaurantSetting::class)) {
                Livewire::component($this->nameLower . '::restaurant.setting', RestaurantSetting::class);
                Livewire::component($this->name . '::restaurant.setting', RestaurantSetting::class);
            }
        });
    }

    public function register(): void
    {

        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->commands([
            ProvisionPackageWebhooks::class,
        ]);
    }

    public function registerTranslations(): void
    {
        $langPath = function_exists('lang_path')
            ? lang_path('modules/' . $this->nameLower)
            : resource_path('lang/modules/' . $this->nameLower);

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

    private function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\Modules\Webhooks\Entities\Webhook::class, WebhookPolicy::class);
    }

    private function registerViewOverrides(): void
    {
        $overridePath = __DIR__ . '/../Resources/overrides';
        if (is_dir($overridePath)) {
            View::prependLocation($overridePath);
        }
    }
}
