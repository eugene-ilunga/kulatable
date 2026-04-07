<?php

namespace Modules\FontControl\Services;

use Illuminate\Support\Str;
use Modules\FontControl\Entities\FontControlSetting;
use Modules\FontControl\Services\FontDownloader;

class FontControlManager
{
    /**
     * Resolve the font configuration for the given locale with fallbacks.
     */
    public static function resolveForLocale(?string $locale = null, ?int $restaurantId = null): ?array
    {
        $locale = $locale ?: app()->getLocale();
        $normalized = strtolower(str_replace('_', '-', $locale));

        $candidates = [$normalized];

        if (str_contains($normalized, '-')) {
            $candidates[] = Str::before($normalized, '-');
        }

        $candidates[] = 'default';

        $hasRestaurant = \Schema::hasColumn('font_control_settings', 'restaurant_id');

        foreach ($candidates as $code) {
            $query = FontControlSetting::where('language_code', $code);
            if ($hasRestaurant) {
                if (! is_null($restaurantId)) {
                    $query->where('restaurant_id', $restaurantId);
                } else {
                    $query->whereNull('restaurant_id');
                }
            }
            $setting = $query->first();

            if ($setting) {
                return [
                    'language_code' => $code,
                    'font_family' => $setting->font_family,
                    'font_size' => max(10, min((int) $setting->font_size, 30)),
                    'font_url' => $setting->font_url,
                ];
            }
        }

        return null;
    }

    /**
     * Returns a local font path for QR generation for the given locale (downloads if needed).
     */
    public static function qrFontPath(?string $locale = null, ?int $restaurantId = null): ?string
    {
        $locale = $locale ?: 'ar';
        $hasRestaurant = \Schema::hasColumn('font_control_settings', 'restaurant_id');

        $query = FontControlSetting::where('language_code', $locale);
        if ($hasRestaurant) {
            if (! is_null($restaurantId)) {
                $query->where('restaurant_id', $restaurantId);
            } else {
                $query->whereNull('restaurant_id');
            }
        }
        $setting = $query->first();

        if (! $setting) {
            $fallback = FontControlSetting::where('language_code', 'default');
            if ($hasRestaurant) {
                $fallback = is_null($restaurantId)
                    ? $fallback->whereNull('restaurant_id')
                    : $fallback->where('restaurant_id', $restaurantId);
            }
            $setting = $fallback->first();
        }

        if (! $setting) {
            return null;
        }

        return FontDownloader::ensureLocal($setting);
    }

    /**
     * Resolve current restaurant id (admin scope) for font lookup.
     */
    public static function currentRestaurantId(): ?int
    {
        // 1) Authenticated restaurant admin
        $user = auth()->user();
        if ($user && $user->restaurant_id) {
            return (int) $user->restaurant_id;
        }

        // 2) Public shop context (customer-facing storefront)
        if (function_exists('shop') && shop()) {
            return shop()->id ?? null;
        }

        // 3) Restaurant context resolved by middleware/session
        if (function_exists('restaurant') && restaurant()) {
            return restaurant()->id ?? null;
        }

        // 4) Explicit session value if set by upstream middleware
        if (session()->has('restaurant_id')) {
            return (int) session('restaurant_id');
        }

        return null;
    }
}
