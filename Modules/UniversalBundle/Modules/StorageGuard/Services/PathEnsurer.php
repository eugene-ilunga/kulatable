<?php

namespace Modules\StorageGuard\Services;

use Illuminate\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PathEnsurer
{
    protected int $directoryMode = 0775;
    protected int $fileMode = 0664;

    public function __construct(private Filesystem $files)
    {
    }

    /**
     * Create and normalize the cache/storage directories Laravel touches at runtime.
     */
    public function ensureBasePaths(): void
    {
        $cachePath = config('cache.stores.file.path', storage_path('framework/cache/data'));
        $viewCompiled = config('view.compiled', storage_path('framework/views'));

        $paths = array_unique(array_filter([
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/cache/packages'),
            storage_path('framework/cache/compiled'),
            storage_path('framework/cache/testing'),
            storage_path('framework/views'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            $cachePath,
            $viewCompiled,
        ]));

        foreach ($paths as $path) {
            $this->ensureDirectory($path);
        }

        $this->ensureGitignore($cachePath . DIRECTORY_SEPARATOR . '.gitignore');
        $this->ensureGitignore(storage_path('framework/views/.gitignore'));
        $this->ensureGitignore(storage_path('framework/sessions/.gitignore'));
        
        // Fix permissions on existing cache files
        $this->fixAllCachePermissions();
    }

    /**
     * Return the paths we guard. Used for diagnostics UI.
     */
    public function paths(): array
    {
        $cachePath = config('cache.stores.file.path', storage_path('framework/cache/data'));
        $viewCompiled = config('view.compiled', storage_path('framework/views'));

        return array_unique(array_filter([
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/cache/packages'),
            storage_path('framework/cache/compiled'),
            storage_path('framework/cache/testing'),
            storage_path('framework/views'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            $cachePath,
            $viewCompiled,
        ]));
    }

    protected function ensureDirectory(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            try {
                $this->files->makeDirectory($path, $this->directoryMode, true, true);
            } catch (\Throwable $e) {
                // Swallow race conditions or permission quirks; caller will re-check existence.
            }
        }
        
        // Always ensure directory has correct permissions
        if (is_dir($path)) {
            @chmod($path, $this->directoryMode);
        }
    }

    protected function ensureGitignore(string $path): void
    {
        if (! $this->files->exists($path)) {
            $this->files->put($path, "*\n!.gitignore\n");
        }
        @chmod($path, $this->fileMode);
    }

    /**
     * Fix permissions on all existing cache files and directories.
     * This ensures ALL directories have 775 and all files have 664.
     */
    public function fixAllCachePermissions(): void
    {
        // Main cache directory - MUST be 775
        $cacheRoot = storage_path('framework/cache');
        $this->forceDirectoryPermission($cacheRoot);
        
        // All paths that need permission fixes
        $paths = [
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('framework/views'),
            storage_path('framework/sessions'),
            storage_path('logs'),
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->fixDirectoryPermissionsRecursive($path);
            }
        }
    }

    /**
     * Force a single directory to have correct permissions.
     */
    protected function forceDirectoryPermission(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        
        $oldUmask = umask(0);
        @chmod($directory, $this->directoryMode);
        umask($oldUmask);
    }

    /**
     * Recursively fix permissions on a directory and ALL its contents.
     * ALL directories get 775, ALL files get 664 - NO EXCEPTIONS.
     */
    protected function fixDirectoryPermissionsRecursive(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $oldUmask = umask(0);
        
        try {
            // First, fix the root directory itself
            @chmod($directory, $this->directoryMode);

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $pathname = $item->getPathname();
                
                if ($item->isDir()) {
                    // ALL directories get 775
                    @chmod($pathname, $this->directoryMode);
                } else {
                    // ALL files get 664
                    @chmod($pathname, $this->fileMode);
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore permission errors during bulk fix
            // But log it for debugging if needed
        } finally {
            umask($oldUmask);
        }
    }

    /**
     * Alias for backward compatibility
     */
    protected function fixDirectoryPermissions(string $directory): void
    {
        $this->fixDirectoryPermissionsRecursive($directory);
    }
}
