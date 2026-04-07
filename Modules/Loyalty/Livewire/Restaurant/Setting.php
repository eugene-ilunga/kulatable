<?php

namespace Modules\Loyalty\Livewire\Restaurant;

use Livewire\Component;
use Modules\Loyalty\Entities\LoyaltySetting;
use Modules\Loyalty\Entities\LoyaltyTier;
use Modules\Loyalty\Entities\LoyaltyStampRule;
use App\Models\MenuItem;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class Setting extends Component
{
    use LivewireAlert;

    public $activeTab = 'points'; // 'tiers', 'points', 'stamps'
    
    // General settings
    public $enabled = true;
    public $loyalty_type = 'points'; // 'points', 'stamps', 'both'
    public $enable_points = true;
    public $enable_stamps = false;
    public $enable_for_pos = true;
    public $enable_for_customer_site = true;
    public $enable_for_kiosk = true;
    // Separate platform fields for points
    public $enable_points_for_pos = true;
    public $enable_points_for_customer_site = true;
    public $enable_points_for_kiosk = true;
    // Separate platform fields for stamps
    public $enable_stamps_for_pos = true;
    public $enable_stamps_for_customer_site = true;
    public $enable_stamps_for_kiosk = true;
    public $isKioskModuleEnabled = false;
    
    // Points settings
    public $earn_rate_rupees = 100;
    public $earn_rate_points = 1;
    public $value_per_point = 1;
    public $min_redeem_points = 50;
    public $max_discount_percent = 20;
    
    // Tiers management
    public $tiers = [];
    public $editingTierId = null;
    public $tierForm = [
        'name' => '',
        'color' => '#8B7355',
        'icon' => '',
        'min_points' => 0,
        'max_points' => null,
        'earning_multiplier' => 1.00,
        'redemption_multiplier' => 1.00,
        'description' => '',
        'is_active' => true,
    ];
    
    // Stamps management
    public $stampRules = [];
    public $editingStampRuleId = null;
    public $stampRuleForm = [
        'menu_item_id' => null,
        'stamps_required' => 1,
        'reward_type' => 'free_item',
        'reward_value' => null,
        'reward_menu_item_id' => null,
        'reward_menu_item_variation_id' => null,
        'description' => '',
        'is_active' => true,
    ];
    public $menuItems = [];
    public $rewardMenuItemVariations = [];

    public function mount()
    {
        // Check if module is enabled
        if (!function_exists('module_enabled') || !module_enabled('Loyalty')) {
            abort(404, __('loyalty::app.loyaltyModuleNotEnabled'));
        }

        // Check if Loyalty module is in restaurant's package
        if (function_exists('restaurant_modules')) {
            $restaurantModules = restaurant_modules();
            if (!in_array('Loyalty', $restaurantModules)) {
                abort(404, __('loyalty::app.loyaltyModuleNotInPackage'));
            }
        }

        $this->loadSettings();
        $this->loadTiers();
        $this->loadStampRules();
        $this->loadMenuItems();
        
        // Set active tab based on loyalty_type
        if ($this->loyalty_type === 'stamps') {
            $this->activeTab = 'stamps';
        } elseif ($this->loyalty_type === 'points') {
            $this->activeTab = 'points';
        } elseif ($this->loyalty_type === 'both') {
            // For 'both', default to points tab if it's enabled, otherwise stamps
            if ($this->enable_points) {
                $this->activeTab = 'points';
            } elseif ($this->enable_stamps) {
                $this->activeTab = 'stamps';
            }
        }
        
        // Check if Kiosk module is enabled and in restaurant's package
        $this->isKioskModuleEnabled = false;
        if (function_exists('module_enabled') && module_enabled('Kiosk')) {
            if (function_exists('restaurant_modules')) {
                $restaurantModules = restaurant_modules();
                $this->isKioskModuleEnabled = in_array('Kiosk', $restaurantModules);
            }
        }
    }
    
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    protected function loadTiers()
    {
        $restaurantId = restaurant()->id;
        $this->tiers = LoyaltyTier::where('restaurant_id', $restaurantId)
            ->orderBy('min_points', 'asc')
            ->get()
            ->toArray();
    }
    
    protected function loadStampRules()
    {
        $restaurantId = restaurant()->id;
        $this->stampRules = LoyaltyStampRule::where('restaurant_id', $restaurantId)
            ->with(['menuItem', 'rewardMenuItem', 'rewardMenuItemVariation'])
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }
    
    protected function loadMenuItems()
    {
        $restaurantId = restaurant()->id;
        $branchIds = \App\Models\Branch::where('restaurant_id', $restaurantId)->pluck('id');
        
        $this->menuItems = MenuItem::whereIn('branch_id', $branchIds)
            ->select('id', 'item_name')
            ->orderBy('item_name', 'asc')
            ->get()
            ->toArray();
    }

    protected function loadSettings()
    {
        $restaurantId = restaurant()->id;
        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        $this->enabled = $settings->enabled;
        $this->loyalty_type = $settings->loyalty_type ?? 'points';
        $this->enable_points = $settings->enable_points ?? true;
        $this->enable_stamps = $settings->enable_stamps ?? false;
        $this->enable_for_pos = $settings->enable_for_pos ?? true;
        $this->enable_for_customer_site = $settings->enable_for_customer_site ?? true;
        $this->enable_for_kiosk = $settings->enable_for_kiosk ?? true;
        // Load separate platform settings for points
        $this->enable_points_for_pos = $settings->enable_points_for_pos ?? ($settings->enable_for_pos ?? true);
        $this->enable_points_for_customer_site = $settings->enable_points_for_customer_site ?? ($settings->enable_for_customer_site ?? true);
        $this->enable_points_for_kiosk = $settings->enable_points_for_kiosk ?? ($settings->enable_for_kiosk ?? true);
        // Load separate platform settings for stamps
        $this->enable_stamps_for_pos = $settings->enable_stamps_for_pos ?? ($settings->enable_for_pos ?? true);
        $this->enable_stamps_for_customer_site = $settings->enable_stamps_for_customer_site ?? ($settings->enable_for_customer_site ?? true);
        $this->enable_stamps_for_kiosk = $settings->enable_stamps_for_kiosk ?? ($settings->enable_for_kiosk ?? true);
        $this->earn_rate_rupees = $settings->earn_rate_rupees;
        $this->earn_rate_points = $settings->earn_rate_points;
        $this->value_per_point = $settings->value_per_point;
        $this->min_redeem_points = $settings->min_redeem_points;
        $this->max_discount_percent = $settings->max_discount_percent;
    }

    public function updatedLoyaltyType($value)
    {
        // Automatically set enable_points and enable_stamps based on loyalty_type
        if ($value === 'points') {
            $this->enable_points = true;
            $this->enable_stamps = false;
            $this->activeTab = 'points'; // Switch to points tab
        } elseif ($value === 'stamps') {
            $this->enable_points = false;
            $this->enable_stamps = true;
            $this->activeTab = 'stamps'; // Switch to stamps tab
        } elseif ($value === 'both') {
            $this->enable_points = true;
            $this->enable_stamps = true;
            // Keep current tab or default to points
            if (!in_array($this->activeTab, ['tiers', 'points', 'stamps'])) {
                $this->activeTab = 'points';
            }
        }
    }
    
    public function save()
    {
        $rules = [
            'enabled' => 'boolean',
            'loyalty_type' => 'required|in:points,stamps,both',
            'enable_points' => 'boolean',
            'enable_stamps' => 'boolean',
            'enable_for_pos' => 'boolean',
            'enable_for_customer_site' => 'boolean',
            'enable_points_for_pos' => 'boolean',
            'enable_points_for_customer_site' => 'boolean',
            'enable_stamps_for_pos' => 'boolean',
            'enable_stamps_for_customer_site' => 'boolean',
            'earn_rate_rupees' => 'required|numeric|min:0.01',
            'earn_rate_points' => 'required|integer|min:1',
            'value_per_point' => 'required|numeric|min:0.01',
            'min_redeem_points' => 'required|integer|min:1',
            'max_discount_percent' => 'required|numeric|min:0|max:100',
        ];
        
        // Only validate kiosk fields if Kiosk module is enabled
        if ($this->isKioskModuleEnabled) {
            $rules['enable_for_kiosk'] = 'boolean';
            $rules['enable_points_for_kiosk'] = 'boolean';
            $rules['enable_stamps_for_kiosk'] = 'boolean';
        }
        
        $this->validate($rules);

        $restaurantId = restaurant()->id;
        $settings = LoyaltySetting::getForRestaurant($restaurantId);

        $updateData = [
            'enabled' => $this->enabled,
            'loyalty_type' => $this->loyalty_type,
            'enable_points' => $this->enable_points,
            'enable_stamps' => $this->enable_stamps,
            'enable_for_pos' => $this->enable_for_pos,
            'enable_for_customer_site' => $this->enable_for_customer_site,
            'enable_points_for_pos' => $this->enable_points_for_pos,
            'enable_points_for_customer_site' => $this->enable_points_for_customer_site,
            'enable_stamps_for_pos' => $this->enable_stamps_for_pos,
            'enable_stamps_for_customer_site' => $this->enable_stamps_for_customer_site,
            'earn_rate_rupees' => $this->earn_rate_rupees,
            'earn_rate_points' => $this->earn_rate_points,
            'value_per_point' => $this->value_per_point,
            'min_redeem_points' => $this->min_redeem_points,
            'max_discount_percent' => $this->max_discount_percent,
        ];
        
        // Only update kiosk fields if Kiosk module is enabled
        if ($this->isKioskModuleEnabled) {
            $updateData['enable_for_kiosk'] = $this->enable_for_kiosk;
            $updateData['enable_points_for_kiosk'] = $this->enable_points_for_kiosk;
            $updateData['enable_stamps_for_kiosk'] = $this->enable_stamps_for_kiosk;
        }
        
        $settings->update($updateData);

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }
    
    // Tier Management Methods
    public function openTierModal($tierId = null)
    {
        $this->editingTierId = $tierId;
        if ($tierId) {
            $tier = LoyaltyTier::find($tierId);
            $this->tierForm = [
                'name' => $tier->name,
                'color' => $tier->color,
                'icon' => $tier->icon ?? '',
                'min_points' => $tier->min_points,
                'max_points' => $tier->max_points,
                'earning_multiplier' => $tier->earning_multiplier,
                'redemption_multiplier' => $tier->redemption_multiplier,
                'description' => $tier->description ?? '',
                'is_active' => $tier->is_active,
            ];
        } else {
            $this->tierForm = [
                'name' => '',
                'color' => '#8B7355',
                'icon' => '',
                'min_points' => 0,
                'max_points' => null,
                'earning_multiplier' => 1.00,
                'redemption_multiplier' => 1.00,
                'description' => '',
                'is_active' => true,
            ];
        }
        $this->dispatch('open-tier-modal');
    }
    
    public function saveTier()
    {
        $rules = [
            'tierForm.name' => 'required|string|max:255',
            'tierForm.color' => 'required|string|max:20',
            'tierForm.min_points' => 'required|integer|min:0',
            'tierForm.max_points' => 'nullable|integer|min:0|gt:tierForm.min_points',
            'tierForm.earning_multiplier' => 'required|numeric|min:0.01|max:10',
            'tierForm.redemption_multiplier' => 'required|numeric|min:0.01|max:10',
            'tierForm.is_active' => 'boolean',
        ];
        
        $attributes = [
            'tierForm.name' => __('loyalty::app.tierName'),
            'tierForm.color' => __('loyalty::app.tierColor'),
            'tierForm.min_points' => __('loyalty::app.minPoints'),
            'tierForm.max_points' => __('loyalty::app.maxPoints'),
            'tierForm.earning_multiplier' => __('loyalty::app.earningMultiplier'),
            'tierForm.redemption_multiplier' => __('loyalty::app.redemptionMultiplier'),
        ];
        
        $this->validate($rules, [], $attributes);
        
        $restaurantId = restaurant()->id;
        $data = $this->tierForm;
        $data['restaurant_id'] = $restaurantId;
        
        // Calculate order based on min_points
        $data['order'] = $data['min_points'];
        
        if ($this->editingTierId) {
            $tier = LoyaltyTier::find($this->editingTierId);
            $tier->update($data);
            $this->alert('success', __('loyalty::app.tierUpdated'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        } else {
            LoyaltyTier::create($data);
            $this->alert('success', __('loyalty::app.tierCreated'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
        
        $this->loadTiers();
        $this->dispatch('close-tier-modal');
        $this->editingTierId = null;
    }
    
    public function deleteTier($tierId)
    {
        $tier = LoyaltyTier::find($tierId);
        if ($tier) {
            $tier->delete();
            $this->alert('success', __('loyalty::app.tierDeleted'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            $this->loadTiers();
        }
    }
    
    public function updateTierOrder($tierIds)
    {
        foreach ($tierIds as $index => $tierId) {
            LoyaltyTier::where('id', $tierId)->update(['order' => $index]);
        }
        $this->loadTiers();
    }
    
    // Stamp Rule Management Methods
    public function openStampRuleModal($stampRuleId = null)
    {
        $this->editingStampRuleId = $stampRuleId;
        if ($stampRuleId) {
            $rule = LoyaltyStampRule::find($stampRuleId);
            $this->stampRuleForm = [
                'menu_item_id' => $rule->menu_item_id,
                'stamps_required' => $rule->stamps_required,
                'reward_type' => $rule->reward_type,
                'reward_value' => $rule->reward_value,
                'reward_menu_item_id' => $rule->reward_menu_item_id,
                'reward_menu_item_variation_id' => $rule->reward_menu_item_variation_id,
                'description' => $rule->description ?? '',
                'is_active' => $rule->is_active,
            ];
            
            // Load variations if reward menu item is set
            if ($rule->reward_menu_item_id) {
                $this->loadRewardMenuItemVariations($rule->reward_menu_item_id);
            } else {
                $this->rewardMenuItemVariations = [];
            }
        } else {
            $this->stampRuleForm = [
                'menu_item_id' => null,
                'stamps_required' => 1,
                'reward_type' => 'free_item',
                'reward_value' => null,
                'reward_menu_item_id' => null,
                'reward_menu_item_variation_id' => null,
                'description' => '',
                'is_active' => true,
            ];
            $this->rewardMenuItemVariations = [];
        }
        $this->dispatch('open-stamp-rule-modal');
    }
    
    public function updatedStampRuleFormRewardType($value)
    {
        // Clear irrelevant fields when reward type changes
        if ($value === 'free_item') {
            $this->stampRuleForm['reward_value'] = null;
        } else {
            $this->stampRuleForm['reward_menu_item_id'] = null;
            $this->stampRuleForm['reward_menu_item_variation_id'] = null;
            $this->rewardMenuItemVariations = [];
        }
    }
    
    public function updatedStampRuleFormRewardMenuItemId($value)
    {
        // Load variations when reward menu item is selected
        if ($value) {
            $this->loadRewardMenuItemVariations($value);
        } else {
            $this->rewardMenuItemVariations = [];
            $this->stampRuleForm['reward_menu_item_variation_id'] = null;
        }
    }
    
    protected function loadRewardMenuItemVariations($menuItemId)
    {
        $menuItem = \App\Models\MenuItem::with('variations')->find($menuItemId);
        if ($menuItem && $menuItem->variations && $menuItem->variations->count() > 0) {
            $this->rewardMenuItemVariations = $menuItem->variations->map(function($variation) {
                return [
                    'id' => $variation->id,
                    'variation_name' => $variation->variation ?? 'Variation #' . $variation->id,
                    'price' => $variation->price ?? 0,
                ];
            })->toArray();
        } else {
            $this->rewardMenuItemVariations = [];
            // If no variations, clear the variation_id
            $this->stampRuleForm['reward_menu_item_variation_id'] = null;
        }
    }
    
    public function saveStampRule()
    {
        $rules = [
            'stampRuleForm.menu_item_id' => 'required|exists:menu_items,id',
            'stampRuleForm.stamps_required' => 'required|integer|min:1',
            'stampRuleForm.reward_type' => 'required|in:free_item,discount_percent,discount_amount',
            'stampRuleForm.is_active' => 'boolean',
        ];
        
        if ($this->stampRuleForm['reward_type'] == 'free_item') {
            $rules['stampRuleForm.reward_menu_item_id'] = 'required|exists:menu_items,id';
            // Variation is mandatory if the menu item has variations
            if (!empty($this->rewardMenuItemVariations) && count($this->rewardMenuItemVariations) > 0) {
                $rules['stampRuleForm.reward_menu_item_variation_id'] = 'required|exists:menu_item_variations,id';
            }
        } elseif (in_array($this->stampRuleForm['reward_type'], ['discount_percent', 'discount_amount'])) {
            $rules['stampRuleForm.reward_value'] = 'required|numeric|min:0.01';
        }
        
        $attributes = [
            'stampRuleForm.menu_item_id' => __('loyalty::app.menuItem'),
            'stampRuleForm.stamps_required' => __('loyalty::app.stampsRequired'),
            'stampRuleForm.reward_type' => __('loyalty::app.rewardType'),
            'stampRuleForm.reward_value' => __('loyalty::app.rewardValue'),
            'stampRuleForm.reward_menu_item_id' => __('loyalty::app.rewardMenuItem'),
            'stampRuleForm.reward_menu_item_variation_id' => __('modules.menu.variationName'),
        ];
        
        $this->validate($rules, [], $attributes);
        
        $restaurantId = restaurant()->id;
        $data = $this->stampRuleForm;
        $data['restaurant_id'] = $restaurantId;
        
        // If no variation selected and menu item has no variations, set to null
        if (empty($data['reward_menu_item_variation_id']) && (empty($this->rewardMenuItemVariations) || count($this->rewardMenuItemVariations) == 0)) {
            $data['reward_menu_item_variation_id'] = null;
        }
        
        // Check for duplicate menu_item_id
        $existing = LoyaltyStampRule::where('restaurant_id', $restaurantId)
            ->where('menu_item_id', $data['menu_item_id'])
            ->where('id', '!=', $this->editingStampRuleId)
            ->first();
        
        if ($existing) {
            $this->alert('error', __('loyalty::app.stampRuleAlreadyExists'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }
        
        if ($this->editingStampRuleId) {
            $rule = LoyaltyStampRule::find($this->editingStampRuleId);
            $rule->update($data);
            $this->alert('success', __('loyalty::app.stampRuleUpdated'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        } else {
            LoyaltyStampRule::create($data);
            $this->alert('success', __('loyalty::app.stampRuleCreated'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
        
        $this->loadStampRules();
        $this->dispatch('close-stamp-rule-modal');
        $this->editingStampRuleId = null;
    }
    
    public function deleteStampRule($stampRuleId)
    {
        $rule = LoyaltyStampRule::find($stampRuleId);
        if ($rule) {
            $rule->delete();
            $this->alert('success', __('loyalty::app.stampRuleDeleted'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            $this->loadStampRules();
        }
    }

    public function render()
    {
        return view('loyalty::livewire.restaurant.loyalty-settings');
    }
}

