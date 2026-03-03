<?php

namespace App\Traits;

use App\Models\Restaurant;
use App\Models\Table;
use App\Models\Order;
use Illuminate\Support\Facades\App;

trait HasLanguageSettings
{
    protected function applyLanguageSettings(): void
    {
        $restaurant = $this->getRestaurantForLanguage();

        if ($restaurant) {
            $this->setLanguageAndRTL($restaurant);
        }
    }
    
    private function getRestaurantForLanguage(): ?Restaurant
    {
        // First try to get restaurant from query parameter (for table routes)
        if (request()->filled('hash')) {
            $restaurant = Restaurant::where('hash', request('hash'))->first();
            if ($restaurant) return $restaurant;
        }
        
        // Try route parameter
        $hash = request()->route('hash');
        if ($hash) {
            $restaurant = Restaurant::where('hash', $hash)->first();
            if ($restaurant) return $restaurant;
        }
        
        // Try to get from table hash (for tableOrder method)
        if ($hash) {
            $table = Table::where('hash', $hash)->first();
            if ($table) return $table->branch->restaurant;
        }
        
        // Try to get from order UUID (for orderSuccess method)
        $uuid = request()->route('id');
        if ($uuid) {
            $order = Order::where('uuid', $uuid)->first();
            if ($order) return $order->branch->restaurant;
        }
        
        return null;
    }
    
    private function setLanguageAndRTL(Restaurant $restaurant): void
    {
        $defaultLocale = normalize_locale(global_setting()?->locale, config('app.fallback_locale', 'en'));

        if (session()->has('customer_locale')) {
            $locale = normalize_locale(session('customer_locale'), $defaultLocale);
            $rtl = locale_is_rtl($locale);
            session(['customer_is_rtl' => $rtl]);
            session()->forget('isRtl'); // Clear admin session
        } else {
            // First visit - use file-based default locale
            $locale = $defaultLocale;
            $rtl = locale_is_rtl($locale);

            // Set session for consistency
            session([
                'customer_site_language' => $defaultLocale,
                'customer_locale' => $locale,
                'customer_is_rtl' => $rtl,
            ]);
            session()->forget('isRtl'); // Clear admin session
        }
        
        App::setLocale($locale);
    }
} 
