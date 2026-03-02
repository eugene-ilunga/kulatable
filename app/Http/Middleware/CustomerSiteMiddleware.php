<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Restaurant;

class CustomerSiteMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hash = $request->route('hash');
        
        if ($hash) {
            $restaurant = Restaurant::where('hash', $hash)->first();
    
            if ($restaurant && $restaurant->customer_site_language) {
                // If session has locale (from language switcher), use it
                if (session()->has('customer_locale') || session()->has('locale')) {
                    $locale = normalize_locale(
                        session('customer_locale') ?? session('locale'),
                        $restaurant->customer_site_language
                    );
                    $rtl = locale_is_rtl($locale);
                    session(['customer_is_rtl' => $rtl]);
                    session()->forget('isRtl'); // Clear admin session
                } else {
                    // First visit - use restaurant's customer_site_language directly
                    $locale = normalize_locale($restaurant->customer_site_language, global_setting()->locale);
                    $rtl = locale_is_rtl($locale);
                    
                    // Set session for consistency
                    session([
                        'customer_site_language' => $locale,
                        'customer_locale' => $locale,
                        'customer_is_rtl' => $rtl,
                    ]);
                    session()->forget('isRtl'); // Clear admin session
                }
                
                App::setLocale($locale);
            }
        }
    
        return $next($request);
    }
}
