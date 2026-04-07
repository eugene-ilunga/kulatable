<?php

namespace Modules\Aitools\Livewire\Settings;

use App\Models\Restaurant;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AitoolsSettings extends Component
{
    use LivewireAlert;

    public $settings;
    public $aiEnabled = false;
    public $aiAllowedRoles = [];
    public $availableRoles = ['owner', 'admin', 'manager', 'cashier'];
    public $packageInfo = null;

    public function mount()
    {
        $this->settings = Restaurant::with('package')->find(restaurant()->id);
        $this->aiEnabled = (bool) $this->settings->ai_enabled;

        // Ensure ai_allowed_roles is always an array
        $roles = $this->settings->ai_allowed_roles;
        if (is_string($roles)) {
            $roles = json_decode($roles, true) ?? [];
        }
        $this->aiAllowedRoles = is_array($roles) && !empty($roles) ? $roles : ['owner', 'admin'];
        
        // Get package information
        $this->loadPackageInfo();
    }
    
    public function loadPackageInfo()
    {
        $restaurant = $this->settings;
        if ($restaurant && $restaurant->package) {
            $policy = new \Modules\Aitools\Services\Ai\AiPolicy();
            $monthlyLimit = $restaurant->package->ai_monthly_token_limit ?? -1;
            $used = $restaurant->ai_monthly_tokens_used ?? 0;
            $remaining = $policy->getRemainingMonthlyTokens($restaurant);
            
            $this->packageInfo = [
                'monthly_limit' => $monthlyLimit,
                'used' => $used,
                'remaining' => $remaining,
                'unlimited' => $monthlyLimit == -1,
                'package_name' => $restaurant->package->package_name ?? 'N/A',
            ];
        } else {
            $this->packageInfo = [
                'monthly_limit' => -1,
                'used' => 0,
                'remaining' => 999999,
                'unlimited' => true,
                'package_name' => 'No Package',
            ];
        }
    }

    public function save()
    {
        $this->validate([
            'aiEnabled' => 'boolean',
            'aiAllowedRoles' => 'array',
        ]);

        $this->settings->ai_enabled = $this->aiEnabled;
        $this->settings->ai_allowed_roles = $this->aiAllowedRoles;
        $this->settings->save();
        
        // Reload package info after save
        $this->loadPackageInfo();


        cache()->forget('restaurant');
        session()->forget('restaurant');

        $this->alert('success', __('aitools::app.messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        // Reload the page to update menu visibility based on AI settings
        $this->js('window.location.reload()');
    }

    public function toggleRole($role)
    {
        // Ensure aiAllowedRoles is always an array
        if (!is_array($this->aiAllowedRoles)) {
            $this->aiAllowedRoles = is_string($this->aiAllowedRoles)
                ? json_decode($this->aiAllowedRoles, true) ?? []
                : [];
        }

        if (in_array($role, $this->aiAllowedRoles)) {
            $this->aiAllowedRoles = array_values(array_diff($this->aiAllowedRoles, [$role]));
        } else {
            $this->aiAllowedRoles[] = $role;
        }
    }

    public function render()
    {
        return view('aitools::livewire.aitools-settings');
    }
}
