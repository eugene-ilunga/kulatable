<?php

namespace Modules\Whatsapp\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Facades\Module;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Whatsapp';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
        $this->map();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        // Always register web routes if file exists (webhooks need to work even if module is disabled)
        $routesPath = module_path($this->name, '/Routes/web.php');
        if (!file_exists($routesPath)) {
            return;
        }

        Route::middleware('web')->group($routesPath);
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        // Only register routes if module is enabled
        // Use Module facade directly to avoid issues during service provider boot
        try {
            if (!Module::has('Whatsapp') || !Module::isEnabled('Whatsapp')) {
                return;
            }
        } catch (\Exception $e) {
            // If Module facade is not available yet, skip registration
            return;
        }

        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/Routes/api.php'));
    }
}
