<?php

namespace Modules\Whatsapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWhatsAppModuleEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!function_exists('module_enabled') || !module_enabled('Whatsapp')) {
            abort(404, 'WhatsApp module is not enabled');
        }

        // For restaurant routes, check if WhatsApp is in the restaurant's package
        if (function_exists('restaurant') && restaurant()) {
            if (function_exists('restaurant_modules')) {
                $restaurantModules = restaurant_modules();
                if (!in_array('Whatsapp', $restaurantModules)) {
                    abort(404, 'WhatsApp module is not available in your package');
                }
            }
        }

        return $next($request);
    }
}

