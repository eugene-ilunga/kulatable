<?php

namespace Modules\StorageGuard\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

/**
 * Filesystem override that ensures parent directories exist before writes.
 */
class SafeFilesystem extends Filesystem
{
    protected int $directoryMode;
    protected int $fileMode;

    public function __construct(int $directoryMode = 0775, int $fileMode = 0664)
    {
        $this->directoryMode = $directoryMode;
        $this->fileMode = $fileMode;
    }

    /**
     * Proactively create cache base path on construction (common 500 source).
     */
    protected function bootBaseCachePath(): void
    {
        $paths = [
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            storage_path('framework/sessions'),
            storage_path('logs'),
        ];

        foreach ($paths as $path) {
            if (! $this->isDirectory($path)) {
                $this->makeDirectory($path, $this->directoryMode, true, true);
                @chmod($path, $this->directoryMode);
            }
        }
    }

    public function put($path, $contents, $lock = false)
    {
        clearstatcache(true, $path);
        $this->bootBaseCachePath();
        $this->ensureParentDirectoryExists($path, 'put');

        try {
            $result = parent::put($path, $contents, $lock);
            // Force file permissions after write
            $this->ensureFilePermissions($path);
            return $result;
        } catch (\Throwable $e) {
            // If the path was removed between checks, recreate and retry once.
            usleep(50000);
            clearstatcache(true, $path);
            $this->ensureParentDirectoryExists($path, 'put-retry', $e);
            $result = parent::put($path, $contents, $lock);
            $this->ensureFilePermissions($path);
            return $result;
        }
    }

    public function append($path, $data, $lock = false)
    {
        clearstatcache(true, $path);
        $this->bootBaseCachePath();
        $this->ensureParentDirectoryExists($path, 'append');

        try {
            $result = parent::append($path, $data, $lock);
            $this->ensureFilePermissions($path);
            return $result;
        } catch (\Throwable $e) {
            usleep(50000);
            clearstatcache(true, $path);
            $this->ensureParentDirectoryExists($path, 'append-retry', $e);
            $result = parent::append($path, $data, $lock);
            $this->ensureFilePermissions($path);
            return $result;
        }
    }

    public function prepend($path, $data, $lock = false)
    {
        clearstatcache(true, $path);
        $this->bootBaseCachePath();
        $this->ensureParentDirectoryExists($path, 'prepend');

        try {
            $result = parent::prepend($path, $data, $lock);
            $this->ensureFilePermissions($path);
            return $result;
        } catch (\Throwable $e) {
            usleep(50000);
            clearstatcache(true, $path);
            $this->ensureParentDirectoryExists($path, 'prepend-retry', $e);
            $result = parent::prepend($path, $data, $lock);
            $this->ensureFilePermissions($path);
            return $result;
        }
    }

    public function copy($path, $target)
    {
        $this->ensureParentDirectoryExists($target, 'copy');
        $result = parent::copy($path, $target);
        $this->ensureFilePermissions($target);
        return $result;
    }

    public function move($path, $target)
    {
        $this->ensureParentDirectoryExists($target, 'move');
        $result = parent::move($path, $target);
        $this->ensureFilePermissions($target);
        return $result;
    }

    /**
     * Ensure file has proper permissions after write.
     * Also ensures ALL parent directories in the cache path have 775.
     */
    protected function ensureFilePermissions(string $path): void
    {
        clearstatcache(true, $path);
        
        if (!file_exists($path)) {
            return;
        }

        $oldUmask = umask(0);
        
        try {
            // Check if path is within storage/framework (cache, views, sessions)
            $frameworkPath = storage_path('framework');
            
            if (strpos($path, $frameworkPath) === 0 || strpos($path, 'cache') !== false) {
                // Set file permissions
                @chmod($path, $this->fileMode);
                
                // Fix ALL parent directories up to framework folder
                $directory = dirname($path);
                while ($directory && strlen($directory) >= strlen($frameworkPath)) {
                    if (is_dir($directory)) {
                        @chmod($directory, $this->directoryMode);
                    }
                    
                    $parent = dirname($directory);
                    if ($parent === $directory) {
                        break; // Reached root
                    }
                    $directory = $parent;
                }
            }
        } finally {
            umask($oldUmask);
        }
    }

    protected function ensureParentDirectoryExists(string $path, string $context = 'write', \Throwable $reason = null): void
    {
        $directory = \dirname($path);

        clearstatcache(true, $directory);

        if (! $this->isDirectory($directory)) {
            try {
                $this->makeDirectory($directory, $this->directoryMode, true, true);
            } catch (\Throwable $e) {
                $this->forceCreateParents($directory, $context, $reason ?? $e, $e);
            }

            clearstatcache(true, $directory);

            // Verify once more; if still missing, attempt a final creation and log.
            if (! $this->isDirectory($directory)) {
                $this->forceCreateParents($directory, $context, $reason);
            }
        }
    }

    protected function forceCreateParents(string $directory, string $context, \Throwable $reason = null, \Throwable $secondary = null): void
    {
        $attempts = 0;
        while ($attempts < 3) {
            clearstatcache(true, $directory);

            if (is_dir($directory)) {
                return;
            }

            // If a file exists where we need a directory, nuke it
            if (file_exists($directory)) {
                @unlink($directory);
                clearstatcache(true, $directory);
            }

            // Temporarily unmask to ensure permissions stick
            $oldUmask = umask(0);
            $result = @mkdir($directory, $this->directoryMode, true);
            umask($oldUmask);

            if ($result) {
                clearstatcache(true, $directory);
                return;
            }

            // Check again in case another process made it
            if (is_dir($directory)) {
                return;
            }

            usleep(mt_rand(10000, 50000));
            $attempts++;
        }

        // Final check
        clearstatcache(true, $directory);
        if (! $this->isDirectory($directory)) {
            Log::warning('[StorageGuard] Directory creation fallback failed', [
                'directory' => $directory,
                'context' => $context,
                'reason' => $reason?->getMessage(),
                'secondary' => $secondary?->getMessage(),
            ]);
        }
    }
}
