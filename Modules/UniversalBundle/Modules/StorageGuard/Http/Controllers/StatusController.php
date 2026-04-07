<?php

namespace Modules\StorageGuard\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\StorageGuard\Services\PathEnsurer;
use Modules\StorageGuard\Support\SafeFilesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class StatusController extends Controller
{
    public function __construct(private PathEnsurer $paths, private SafeFilesystem $files)
    {
    }

    public function index(): View
    {
        $statuses = $this->diagnose();
        $hasErrors = collect($statuses)->contains(fn ($s) => $s['status'] !== 'ok');

        return view('storageguard::status', [
            'statuses' => $statuses,
            'has_errors' => $hasErrors,
        ]);
    }

    public function fix(): RedirectResponse
    {
        $this->paths->ensureBasePaths();
        $directories = $this->paths->paths();
        $fixedCount = 0;
        
        // Normalize cache data path for comparison
        $cacheDataPath = realpath(storage_path('framework/cache/data')) ?: storage_path('framework/cache/data');

        foreach ($directories as $path) {
            try {
                if (!is_dir($path)) {
                    continue;
                }

                // Fix base directory
                $this->repairPermission($path, 0775);
                $fixedCount++;

                // Recursively fix sub-directories and files
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        $this->repairPermission($item->getPathname(), 0775);
                    } else {
                        $this->repairPermission($item->getPathname(), 0664);
                    }
                    $fixedCount++;
                }
            } catch (\Throwable $e) {
                Log::error('[StorageGuard] Recursive fix failed', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('storageguard.status')->with('storageguard_message', __('storageguard::messages.success_message', ['count' => $fixedCount]));
    }

    protected function diagnose(): array
    {
        $results = [];

        foreach ($this->paths->paths() as $path) {
            $exists = $this->files->isDirectory($path);
            $perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';
            $writable = $exists ? $this->files->isWritable($path) : false;
            $readable = $exists ? $this->files->isReadable($path) : false;

            $status = ($exists && $writable && $readable) ? 'ok' : 'issue';

            $results[] = [
                'path' => $path,
                'exists' => $exists,
                'perms' => $perms,
                'readable' => $readable,
                'writable' => $writable,
                'status' => $status,
            ];
        }

        return $results;
    }

    private function repairPermission(string $path, int $mode): void
    {
        try {
            $currentPerms = fileperms($path) & 0777;
            if ($currentPerms !== $mode) {
                @chmod($path, $mode);
            }
        } catch (\Throwable $e) {
            // Best effort
        }
    }
}
