<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;


abstract class Controller
{
    /**
     * @var array
     */
    public $data = [];

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * @param mixed $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __construct()
    {
        $this->checkMigrateStatus();

        $user = auth()->user();
        $preferredLocale = session('locale')
            ?? session('customer_locale')
            ?? $user?->locale
            ?? global_setting()?->locale
            ?? 'en';

        try {
            App::setLocale(normalize_locale($preferredLocale, global_setting()?->locale ?? config('app.fallback_locale', 'en')));
        } catch (\Throwable $e) {
            App::setLocale('en');
        }
    }

    public function checkMigrateStatus()
    {
        return check_migrate_status();
    }
}
