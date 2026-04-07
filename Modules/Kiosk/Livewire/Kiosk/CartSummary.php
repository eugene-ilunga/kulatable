<?php

namespace Modules\Kiosk\Livewire\Kiosk;

use Livewire\Component;
use Modules\Kiosk\Services\KioskCartService;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Kiosk\Entities\Kiosk;

class CartSummary extends Component
{
    use LivewireAlert;
    
    protected $listeners = ['refreshCartSummary' => '$refresh'];
    
    public $restaurant;
    public $shopBranch;
    public $kioskId;
    
    // Customer Information
    public $customerName = '';
    public $customerEmail = '';
    public $customerPhone = '';
    public $pickupTime = '15';
    public $orderType = 'dine_in';

    // Requirement flags from Kiosk settings
    public $requireName = true;
    public $requireEmail = false;
    public $requirePhone = true;

    public function mount($restaurant, $shopBranch, $kioskId = null)
    {
        $this->restaurant = $restaurant;
        $this->shopBranch = $shopBranch;
        $this->kioskId = $kioskId ?: session('kiosk_id');

        if ($this->kioskId) {
            $kiosk = Kiosk::find($this->kioskId);
            if ($kiosk) {
                $this->requireName = (bool) ($kiosk->require_name ?? true);
                $this->requireEmail = (bool) ($kiosk->require_email ?? false);
                $this->requirePhone = (bool) ($kiosk->require_phone ?? true);
            }
        }
    }

    public function removeFromCart($itemId)
    {
        $cartService = new KioskCartService();
        $result = $cartService->removeKioskItem($this->shopBranch->id, $itemId);
        
        if ($result['success']) {
            $this->alert('success', 'Item removed from cart', [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 2000,
            ]);
        }
    }

    public function updateQuantity($itemId, $change)
    {
        $cartService = new KioskCartService();
        $result = $cartService->updateKioskItemQuantity($itemId, $change);
        
        if ($result['success']) {
            $this->alert('success', $result['message'], [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 1500,
            ]);
        }
    }

    public function proceedToPayment()
    {
        $rules = [];

        $rules['customerName'] = ($this->requireName ? 'required' : 'nullable') . '|string|min:2';
        $rules['customerEmail'] = ($this->requireEmail ? 'required' : 'nullable') . '|email';
        $rules['customerPhone'] = ($this->requirePhone ? 'required' : 'nullable') . '|string|min:10';

        $this->validate($rules);

        
        session(['customerInfo' => [
            'name' => $this->customerName,
            'email' => $this->customerEmail,
            'phone' => $this->customerPhone,
            'pickup_time' => $this->pickupTime,
        ]]);
        
        // Dispatch event to proceed to payment screen
        $this->dispatch('proceedToPayment');
    }

    public function backToMenu()
    {
        $this->dispatch('showMenuScreen');
    }

    public function render()
    {
        $kioskService = new KioskCartService();
        $cartItemList = $kioskService->getKioskCartSummary($this->shopBranch->id);
        
        $subtotal = $cartItemList['sub_total'];
        $total = $cartItemList['total'];
        $totalTaxAmount = $cartItemList['total_tax_amount'];
        $taxBreakdown = $cartItemList['tax_breakdown'];
        $taxMode = $cartItemList['tax_mode'];
        $cartCount = $cartItemList['count'];

        return view('kiosk::livewire.kiosk.cart-summary', [
            'cartItemList' => $cartItemList,
            'subtotal' => $subtotal,
            'total' => $total,
            'totalTaxAmount' => $totalTaxAmount,
            'taxBreakdown' => $taxBreakdown,
            'taxMode' => $taxMode,
            'cartCount' => $cartCount,
        ]);
    }
}
