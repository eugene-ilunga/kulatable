<?php

namespace App\Livewire\Shop;

use Livewire\Component;

class LanguageSwitcher extends Component
{

    public function setLanguage($locale)
    {
        $locale = normalize_locale($locale, global_setting()->locale);
        $availableLocales = languages()->pluck('language_code')->all();
        if (!in_array($locale, $availableLocales, true)) {
            return;
        }

        session(['customer_locale' => $locale]);
        session(['customer_is_rtl' => locale_is_rtl($locale)]);

        $this->js('window.location.reload()');

    }

    public function render()
    {
        $locale = normalize_locale(session('customer_locale'), global_setting()->locale);
        $activeLanguage = languages()->firstWhere('language_code', $locale)
            ?? languages()->first();

        return view('livewire.shop.language-switcher', [
            'activeLanguage' => $activeLanguage,
        ]);
    }

}
