<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class LanguageSettings extends Component
{
    public $languageSettings;

    public function mount()
    {
        cache()->forget('languages');
        $this->languageSettings = languages();
    }

    public function refreshLanguages(): void
    {
        cache()->forget('languages');
        $this->languageSettings = languages();
    }

    public function render()
    {
        return view('livewire.settings.language-settings');
    }
}
