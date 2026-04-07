<?php

namespace Modules\FontControl\Services;

/**
 * Minimal Arabic glyph shaper to render via GD/TTF (Endroid QR label).
 * This is a lightweight subset suitable for QR labels; for full shaping use HarfBuzz.
 */
class ArabicGlyphShaper
{
    // Expanded presentation map with lam-alef ligatures
    private const MAP = [
        'ء' => ['isolated' => 'ﺀ'],
        'آ' => ['isolated' => 'ﺁ', 'initial' => 'ﺁ', 'medial' => 'ﺂ', 'final' => 'ﺂ'],
        'أ' => ['isolated' => 'ﺃ', 'initial' => 'ﺃ', 'medial' => 'ﺄ', 'final' => 'ﺄ'],
        'ؤ' => ['isolated' => 'ﺅ', 'final' => 'ﺆ'],
        'إ' => ['isolated' => 'ﺇ', 'initial' => 'ﺇ', 'medial' => 'ﺈ', 'final' => 'ﺈ'],
        'ئ' => ['isolated' => 'ﺉ', 'initial' => 'ﺋ', 'medial' => 'ﺋ', 'final' => 'ﺊ'],
        'ا' => ['isolated' => 'ﺍ', 'final' => 'ﺎ'],
        'ب' => ['isolated' => 'ﺏ', 'initial' => 'ﺑ', 'medial' => 'ﺒ', 'final' => 'ﺐ'],
        'ت' => ['isolated' => 'ﺕ', 'initial' => 'ﺗ', 'medial' => 'ﺘ', 'final' => 'ﺖ'],
        'ث' => ['isolated' => 'ﺙ', 'initial' => 'ﺛ', 'medial' => 'ﺜ', 'final' => 'ﺚ'],
        'ج' => ['isolated' => 'ﺝ', 'initial' => 'ﺟ', 'medial' => 'ﺠ', 'final' => 'ﺞ'],
        'ح' => ['isolated' => 'ﺡ', 'initial' => 'ﺣ', 'medial' => 'ﺤ', 'final' => 'ﺢ'],
        'خ' => ['isolated' => 'ﺥ', 'initial' => 'ﺧ', 'medial' => 'ﺨ', 'final' => 'ﺦ'],
        'د' => ['isolated' => 'ﺩ', 'final' => 'ﺪ'],
        'ذ' => ['isolated' => 'ﺫ', 'final' => 'ﺬ'],
        'ر' => ['isolated' => 'ﺭ', 'final' => 'ﺮ'],
        'ز' => ['isolated' => 'ﺯ', 'final' => 'ﺰ'],
        'س' => ['isolated' => 'ﺱ', 'initial' => 'ﺳ', 'medial' => 'ﺴ', 'final' => 'ﺲ'],
        'ش' => ['isolated' => 'ﺵ', 'initial' => 'ﺷ', 'medial' => 'ﺸ', 'final' => 'ﺶ'],
        'ص' => ['isolated' => 'ﺹ', 'initial' => 'ﺻ', 'medial' => 'ﺼ', 'final' => 'ﺺ'],
        'ض' => ['isolated' => 'ﺽ', 'initial' => 'ﺿ', 'medial' => 'ﻀ', 'final' => 'ﺾ'],
        'ط' => ['isolated' => 'ﻁ', 'initial' => 'ﻃ', 'medial' => 'ﻄ', 'final' => 'ﻂ'],
        'ظ' => ['isolated' => 'ﻅ', 'initial' => 'ﻇ', 'medial' => 'ﻈ', 'final' => 'ﻆ'],
        'ع' => ['isolated' => 'ﻉ', 'initial' => 'ﻋ', 'medial' => 'ﻌ', 'final' => 'ﻊ'],
        'غ' => ['isolated' => 'ﻍ', 'initial' => 'ﻏ', 'medial' => 'ﻐ', 'final' => 'ﻎ'],
        'ف' => ['isolated' => 'ﻑ', 'initial' => 'ﻓ', 'medial' => 'ﻔ', 'final' => 'ﻒ'],
        'ق' => ['isolated' => 'ﻕ', 'initial' => 'ﻗ', 'medial' => 'ﻘ', 'final' => 'ﻖ'],
        'ك' => ['isolated' => 'ﻙ', 'initial' => 'ﻛ', 'medial' => 'ﻜ', 'final' => 'ﻚ'],
        'ل' => ['isolated' => 'ﻝ', 'initial' => 'ﻟ', 'medial' => 'ﻠ', 'final' => 'ﻞ'],
        'م' => ['isolated' => 'ﻡ', 'initial' => 'ﻣ', 'medial' => 'ﻤ', 'final' => 'ﻢ'],
        'ن' => ['isolated' => 'ﻥ', 'initial' => 'ﻧ', 'medial' => 'ﻨ', 'final' => 'ﻦ'],
        'ه' => ['isolated' => 'ﻩ', 'initial' => 'ﻫ', 'medial' => 'ﻬ', 'final' => 'ﻪ'],
        'و' => ['isolated' => 'ﻭ', 'final' => 'ﻮ'],
        'ي' => ['isolated' => 'ﻱ', 'initial' => 'ﻳ', 'medial' => 'ﻴ', 'final' => 'ﻲ'],
        'ى' => ['isolated' => 'ﻯ', 'final' => 'ﻰ'],
        'لا' => ['isolated' => 'ﻻ', 'final' => 'ﻼ', 'initial' => 'ﻻ', 'medial' => 'ﻼ'],
        'لأ' => ['isolated' => 'ﻷ', 'final' => 'ﻸ', 'initial' => 'ﻷ', 'medial' => 'ﻸ'],
        'لإ' => ['isolated' => 'ﻹ', 'final' => 'ﻺ', 'initial' => 'ﻹ', 'medial' => 'ﻺ'],
        'لآ' => ['isolated' => 'ﻵ', 'final' => 'ﻶ', 'initial' => 'ﻵ', 'medial' => 'ﻶ'],
        'ة' => ['isolated' => 'ﺓ', 'final' => 'ﺔ'],
    ];

