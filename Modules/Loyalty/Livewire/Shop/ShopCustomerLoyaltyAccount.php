<?php

namespace Modules\Loyalty\Livewire\Shop;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Loyalty\Entities\LoyaltyAccount;
use Modules\Loyalty\Entities\LoyaltyLedger;
use Modules\Loyalty\Entities\LoyaltySetting;
use Modules\Loyalty\Entities\LoyaltyTier;
use Modules\Loyalty\Services\LoyaltyService;

class ShopCustomerLoyaltyAccount extends Component
{
    use WithPagination;

    public $account;
    public $settings;
    public $pointsBalance = 0;
    public $pointsValue = 0;
    public $currentTier = null;
    public $nextTier = null;
    public $pointsToNextTier = null;
    public $tierProgress = 0;
    public $customerStamps = [];
    public $enablePoints = false;
    public $enableStamps = false;

    public $restaurant;
    public $shopBranch;

    public function mount($restaurant = null, $shopBranch = null)
    {
        // Use passed restaurant or try to get from helper
        if (!$this->restaurant && $restaurant) {
            $this->restaurant = $restaurant;
        } elseif (!$this->restaurant && function_exists('restaurant')) {
            $this->restaurant = restaurant();
        }
        
        if (!$this->shopBranch && $shopBranch) {
            $this->shopBranch = $shopBranch;
        }
        
        $this->loadAccount();
    }

    protected function loadAccount()
    {
        // Get logged-in customer
        $customer = customer();
        if (!$customer) {
            return;
        }

        // Use component's restaurant property or try helper function
        $restaurantId = $this->restaurant->id ?? (restaurant()->id ?? null);
        if (!$restaurantId) {
            return;
        }
        
        // Check if module is enabled
        if (!function_exists('module_enabled') || !module_enabled('Loyalty')) {
            return;
        }

        // Check if Loyalty module is in restaurant's package
        if (function_exists('restaurant_modules')) {
            // Use component's restaurant or get from helper
            $restaurantForModules = $this->restaurant ?? (function_exists('restaurant') ? restaurant() : null);
            if ($restaurantForModules) {
                $restaurantModules = restaurant_modules($restaurantForModules);
                if (!in_array('Loyalty', $restaurantModules)) {
                    return;
                }
            } else {
                // If no restaurant available, can't check modules
                return;
            }
        }

        $loyaltyService = app(LoyaltyService::class);
        $this->account = $loyaltyService->getOrCreateAccount($restaurantId, $customer->id);
        $this->pointsBalance = $this->account->points_balance ?? 0;

        $this->settings = LoyaltySetting::getForRestaurant($restaurantId);
        
        if ($this->settings) {
            // Check if loyalty is enabled
            if (!$this->settings->isEnabled()) {
                return;
            }
            
            // Check if points or stamps are enabled for customer site
            $pointsEnabledForSite = ($this->settings->enable_points ?? false) && ($this->settings->enable_points_for_customer_site ?? true);
            $stampsEnabledForSite = ($this->settings->enable_stamps ?? false) && ($this->settings->enable_stamps_for_customer_site ?? true);
            
            // Set enabled flags (even if both are false, allow page to load)
            $this->enablePoints = $pointsEnabledForSite;
            $this->enableStamps = $stampsEnabledForSite;
            
            // Only load account data if at least one feature is enabled
            if (!$pointsEnabledForSite && !$stampsEnabledForSite) {
                // Both disabled - don't load account data, view will show appropriate message
                return;
            }
            
            // Calculate points value only if settings exist and points are enabled
            if ($this->enablePoints) {
                $valuePerPoint = $this->settings->value_per_point ?? 1;
                // Ensure value_per_point is at least 1 to avoid 0 calculations
                if ($valuePerPoint <= 0) {
                    $valuePerPoint = 1;
                }
                $this->pointsValue = $this->pointsBalance * $valuePerPoint;
                
                // Load tier information if points are enabled
                $this->loadTierInformation($restaurantId);
            } else {
                $this->pointsValue = 0;
            }
            
            // Load stamp information if stamps are enabled
            if ($this->enableStamps) {
                $this->loadStampInformation($restaurantId, $customer->id);
            }
        }
    }
    
    protected function loadTierInformation($restaurantId)
    {
        // Get current tier
        $this->currentTier = LoyaltyTier::getTierForPoints($restaurantId, $this->pointsBalance);
        
        if ($this->currentTier) {
            // Update account tier if needed
            if ($this->account->tier_id != $this->currentTier->id) {
                $this->account->tier_id = $this->currentTier->id;
                $this->account->save();
            }
            
            // Get next tier
            $this->nextTier = $this->currentTier->getNextTier();
            
            if ($this->nextTier) {
                $this->pointsToNextTier = $this->currentTier->getPointsToNextTier($this->pointsBalance);
                
                // Calculate progress percentage
                $pointsInCurrentTier = $this->pointsBalance - $this->currentTier->min_points;
                $pointsNeededForNextTier = $this->nextTier->min_points - $this->currentTier->min_points;
                if ($pointsNeededForNextTier > 0) {
                    $this->tierProgress = min(100, ($pointsInCurrentTier / $pointsNeededForNextTier) * 100);
                }
            } else {
                // Already at highest tier
                $this->tierProgress = 100;
            }
        } else {
            // No tier found, get default tier
            $defaultTier = LoyaltyTier::where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->orderBy('min_points', 'asc')
                ->first();
            
            if ($defaultTier) {
                $this->currentTier = $defaultTier;
                $this->nextTier = $defaultTier->getNextTier();
                if ($this->nextTier) {
                    $this->pointsToNextTier = max(0, $this->nextTier->min_points - $this->pointsBalance);
                }
            }
        }
    }
    
    protected function loadStampInformation($restaurantId, $customerId)
    {
        $loyaltyService = app(LoyaltyService::class);
        $this->customerStamps = $loyaltyService->getCustomerStamps($restaurantId, $customerId);
    }

    public function render()
    {
        $customer = customer();
        $restaurant = $this->restaurant ?? (function_exists('restaurant') ? restaurant() : null);
        
        if (!$customer) {
            return view('loyalty::livewire.shop.customer-loyalty-account', [
                'ledgerEntries' => collect([]),
                'restaurant' => $restaurant
            ]);
        }

        // Use component's restaurant property or try helper function
        $restaurantId = $restaurant->id ?? null;

        if (!$restaurantId) {
            return view('loyalty::livewire.shop.customer-loyalty-account', [
                'ledgerEntries' => collect([]),
                'restaurant' => $restaurant
            ]);
        }
        
        $ledgerEntries = LoyaltyLedger::where('restaurant_id', $restaurantId)
            ->where('customer_id', $customer->id)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('loyalty::livewire.shop.customer-loyalty-account', [
            'ledgerEntries' => $ledgerEntries,
            'restaurant' => $restaurant
        ]);
    }
}
