@php
    $fallbacks = ['Figtree', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'];
    $primaryFonts = array_filter(array_map('trim', explode(',', $font['font_family'] ?? '')));
    $families = collect(array_merge($primaryFonts, $fallbacks))
        ->filter()
        ->unique()
        ->map(function ($name) {
            $safe = preg_replace('/[^A-Za-z0-9 \\-]/', '', $name);
            if ($safe === '') {
                return null;
            }

            return preg_match('/\\s/', $safe) ? '"' . $safe . '"' : $safe;
        })
        ->filter()
        ->implode(', ');
@endphp
<!-- fontcontrol-styles -->
@if (!empty($font['font_url']))
    <link rel="stylesheet" href="{{ $font['font_url'] }}">
@endif
<style id="fontcontrol-styles">
:root {
    --fontcontrol-size: {{ $font['font_size'] ?? 14 }}px;
}

body,
.font-sans {
    font-family: {!! $families !!} !important;
}

body {
    font-size: var(--fontcontrol-size);
}

input,
select,
textarea,
button {
    font-family: inherit;
    font-size: inherit;
}
</style>
