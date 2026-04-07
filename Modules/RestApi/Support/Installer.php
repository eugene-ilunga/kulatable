<?php

namespace Modules\RestApi\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Installer
{
    /**
     * Run module migrations/seeding idempotently after (re)enable.
     */
    public static function run(): void
    {
        $versionFile = module_path('RestApi', 'version.txt');
        $version = is_file($versionFile) ? trim((string) file_get_contents($versionFile)) : 'v1';
        $cacheKey = 'restapi_installed_' . $version;

        if (Cache::get($cacheKey)) {
            return;
        }

        try {
            Artisan::call('migrate', [
                '--path' => 'Modules/RestApi/Database/Migrations',
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('RestApi installer migration failed', [
                'error' => $e->getMessage(),
            ]);
        }

        Cache::put($cacheKey, true, now()->addDay());
    }
}

