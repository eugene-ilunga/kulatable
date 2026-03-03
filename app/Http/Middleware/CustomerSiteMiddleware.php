<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Order;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Restaurant;
use App\Models\Table;

class CustomerSiteMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $this->resolveRestaurant($request);

        if ($restaurant) {
            $defaultLocale = normalize_locale(global_setting()?->locale, config('app.fallback_locale', 'en'));

            // On customer routes, only customer_locale should drive language (never admin locale).
            if (session()->has('customer_locale')) {
                $locale = normalize_locale(session('customer_locale'), $defaultLocale);
            } else {
                $locale = $defaultLocale;
            }

            $rtl = locale_is_rtl($locale);

            session([
                'customer_site_language' => $defaultLocale,
                'customer_locale' => $locale,
                'customer_is_rtl' => $rtl,
            ]);
            session()->forget('isRtl'); // Clear admin session

            App::setLocale($locale);
        }

        return $next($request);
    }

    private function resolveRestaurant(Request $request): ?Restaurant
    {
        $hash = $request->route('hash') ?? $request->query('hash');

        if ($hash) {
            $restaurant = Restaurant::where('hash', $hash)->first();
            if ($restaurant) {
                return $restaurant;
            }

            $table = Table::where('hash', $hash)->first();
            if ($table) {
                return $table->branch?->restaurant;
            }
        }

        $orderId = $request->route('id');
        if ($orderId) {
            $order = Order::where('uuid', $orderId)->first();
            if ($order) {
                return $order->branch?->restaurant;
            }
        }

        return null;
    }
}
