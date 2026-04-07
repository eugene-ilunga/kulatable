<?php

use Modules\MultiPOS\Entities\PosMachine;

if (!function_exists('pos_machine')) {
    /**
     * Get the current POS machine from the request or cookie
     *
     * @return PosMachine|null
     */
    function pos_machine()
    {
        // First try to get from request attributes (set by middleware)
        $machine = request()->attributes->get('posMachine');

        if ($machine) {
            return $machine;
        }

        // If not in request attributes (e.g., Livewire requests), resolve from cookie
        if (!module_enabled('MultiPOS') || !in_array('MultiPOS', restaurant_modules())) {
            return null;
        }

        $cookieName = config('multipos.cookie.name', 'pos_token');
        $deviceId = request()->cookie($cookieName);

        if (!$deviceId) {
            return null;
        }

        // Get current branch from session
        $branchId = session('branch')?->id ?? null;

        if (!$branchId) {
            return null;
        }

        // Find machine by device_id and branch_id
        return PosMachine::where('device_id', $deviceId)
            ->where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'active']) // Only allow pending and active machines
            ->first();
    }
}

if (!function_exists('pos_machine_id')) {
    /**
     * Get the current POS machine ID
     *
     * @return int|null
     */
    function pos_machine_id()
    {
        $machine = pos_machine();
        return $machine ? $machine->id : null;
    }
}

if (!function_exists('is_pos_registered')) {
    /**
     * Check if the current device is registered as a POS machine
     *
     * @return bool
     */
    function is_pos_registered()
    {
        return !is_null(pos_machine()) && pos_machine()->isActive();
    }
}

if (!function_exists('pos_machine_alias')) {
    /**
     * Get the alias of the current POS machine
     *
     * @return string
     */
    function pos_machine_alias()
    {
        $machine = pos_machine();
        return $machine ? ($machine->alias ?? $machine->public_id) : 'Unknown';
    }
}

if (!function_exists('pos_cookie_name')) {
    /**
     * Get the cookie name for POS machines
     *
     * @return string
     */
    function pos_cookie_name()
    {
        return config('multipos.cookie.name', 'pos_token');
    }
}
