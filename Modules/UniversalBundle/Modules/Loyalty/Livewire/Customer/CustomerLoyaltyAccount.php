<?php

namespace Modules\Loyalty\Livewire\Customer;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use Modules\Loyalty\Entities\LoyaltyAccount;
use Modules\Loyalty\Entities\LoyaltyLedger;
use Modules\Loyalty\Entities\LoyaltySetting;
use Modules\Loyalty\Entities\LoyaltyTier;
use Modules\Loyalty\Services\LoyaltyService;

class CustomerLoyaltyAccount extends Component
{
    use WithPagination;

    public Customer $customer;
    public $account;
    public $settings;
    public $pointsBalance = 0;
    public $pointsValue = 0;
    public $currentTier = null;
    public $nextTier = null;
    public $pointsToNextTier = null;
    public $tierProgress = 0;
    public $customerStamps = [];

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
        $this->loadAccount();
    }

    protected function loadAccount()
    {
        $restaurantId = restaurant()->id;
        
        // Check if module is enabled
        if (!function_exists('module_enabled') || !module_enabled('Loyalty')) {
            return;
        }

        // Check if Loyalty module is in restaurant's package
        if (function_exists('restaurant_modules')) {
            $restaurantModules = restaurant_modules();
            if (!in_array('Loyalty', $restaurantModules)) {
                return;
            }
        }

        $loyaltyService = app(LoyaltyService::class);
        $this->account = $loyaltyService->getOrCreateAccount($restaurantId, $this->customer->id);
        $this->pointsBalance = $this->account->points_balance ?? 0;

        $this->settings = LoyaltySetting::getForRestaurant($restaurantId);
        
        // Calculate points value only if settings exist and points are enabled
        if ($this->settings && $this->settings->enable_points) {
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
        if ($this->settings && $this->settings->enable_stamps) {
            $this->loadStampInformation($restaurantId);
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
    
    protected function loadStampInformation($restaurantId)
    {
        $loyaltyService = app(LoyaltyService::class);
        $this->customerStamps = $loyaltyService->getCustomerStamps($restaurantId, $this->customer->id);
    }

    public function render()
    {
        $restaurantId = restaurant()->id;
        
        $ledgerEntries = LoyaltyLedger::where('restaurant_id', $restaurantId)
            ->where('customer_id', $this->customer->id)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('loyalty::livewire.customer.loyalty-account', [
            'ledgerEntries' => $ledgerEntries
        ]);
    }
}

