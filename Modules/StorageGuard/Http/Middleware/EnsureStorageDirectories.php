<?php

namespace Modules\StorageGuard\Http\Middleware;

use Closure;
use Modules\StorageGuard\Services\PathEnsurer;

class EnsureStorageDirectories
{
    public function __construct(private PathEnsurer $pathEnsurer)
    {
    }

    public function handle($request, Closure $next)
    {
        $this->pathEnsurer->ensureBasePaths();

        return $next($request);
    }
}
