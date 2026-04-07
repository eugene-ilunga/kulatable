<?php

namespace Modules\FontControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\FontControl\Services\FontControlManager;
// Removed: use Modules\FontControl\Services\TableQrRegenerator;

class ApplyFontPreferences
{
    /**
     * Inject language-aware font styles into HTML responses.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$response instanceof Response) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $restaurantId = FontControlManager::currentRestaurantId();
        $font = FontControlManager::resolveForLocale(null, $restaurantId);

        if (!$font) {
            return $response;
        }

        $payload = view('fontcontrol::partials.font-styles', ['font' => $font])->render();
        $content = $response->getContent();

        // Avoid injecting multiple times
        if (str_contains($content, 'fontcontrol-styles')) {
            return $response;
        }

        $pos = stripos($content, '</head>');

        if ($pos !== false) {
            $response->setContent(substr_replace($content, $payload, $pos, 0));
        }

        return $response;
    }

    // terminate() method REMOVED - it was calling runThrottled(1) on EVERY request!
    // QR regeneration now happens ONLY when:
    // 1. Settings are saved via FontControl UI (saveQr() -> forceRun())
    // 2. This is the correct behavior - not on every HTTP response
}

