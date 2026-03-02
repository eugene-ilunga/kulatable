<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelLang\Locales\Facades\Locales;

class LocaleManager
{
    private static ?Collection $availableCache = null;

    private const LOCALE_ALIASES = [
        'eng' => 'en',
        'gr' => 'el',
        'in' => 'id',
        'pt_br' => 'pt_BR',
        'zh_cn' => 'zh_CN',
        'zh_tw' => 'zh_TW',
        'zh_hans' => 'zh_CN',
        'zh_hant' => 'zh_TW',
        'en_us' => 'en',
        'en_gb' => 'en',
    ];

    private const FLAG_BY_LOCALE = [
        'en' => 'gb',
        'ar' => 'sa',
        'de' => 'de',
        'es' => 'es',
        'et' => 'et',
        'fa' => 'ir',
        'fr' => 'fr',
        'el' => 'gr',
        'it' => 'it',
        'nl' => 'nl',
        'pl' => 'pl',
        'pt' => 'pt',
        'pt_BR' => 'br',
        'ro' => 'ro',
        'ru' => 'ru',
        'tr' => 'tr',
        'zh_CN' => 'cn',
        'zh_TW' => 'tw',
    ];

    private const RTL_LOCALES = ['ar', 'fa', 'he', 'ur', 'ps', 'sd', 'ckb'];

    public static function available(): Collection
    {
        if (self::$availableCache instanceof Collection) {
            return self::$availableCache;
        }

        $locales = collect(self::installedLocaleCodes())
            ->map(fn (string $locale, int $index) => self::makeLocaleOption($locale, $index + 1))
            ->values();

        if ($locales->isEmpty()) {
            self::$availableCache = collect([self::makeLocaleOption('en', 1)]);

            return self::$availableCache;
        }

        self::$availableCache = $locales;

        return self::$availableCache;
    }

    public static function codes(): array
    {
        return self::available()->pluck('language_code')->all();
    }

    public static function resolve(?string $locale, ?string $fallback = null): string
    {
        $available = self::codes();

        if (empty($available)) {
            return 'en';
        }

        $normalizedLocale = self::normalize($locale);
        if ($normalizedLocale && in_array($normalizedLocale, $available, true)) {
            return $normalizedLocale;
        }

        $normalizedFallback = self::normalize($fallback);
        if ($normalizedFallback && in_array($normalizedFallback, $available, true)) {
            return $normalizedFallback;
        }

        return $available[0];
    }

    public static function isRtl(?string $locale): bool
    {
        $locale = self::normalize($locale) ?? 'en';

        try {
            return (Locales::info($locale)->direction->value ?? 'ltr') === 'rtl';
        } catch (\Throwable $e) {
            return in_array(Str::before($locale, '_'), self::RTL_LOCALES, true);
        }
    }

    public static function label(?string $locale): string
    {
        $code = self::resolve($locale);
        $item = self::available()->firstWhere('language_code', $code);

        if (! $item) {
            return strtoupper($code);
        }

        return $item->language_name;
    }

    public static function normalize(?string $locale): ?string
    {
        if (blank($locale)) {
            return null;
        }

        $locale = str_replace('-', '_', trim($locale));
        $alias = Str::lower($locale);

        if (isset(self::LOCALE_ALIASES[$alias])) {
            return self::LOCALE_ALIASES[$alias];
        }

        if (str_contains($locale, '_')) {
            [$language, $regional] = array_pad(explode('_', $locale, 2), 2, null);
            $language = Str::lower($language);

            if (blank($regional)) {
                return $language;
            }

            $regional = strlen($regional) <= 3 ? Str::upper($regional) : $regional;

            return $language . '_' . $regional;
        }

        return Str::lower($locale);
    }

    private static function installedLocaleCodes(): array
    {
        $langPath = lang_path();

        if (! is_dir($langPath)) {
            return [];
        }

        $directoryLocales = collect(File::directories($langPath))
            ->map(fn (string $path) => basename($path));

        $jsonLocales = collect(File::glob($langPath . '/*.json'))
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME));

        return $directoryLocales
            ->merge($jsonLocales)
            ->map(fn (string $locale) => self::normalize($locale))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private static function makeLocaleOption(string $locale, int $id): object
    {
        $meta = self::localeMeta($locale);
        $flagCode = self::flagCode($locale, $meta['regional'] ?? null);

        return (object) [
            'id' => $id,
            'language_code' => $locale,
            'language_name' => $meta['native'],
            'localized_name' => $meta['localized'],
            'native_name' => $meta['native'],
            'flag_code' => $flagCode,
            'flagUrl' => asset('flags/1x1/' . strtolower($flagCode) . '.svg'),
            'is_rtl' => self::isRtl($locale),
            'active' => true,
        ];
    }

    private static function localeMeta(string $locale): array
    {
        try {
            $info = Locales::info($locale);

            return [
                'localized' => $info->localized,
                'native' => $info->native,
                'regional' => $info->regional,
            ];
        } catch (\Throwable $e) {
            $title = Str::of(str_replace('_', ' ', $locale))->title()->toString();

            return [
                'localized' => $title,
                'native' => $title,
                'regional' => null,
            ];
        }
    }

    private static function flagCode(string $locale, ?string $regional = null): string
    {
        if (isset(self::FLAG_BY_LOCALE[$locale])) {
            return self::FLAG_BY_LOCALE[$locale];
        }

        $base = Str::before($locale, '_');

        if (isset(self::FLAG_BY_LOCALE[$base])) {
            return self::FLAG_BY_LOCALE[$base];
        }

        if (! empty($regional)) {
            return Str::lower($regional);
        }

        return $base === 'en' ? 'gb' : $base;
    }
}
