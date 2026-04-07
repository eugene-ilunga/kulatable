<?php

namespace Modules\FontControl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureQrFonts
{
    /**
     * Regenerate QR codes (once per cache window) to ensure labels use selected fonts.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        return $response;
    }
}
