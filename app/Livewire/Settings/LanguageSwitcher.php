<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Scopes\BranchScope;
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

        User::withoutGlobalScope(BranchScope::class)->where('id', user()->id)->update(['locale' => $locale]);

        session()->forget('user');
        session(['user' => auth()->user()]);

        if (user()) {
            session(['isRtl' => locale_is_rtl($locale)]);
        }

        $this->js('window.location.reload()');

    }

    public function render()
    {
        $locale = normalize_locale(auth()->user()->locale, global_setting()->locale);
        $activeLanguage = languages()->firstWhere('language_code', $locale)
            ?? languages()->first();

        return view('livewire.settings.language-switcher', [
            'activeLanguage' => $activeLanguage,
        ]);
    }

}
