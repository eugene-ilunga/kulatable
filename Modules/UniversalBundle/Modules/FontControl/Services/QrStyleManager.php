<?php

namespace Modules\FontControl\Services;

use Modules\FontControl\Entities\FontControlQrSetting;
use Modules\FontControl\Services\Fonts\LocalFont;

class QrStyleManager
{
    private const DEFAULTS = [
        'label_format' => '{table_code}',
        'font_family' => 'Noto Sans',
        'font_size' => 16,
        'font_url' => null,
        'font_local_path' => null,
        'qr_size' => 300,
        'qr_margin' => 10,
        'qr_foreground_color' => '#000000',
        'qr_background_color' => '#FFFFFF',
        'qr_round_block_size' => true,
        'label_color' => '#000000',
        'advanced_qr_enabled' => false,
        'qr_logo_path' => null,
        'qr_logo_size' => 20,
        'qr_ecc_level' => 'H',
    ];

    public static function get(?int $restaurantId = null): array
    {
        $settings = null;

        if (\Schema::hasTable('font_control_qr_settings')) {
            // Exact restaurant match first
            if ($restaurantId) {
                $settings = FontControlQrSetting::where('restaurant_id', $restaurantId)->first();
            }
            // Fallback to global
            if (! $settings) {
                $settings = FontControlQrSetting::whereNull('restaurant_id')->first();
            }
        }

        return array_merge(self::DEFAULTS, $settings ? $settings->toArray() : []);
    }

    public static function labelForTable(\App\Models\Table $table, array $config): string
    {
        $format = $config['label_format'] ?? self::DEFAULTS['label_format'];
        $replacements = [
            '{table_code}' => $table->table_code ?? '',
            '{table_hash}' => $table->hash ?? '',
            '{area_name}' => $table->area->area_name ?? '',
        ];

        $label = strtr($format, $replacements);

        // Ensure UTF-8 and shape Arabic if needed
        $label = mb_convert_encoding($label, 'UTF-8', 'UTF-8');
        return ArabicGlyphShaper::shape($label);
    }

    public static function labelFont(?int $restaurantId, array $config): LocalFont
    {
        // Prefer QR-specific font path if provided; else fall back to FontControlManager QR font; else noto
        $fontPath = $config['font_local_path'] ?? null;
        if (! $fontPath) {
            $fontPath = FontControlManager::qrFontPath('ar', $restaurantId);
        }

        $fallbacks = [
            $fontPath,
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
            base_path('vendor/endroid/qr-code/assets/noto_sans.otf'),
            base_path('vendor/endroid/qr-code/assets/noto_sans.ttf'),
            public_path('fonts/NotoSans-Regular.ttf'),
            __DIR__ . '/../Resources/fonts/NotoSans-Regular.ttf',
        ];

        $chosen = collect($fallbacks)->first(fn($p) => $p && file_exists($p));

        return new LocalFont($chosen, (int) ($config['font_size'] ?? self::DEFAULTS['font_size']));
    }

    public static function foregroundColor(array $config): array
    {
        return self::hexToRgb($config['qr_foreground_color'] ?? self::DEFAULTS['qr_foreground_color']);
    }

    public static function backgroundColor(array $config): array
    {
        return self::hexToRgb($config['qr_background_color'] ?? self::DEFAULTS['qr_background_color']);
    }

    public static function labelColor(array $config): array
    {
        return self::hexToRgb($config['label_color'] ?? $config['qr_foreground_color'] ?? self::DEFAULTS['label_color']);
    }

    public static function isAdvanced(array $config): bool
    {
        return (bool) ($config['advanced_qr_enabled'] ?? false)
            || ! empty($config['qr_logo_path']);
    }

    public static function logoPath(array $config): ?string
    {
        if (empty($config['qr_logo_path'])) {
            return null;
        }
        $path = $config['qr_logo_path'];
        // If stored on default disk and path is relative, build public path
        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^https?:\\/\\//i', $path)) {
            $public = public_path($path);
            if (file_exists($public)) {
                return $public;
            }
            $storage = storage_path('app/' . ltrim($path, '/'));
            if (file_exists($storage)) {
                return $storage;
            }
            try {
                $disk = config('filesystems.default', 'public');
                $storagePath = \Storage::disk($disk)->path($path);
                if (file_exists($storagePath)) {
                    return $storagePath;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return $path;
    }

    public static function logoSize(array $config, int $qrSize): ?int
    {
        $pct = (int) ($config['qr_logo_size'] ?? 20);
        if ($pct <= 0 || $pct > 80) {
            return null;
        }
        return max(10, (int) ($qrSize * ($pct / 100)));
    }

    public static function ecc(array $config): string
    {
        $level = strtoupper($config['qr_ecc_level'] ?? 'H');
        if ($level === 'NONE' || $level === 'N') {
            return 'NONE';
        }
        return in_array($level, ['L','M','Q','H'], true) ? $level : 'H';
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
        }

        $int = hexdec($hex ?: '000000');

        return [
            'r' => ($int >> 16) & 255,
            'g' => ($int >> 8) & 255,
            'b' => $int & 255,
        ];
    }
}