    private const NON_CONNECTING = ['ا', 'د', 'ذ', 'ر', 'ز', 'و', 'ؤ', 'آ', 'أ', 'إ', 'ء', 'ٱ'];

    public static function shape(string $text): string
    {
        // Shape only Arabic segments (keep numbers/Latin in place)
        $segments = self::segment($text);
        $shapedSegments = [];

        foreach ($segments as $segment) {
            if ($segment['is_ar']) {
                $shaped = self::shapeArabicSegment($segment['text']);
                // Reverse shaped Arabic to render RTL correctly in GD/Endroid label
                $chars = preg_split('//u', $shaped, -1, PREG_SPLIT_NO_EMPTY);
                $shapedSegments[] = implode('', array_reverse($chars));
            } else {
                $shapedSegments[] = $segment['text'];
            }
        }

        return implode('', $shapedSegments);
    }

    private static function segment(string $text): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $segments = [];
        $buf = '';
        $isAr = null;

        foreach ($chars as $ch) {
            $chIsAr = preg_match('/[\\x{0600}-\\x{06FF}]/u', $ch) === 1;
            if ($isAr === null) {
                $isAr = $chIsAr;
            }
            if ($chIsAr !== $isAr) {
                $segments[] = ['text' => $buf, 'is_ar' => $isAr];
                $buf = $ch;
                $isAr = $chIsAr;
            } else {
                $buf .= $ch;
            }
        }
        if ($buf !== '') {
            $segments[] = ['text' => $buf, 'is_ar' => $isAr ?? false];
        }

        return $segments;
    }

    private static function shapeArabicSegment(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        $len = count($chars);

        $i = 0;
        while ($i < $len) {
            $char = $chars[$i];
            $next = $chars[$i + 1] ?? null;
            $prev = $chars[$i - 1] ?? null;

            // Lam + Alef ligatures
            if ($char === 'ل' && $next && in_array($next, ['ا', 'أ', 'إ', 'آ'], true)) {
                $combo = 'ل' . $next;
                if (isset(self::MAP[$combo])) {
                    $connectedBefore = $prev && ! in_array($prev, self::NON_CONNECTING, true) && isset(self::MAP[$prev]);
                    $connectedAfter = false; // lam-alef does not connect forward
                    $form = $connectedBefore ? 'final' : 'isolated';
                    $result[] = self::MAP[$combo][$form] ?? self::MAP[$combo]['isolated'];
                    $i += 2;
                    continue;
                }
            }

            $connectedBefore = $prev && ! in_array($prev, self::NON_CONNECTING, true) && isset(self::MAP[$prev]);
            $connectedAfter = $next && ! in_array($char, self::NON_CONNECTING, true) && isset(self::MAP[$next]);

            $form = 'isolated';
            if ($connectedBefore && $connectedAfter) {
                $form = 'medial';
            } elseif ($connectedBefore) {
                $form = 'final';
            } elseif ($connectedAfter) {
                $form = 'initial';
            }

            $result[] = self::MAP[$char][$form] ?? $char;
            $i++;
        }

        return implode('', $result);
    }
}
