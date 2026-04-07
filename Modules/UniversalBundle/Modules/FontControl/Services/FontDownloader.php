<?php

namespace Modules\FontControl\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\FontControl\Entities\FontControlSetting;

class FontDownloader
{
    /**
     * Ensure the font for a setting is available locally and return the path.
     */
    public static function ensureLocal(FontControlSetting $setting): ?string
    {
        // Already downloaded and exists
        if ($setting->font_local_path && File::exists($setting->font_local_path)) {
            return $setting->font_local_path;
        }

        if (! $setting->font_url) {
            return null;
        }

        $fontUrl = trim($setting->font_url);
        if ($fontUrl === '') {
            return null;
        }

        // Download and persist
        $fontBinary = self::fetchFontBinary($fontUrl);
        if (! $fontBinary) {
            return null;
        }

        $ext = self::guessExtension($fontUrl, $fontBinary);
        $safeName = Str::slug($setting->font_family ?: 'font', '_') ?: 'font';
        $fileName = $safeName . '_' . Str::random(8) . '.' . $ext;
        $fontDir = public_path('fonts');

        File::ensureDirectoryExists($fontDir);
        $fullPath = $fontDir . DIRECTORY_SEPARATOR . $fileName;

        File::put($fullPath, $fontBinary);

        $setting->font_local_path = $fullPath;
        $setting->saveQuietly();

        return $fullPath;
    }

    /**
     * Download font binary. Supports direct font URLs and Google Fonts CSS.
     */
    private static function fetchFontBinary(string $url): ?string
    {
        try {
            $resp = Http::timeout(15)->get($url);
            if (! $resp->successful()) {
                return null;
            }

            $body = $resp->body();
            $contentType = $resp->header('Content-Type', '');

            // Direct font file
            if (self::isFontContent($contentType)) {
                return $body;
            }

            // Google Fonts CSS: parse all url(...) entries and prefer TTF/OTF first
            if (str_contains($contentType, 'text/css') || str_contains($body, 'font-face')) {
                preg_match_all('/url\\(([^)]+)\\)/i', $body, $matches);
                $urls = collect($matches[1] ?? [])
                    ->map(fn($u) => trim($u, "'\" "))
                    ->filter()
                    ->values();

                $preferredOrder = ['ttf', 'otf', 'woff2', 'woff'];

                $urls = $urls->sortBy(function ($url) use ($preferredOrder) {
                    $lower = strtolower($url);
                    foreach ($preferredOrder as $idx => $ext) {
                        if (str_contains($lower, '.' . $ext)) {
                            return $idx;
                        }
                    }
                    return count($preferredOrder);
                });

                foreach ($urls as $fontUrl) {
                    $subResp = Http::timeout(15)->get($fontUrl);
                    if ($subResp->successful() && self::isFontContent($subResp->header('Content-Type', ''))) {
                        return $subResp->body();
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private static function isFontContent(string $contentType): bool
    {
        return str_contains($contentType, 'font') ||
            str_contains($contentType, 'application/octet-stream') ||
            str_contains($contentType, 'woff') ||
            str_contains($contentType, 'truetype') ||
            str_contains($contentType, 'opentype');
    }

    private static function guessExtension(string $url, string $binary): string
    {
        $lower = strtolower($url);
        foreach (['.ttf', '.otf', '.woff2', '.woff'] as $ext) {
            if (str_contains($lower, $ext)) {
                return ltrim($ext, '.');
            }
        }

        // Heuristic based on magic bytes
        if (str_starts_with($binary, 'wOFF')) {
            return 'woff';
        }
        if (str_starts_with($binary, 'wOF2')) {
            return 'woff2';
        }

        return 'ttf';
    }
}
