<?php

namespace Modules\Kiosk\Livewire\Kiosk;

use Livewire\Component;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Kiosk\Services\KioskCartService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTax;
use App\Models\OrderType;
use App\Models\Tax;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Payment;
use App\Models\OrderCharge;
use App\Events\NewOrderCreated;
use App\Events\OrderUpdated;
use App\Models\Customer;
use App\Models\PaymentGatewayCredential;
use App\Models\OfflinePaymentMethod;
use Razorpay\Api\Api;
use App\Models\RazorpayPayment;
use App\Models\StripePayment;
use App\Models\FlutterwavePayment;
use App\Models\PaypalPayment;
use App\Models\AdminPayfastPayment;
use App\Models\AdminPaystackPayment;
use App\Models\XenditPayment;
use App\Models\EpayPayment;
use App\Models\AdminMolliePayment;
use App\Models\TapPayment;
use App\Services\RestaurantAvailabilityService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mollie\Api\MollieApiClient;
use App\Models\RestaurantCharge;
use Modules\Loyalty\Services\KioskLoyaltyHandler;


class PaymentMethod extends Component
{
    use LivewireAlert;
    
    // Check if loyalty module is available
    protected function isLoyaltyModuleAvailable()
    {
        return module_enabled('Loyalty');
    }

    protected function loyaltyHandler(): ?KioskLoyaltyHandler
    {
        if (!$this->isLoyaltyModuleAvailable()) {
            return null;
        }

        return new KioskLoyaltyHandler($this);
    }

    public function isLoyaltyEnabled(): bool
    {
        $handler = $this->loyaltyHandler();
        return $handler ? $handler->isLoyaltyEnabled() : false;
    }

    public function isPointsEnabledForKiosk(): bool
    {
        $handler = $this->loyaltyHandler();
        return $handler ? $handler->isPointsEnabledForKiosk() : false;
    }

    public function isStampsEnabledForKiosk(): bool
    {
        $handler = $this->loyaltyHandler();
        return $handler ? $handler->isStampsEnabledForKiosk() : false;
    }

    public function getApplicableKioskCharges(string $orderType): \Illuminate\Support\Collection
    {
        return RestaurantCharge::withoutGlobalScopes()
            ->where('restaurant_id', $this->restaurant->id ?? null)
            ->where('is_enabled', true)
            ->whereJsonContains('order_types', $orderType)
            ->get();
    }

    protected function calculateTotalsWithoutLoyalty(array $cartItemList): array
    {
        $discountedSubtotal = $cartItemList['sub_total'] ?? 0;
        $originalSubtotal = $discountedSubtotal;
        $taxMode = $cartItemList['tax_mode'] ?? 'order';

        // Step 1: Apply service charges on net subtotal
        $serviceTotal = 0;
        $chargesBreakdown = [];
        $orderType = $cartItemList['order_type'] ?? 'dine_in';
        $charges = $this->getApplicableKioskCharges($orderType);
        foreach ($charges as $charge) {
            if (is_object($charge) && method_exists($charge, 'getAmount')) {
                $chargeAmount = $charge->getAmount($discountedSubtotal);
                $serviceTotal += $chargeAmount;
                $chargesBreakdown[] = [
                    'name' => $charge->charge_name ?? __('charges.charge'),
                    'amount' => round($chargeAmount, 2),
                ];
            }
        }

        // Step 2: Calculate taxes on tax base
        $totalTaxAmount = 0;
        $taxBreakdown = [];
        $taxBase = null;

        if ($taxMode === 'order') {
            $taxes = Tax::withoutGlobalScopes()
                ->where('restaurant_id', $this->restaurant->id)
                ->get();

            $includeChargesInTaxBase = $this->restaurant->include_charges_in_tax_base ?? true;
            $taxBase = $includeChargesInTaxBase ? ($discountedSubtotal + $serviceTotal) : $discountedSubtotal;

            foreach ($taxes as $tax) {
                $taxAmount = ($tax->tax_percent / 100) * $taxBase;
                $totalTaxAmount += $taxAmount;
                $taxBreakdown[$tax->tax_name] = [
                    'percent' => $tax->tax_percent,
                    'amount' => round($taxAmount, 2),
                ];
            }
        } else {
            $totalTaxAmount = $cartItemList['total_tax_amount'] ?? 0;
            $taxBreakdown = $cartItemList['tax_breakdown'] ?? [];
        }

        // Step 3: Build total
        $total = $discountedSubtotal + $serviceTotal;
        if ($taxMode === 'order') {
            $total += $totalTaxAmount;
        } else {
            $isInclusive = $this->restaurant->tax_inclusive ?? false;
            if (!$isInclusive) {
                $total += $totalTaxAmount;
            }
        }

        return [
            'subtotal' => $originalSubtotal,
            'discountedSubtotal' => $discountedSubtotal,
            'total' => $total,
            'totalTaxAmount' => $totalTaxAmount,
            'taxBreakdown' => $taxBreakdown,
            'serviceTotal' => $serviceTotal,
            'chargeBreakdown' => $chargesBreakdown,
            'taxBase' => $taxBase,
        ];
    }
    
    public $restaurant;
    public $shopBranch;
    public $paymentMethod;
    public $kioskId;
    public $paymentGateway;
    public bool $showQrCode = false;
    public bool $showPaymentModal = false;
    public bool $showOfflineModal = false;
    public $selectedOnlineGateway = null;
    public $offlinePaymentMethods = [];
    public $selectedOfflineMethodId = null;
    // Do NOT keep a separate public $total property because it conflicts
    // with the $total value computed in render() and passed to the view.
    
    // Customer information properties
    public $customerName = null;
    public $customerEmail = null;
    public $customerPhone = null;
    public $pickupTime = '15';
    public $customerId = null;
    public $customer = null;

    // Loyalty/stamp properties (used by KioskLoyaltyHandler and views)
    public $loyaltyPointsRedeemed = 0;
    public $loyaltyDiscountAmount = 0;
    public $availableLoyaltyPoints = 0;
    public $pointsToRedeem = 0;
    public $maxRedeemablePoints = 0;
    public $minRedeemPoints = 0;
    public $loyaltyPointsValue = 0;
    public $maxLoyaltyDiscount = 0;

    public $customerStamps = [];
    public $selectedStampRuleId = null;
    public $selectedStampRuleIds = [];
    public $stampDiscountAmount = 0;
    public $stampDiscountBreakdown = [];
    public $stampRedemptionCounts = [];

    protected $listeners = [
        'refreshPaymentMethod' => '$refresh'
    ];

    public function mount($restaurant, $shopBranch, $kioskId = null)
    {
        $this->paymentGateway = PaymentGatewayCredential::where('restaurant_id', $restaurant->id)->first();
        $this->restaurant = $restaurant;
        $this->shopBranch = $shopBranch;
        $this->paymentMethod = 'due';
        $this->kioskId = $kioskId ?: session('kiosk_id'); 
        $this->offlinePaymentMethods = OfflinePaymentMethod::where('restaurant_id', $restaurant->id)->where('status', 'active')->orderBy('created_at', 'desc')->get();
        
        // Load customer info from session
        $this->loadCustomerAndLoyalty();
    }
    
    protected function loadCustomerAndLoyalty()
    {
        $customerInfo = session('customerInfo', []);
        $this->customerName = $customerInfo['name'] ?? null;
        $this->customerEmail = $customerInfo['email'] ?? null;
        $this->customerPhone = $customerInfo['phone'] ?? null;
        $this->pickupTime = $customerInfo['pickup_time'] ?? '15';
        
        // Fix: If email is empty but phone looks like an email, swap them
        if (empty($this->customerEmail) && !empty($this->customerPhone) && filter_var($this->customerPhone, FILTER_VALIDATE_EMAIL)) {
            $this->customerEmail = $this->customerPhone;
            $this->customerPhone = null;
        }
        
        // Try to find customer to get ID for loyalty
        if (!empty($this->customerEmail) || !empty($this->customerPhone)) {
            // First try by email
            if (!empty($this->customerEmail)) {
                $this->customer = Customer::where('email', $this->customerEmail)
                    ->where('restaurant_id', $this->restaurant->id)
                    ->first();
            }
            
            // If not found by email, try by phone
            if (!$this->customer && !empty($this->customerPhone)) {
                $this->customer = Customer::where('phone', $this->customerPhone)
                    ->where('restaurant_id', $this->restaurant->id)
                    ->first();
            }
            
            // If still not found and phone looks like email, try phone as email
            if (!$this->customer && !empty($this->customerPhone) && filter_var($this->customerPhone, FILTER_VALIDATE_EMAIL)) {
                $this->customer = Customer::where('email', $this->customerPhone)
                    ->where('restaurant_id', $this->restaurant->id)
                    ->first();
                if ($this->customer) {
                    $this->customerEmail = $this->customerPhone;
                    $this->customerPhone = null;
                }
            }
            
            if ($this->customer) {
                $this->customerId = $this->customer->id;
                // Ensure email and phone are set from customer record if missing
                if (empty($this->customerEmail) && !empty($this->customer->email)) {
                    $this->customerEmail = $this->customer->email;
                }
                if (empty($this->customerPhone) && !empty($this->customer->phone)) {
                    $this->customerPhone = $this->customer->phone;
                }
                // Load loyalty points and stamps if enabled (using trait method)
                if ($this->isLoyaltyModuleAvailable() && method_exists($this, 'loadCustomerAndLoyaltyData')) {
                    $this->loadCustomerAndLoyaltyData();
                }
            }
        }
    }

    public function processPayment()
    {
        $availability = RestaurantAvailabilityService::getAvailability($this->restaurant, $this->shopBranch);
        if (!($availability['is_open'] ?? true)) {
            $this->alert('error', RestaurantAvailabilityService::getMessage($availability, $this->restaurant), [
                'toast' => false,
                'position' => 'center',
            ]);
            return;
        }

        // If online payment is selected, use processOnlinePayment instead
        if ($this->paymentMethod && str_starts_with($this->paymentMethod, 'online_')) {
            if (!$this->selectedOnlineGateway) {
                // Open modal if gateway not selected
                $this->openPaymentModal();
                return;
            }
            return $this->processOnlinePayment();
        }

        // If "Offline Payment" option is selected, translate it to the chosen offline method name
        if ($this->paymentMethod === 'offline') {
            $method = null;
            if (!empty($this->selectedOfflineMethodId)) {
                $method = OfflinePaymentMethod::where('restaurant_id', $this->restaurant->id ?? null)
                    ->where('status', 'active')
                    ->find($this->selectedOfflineMethodId);
            }

            $this->paymentMethod = $method?->name ?: 'due';
        }

        $kioskService = new KioskCartService();
        $cartItemList = $kioskService->getKioskCartSummary($this->shopBranch->id);

        if (empty($cartItemList['items'])) {
            return;
        }

        // Create or find customer
        $customer = $this->createOrFindCustomer();
        
        // Set customer ID for loyalty
        if ($customer) {
            $this->customerId = $customer->id;
            $this->customer = $customer;
        }

        $orderNumberData = Order::generateOrderNumber($this->shopBranch);

        $orderTypeModel = OrderType::where('is_default', 1)
            ->where('type', $cartItemList['order_type'])
            ->first();

        // Calculate totals with loyalty discount applied before tax (using trait method)
        $handler = $this->loyaltyHandler();
        if ($handler) {
            $totals = $handler->calculateTotalsWithLoyaltyDiscount($cartItemList);
        } else {
            $totals = $this->calculateTotalsWithoutLoyalty($cartItemList);
        }
        $orderSubTotal = $totals['subtotal'];
        $discountedSubtotal = $totals['discountedSubtotal'];
        $orderTotal = $totals['total'];
        $orderTotalTaxAmount = $totals['totalTaxAmount'];
        $serviceTotal = $totals['serviceTotal'] ?? 0;
        $taxBase = $totals['taxBase'] ?? null;
        $taxMode = $cartItemList['tax_mode'];
        
        // Order structure:
        // - sub_total: original subtotal (before discounts)
        // - loyalty_discount_amount: points discount
        // - stamp_discount_amount: stamp discount
        // - total: final total after all discounts and taxes

        $order = Order::create([
            'order_number' => $orderNumberData['order_number'],
            'formatted_order_number' => $orderNumberData['formatted_order_number'],
            'branch_id' => $this->shopBranch->id,
            'table_id' => null,
            'date_time' => now(),
            'customer_id' => $customer->id ?? null,
            'sub_total' => $orderSubTotal, // Original subtotal before discounts
            'total' => $orderTotal, // Final total after discounts (calculated in calculateTotalsWithLoyaltyDiscount)
            'order_type' => $cartItemList['order_type'],
            'order_type_id' => $orderTypeModel->id ?? null,
            'custom_order_type_name' => $orderTypeModel->order_type_name ?? 'dine_in',
            'status' => 'pending_verification',
            'order_status' => $this->restaurant->auto_confirm_orders ? 'confirmed' : 'placed',
            'placed_via' => 'kiosk',
            'tax_mode' => $taxMode,
            'total_tax_amount' => $orderTotalTaxAmount,
            'tax_base' => $taxBase,
            'pickup_date' => $cartItemList['order_type'] === 'pickup' ? now() : null,
            'kiosk_id' => $this->kioskId,
            'loyalty_points_redeemed' => $this->loyaltyPointsRedeemed ?? 0,
            'loyalty_discount_amount' => $this->loyaltyDiscountAmount ?? 0,
            'stamp_discount_amount' => $this->stampDiscountAmount ?? 0, // Set from calculated discount
        ]);

        // Attach applicable service charges to order
        $charges = $this->getApplicableKioskCharges($cartItemList['order_type'] ?? 'dine_in');
        if ($charges->isNotEmpty()) {
            $chargesData = $charges->map(fn($charge) => ['charge_id' => $charge->id])->toArray();
            $order->charges()->createMany($chargesData);
        }
        
        $transactionId = uniqid('KIOSK_TXN_', true);

        $kot = Kot::create([
            'branch_id' => $this->shopBranch->id,
            'kot_number' => (Kot::generateKotNumber($this->shopBranch) + 1),
            'order_id' => $order->id,
            'note' => null,
            'token_number' => Kot::generateTokenNumber($this->shopBranch->id, $order->order_type_id),
            'transaction_id' => $transactionId,
        ]);

        // Create order items from cart BEFORE loyalty/stamp redemption
        // (stamp discount is already applied to cart item amounts)
        foreach ($cartItemList['items'] as $item) {
            $orderItem = OrderItem::create([
                'branch_id' => $this->shopBranch->id,
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item']['id'],
                'menu_item_variation_id' => $item['variation']['id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'amount' => $item['amount'],
                'transaction_id' => $transactionId,
                'note' => null,
                'tax_amount' => $item['tax_amount'] ?? null,
                'tax_percentage' => $item['tax_percentage'] ?? null,
                'tax_breakup' => !empty($item['tax_breakup']) ? json_encode($item['tax_breakup']) : null,
            ]);

            $kotItem = KotItem::create([
                'kot_id' => $kot->id,
                'menu_item_id' => $item['menu_item']['id'],
                'menu_item_variation_id' => $item['variation']['id'] ?? null,
                'quantity' => $item['quantity'],
                'transaction_id' => $transactionId,
                'note' => null,
            ]);

            if (!empty($item['modifiers'])) {
                $modifierOptionIds = collect($item['modifiers'])->pluck('id')->all();
                $kotItem->modifierOptions()->sync($modifierOptionIds);
                $orderItem->modifierOptions()->sync($modifierOptionIds);
            }
        }

        // Create order taxes BEFORE loyalty/stamp redemption
        if ($cartItemList['tax_mode'] === 'order') {
            $taxes = Tax::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->get();
            foreach ($taxes as $tax) {
                OrderTax::firstOrCreate([
                    'order_id' => $order->id,
                    'tax_id' => $tax->id,
                ]);
            }
        }

        // Redeem loyalty points if applied (using trait method)
        // This happens AFTER order items are created so free items can be added
        if ($handler) {
            $handler->processLoyaltyRedemptionForOrder($order, $customer);
        }

        // Redeem stamps if selected (CRITICAL: after order items are created)
        // This adds free items or applies discounts, then recalculates totals
        if ($handler) {
            $handler->processStampRedemptionForOrder($order, $customer);
        }

        // Refresh order to ensure payment amount is based on the final, potentially updated, total
        $order->refresh();
        $order->load(['items', 'taxes.tax']);

        Payment::create([
            'order_id' => $order->id,
            'branch_id' => $this->shopBranch->id,
            'payment_method' => $this->paymentMethod,
            'amount' => $order->total, // Use order's total from database
            'kiosk_id' => $this->kioskId,
        ]);

        NewOrderCreated::dispatch($order);
        event(new OrderUpdated($order, 'created'));

        $kioskService->clearKioskCart($this->shopBranch->id);


        // Notify kiosk UI to show the confirmation screen with dynamic details
        return $this->redirect(route('kiosk.order-confirmation', $order->uuid), true);
    }

    private function createOrFindCustomer()
    {
        $this->customerName = session('customerInfo')['name'] ?? null;
        $this->customerEmail = session('customerInfo')['email'] ?? null;
        $this->customerPhone = session('customerInfo')['phone'] ?? null;
        $this->pickupTime = session('customerInfo')['pickup_time'] ?? '';

        // If no customer information provided, return null
        if (empty($this->customerName) && empty($this->customerEmail) && empty($this->customerPhone)) {
            return null;
        }

        // Try to find existing customer by email or phone
        // Check all possible combinations to avoid creating duplicate customers
        $customer = null;

        // First, try to find by email (if email field is not empty and looks like email)
        if (!empty($this->customerEmail) && filter_var($this->customerEmail, FILTER_VALIDATE_EMAIL)) {
            $customer = Customer::where('email', $this->customerEmail)
                ->where('restaurant_id', $this->restaurant->id)
                ->first();
        }

        // If not found by email, try by phone
        if (!$customer && !empty($this->customerPhone)) {
            $customer = Customer::where('phone', $this->customerPhone)
                ->where('restaurant_id', $this->restaurant->id)
                ->first();
        }

        // Also check if phone might be an email (common in kiosk where users enter email in phone field)
        if (!$customer && !empty($this->customerPhone) && filter_var($this->customerPhone, FILTER_VALIDATE_EMAIL)) {
            $customer = Customer::where('email', $this->customerPhone)
                ->where('restaurant_id', $this->restaurant->id)
                ->first();
        }

        // Also check if email field contains phone number (reverse check)
        if (!$customer && !empty($this->customerEmail) && !filter_var($this->customerEmail, FILTER_VALIDATE_EMAIL)) {
            // If email field contains phone number, check phone column
            $customer = Customer::where('phone', $this->customerEmail)
                ->where('restaurant_id', $this->restaurant->id)
                ->first();
        }

        // Also check if customer exists with either email OR phone matching either field
        // This handles cases where email is in phone field or vice versa
        if (!$customer) {
            $query = Customer::where('restaurant_id', $this->restaurant->id);
            
            $query->where(function($q) {
                if (!empty($this->customerEmail)) {
                    $q->where('email', $this->customerEmail)
                      ->orWhere('phone', $this->customerEmail);
                }
                if (!empty($this->customerPhone)) {
                    $q->orWhere('email', $this->customerPhone)
                      ->orWhere('phone', $this->customerPhone);
                }
            });
            
            $customer = $query->first();
        }

        // If customer found, update their information if needed
        if ($customer) {
            $updated = false;

            if (!empty($this->customerName) && $customer->name !== $this->customerName) {
                $customer->name = $this->customerName ?? null;
                $updated = true;
            }

            if (!empty($this->customerEmail) && $customer->email !== $this->customerEmail) {
                $customer->email = $this->customerEmail ?? null;
                $updated = true;
            }

            if (!empty($this->customerPhone) && $customer->phone !== $this->customerPhone) {
                $customer->phone = $this->customerPhone ?? null;
                $updated = true;
            }

            if ($updated) {
                $customer->save();
            }
        } else {
            // Create new customer
            $customer = Customer::create([
                'restaurant_id' => $this->restaurant->id,
                'name' => $this->customerName ?? null,
                'email' => strlen($this->customerEmail) > 0 ? $this->customerEmail : null,
                'phone' => strlen($this->customerPhone) > 0 ? $this->customerPhone : null,
            ]);
        }

        return $customer;
    }

    public function selectPaymentMethod(string $method): void
    {
        $this->paymentMethod = $method;
        $this->selectedOnlineGateway = null; // Reset gateway selection when changing payment method
        $this->selectedOfflineMethodId = null; // Reset offline method selection when changing payment method

        if ($method === 'upi') {
            $this->showQrCode = true;
        } else {
            $this->showQrCode = false;
        }
    }

    /**
     * Open the offline payment methods modal.
     * The actual selection of a method will set $paymentMethod to the chosen method name.
     */
    public function openOfflinePaymentModal(): void
    {
        // Keep component state consistent with Alpine state ('offline')
        $this->paymentMethod = 'offline';
        $this->showOfflineModal = true;
    }

    /**
     * Hide the offline payment methods modal.
     * If no offline method is selected and the generic 'offline' flag is set, reset to default.
     */
    public function hideOfflineModal(): void
    {
        $this->showOfflineModal = false;

        // If user closed the modal without selecting a method and current method is a generic 'offline' flag,
        // reset back to default 'due' so processing behaves like plain cash.
        if ($this->paymentMethod === 'offline' && !$this->selectedOfflineMethodId) {
            $this->paymentMethod = 'due';
        }
    }

    /**
     * Select a specific offline payment method (e.g. cash, bank_transfer, custom name).
     * This behaves like cash in terms of processing (no online gateway), but stores the chosen method name.
     */
    public function selectOfflineMethod(int $methodId): void
    {
        $method = OfflinePaymentMethod::where('restaurant_id', $this->restaurant->id ?? null)->where('status', 'active')->find($methodId);

        if (!$method) {
            return;
        }

        $this->selectedOfflineMethodId = $method->id;

        // Store the chosen offline method name (cash/bank_transfer/custom)
        $this->paymentMethod = $method->name;

        // Close modal after selection (requested behavior)
        $this->showOfflineModal = false;
    }

    public function applySelectedOfflineMethod(): void
    {
        if (!$this->selectedOfflineMethodId) {
            return;
        }

        $method = OfflinePaymentMethod::where('restaurant_id', $this->restaurant->id ?? null)
            ->where('status', 'active')
            ->find($this->selectedOfflineMethodId);

        if (!$method) {
            return;
        }

        $this->paymentMethod = $method->name;
        $this->showOfflineModal = false;
    }

    public function openPaymentModal()
    {
        // Just open the modal. Totals are always calculated in render()
        // and passed to the view as $total for consistent display.
        $this->showPaymentModal = true;
    }

    public function hidePaymentModal()
    {
        $this->showPaymentModal = false;
        // Only reset payment method if no gateway was selected
        if (!$this->selectedOnlineGateway) {
            // Reset to default if it was set to online
            if ($this->paymentMethod && str_starts_with($this->paymentMethod, 'online_')) {
                $this->paymentMethod = 'due';
            }
        }
    }

    public function selectOnlineGateway($gateway)
    {
        $this->selectedOnlineGateway = $gateway;
        $this->paymentMethod = 'online_' . $gateway;
        $this->dispatch('onlineGatewaySelected', gateway: $gateway);
    }

    public function processOnlinePayment()
    {
        $availability = RestaurantAvailabilityService::getAvailability($this->restaurant, $this->shopBranch);
        if (!($availability['is_open'] ?? true)) {
            $this->alert('error', RestaurantAvailabilityService::getMessage($availability, $this->restaurant), [
                'toast' => false,
                'position' => 'center',
            ]);
            return;
        }

        if (!$this->selectedOnlineGateway) {
            return;
        }

        $kioskService = new KioskCartService();
        $cartItemList = $kioskService->getKioskCartSummary($this->shopBranch->id);

        if (empty($cartItemList['items'])) {
            return;
        }

        // Create or find customer
        $customer = $this->createOrFindCustomer();
        
        // Set customer ID for loyalty
        if ($customer) {
            $this->customerId = $customer->id;
            $this->customer = $customer;
        }

        $orderNumberData = Order::generateOrderNumber($this->shopBranch);

        $orderTypeModel = OrderType::where('is_default', 1)
            ->where('type', $cartItemList['order_type'])
            ->first();

        // Calculate totals with loyalty discount applied before tax
        if ($this->isLoyaltyModuleAvailable() && method_exists($this, 'calculateTotalsWithLoyaltyDiscount')) {
            $totals = $this->calculateTotalsWithLoyaltyDiscount($cartItemList);
        } else {
            $totals = [
                'subtotal' => $cartItemList['sub_total'],
                'total' => $cartItemList['total'],
                'totalTaxAmount' => $cartItemList['total_tax_amount'] ?? 0,
                'taxBreakdown' => [],
                'discountedSubtotal' => $cartItemList['sub_total'],
            ];
        }
        $orderSubTotal = $totals['subtotal'];
        $discountedSubtotal = $totals['discountedSubtotal'];
        $orderTotal = $totals['total'];
        $orderTotalTaxAmount = $totals['totalTaxAmount'];
        $taxMode = $cartItemList['tax_mode'];

        $order = Order::create([
            'order_number' => $orderNumberData['order_number'],
            'formatted_order_number' => $orderNumberData['formatted_order_number'],
            'branch_id' => $this->shopBranch->id,
            'table_id' => null,
            'date_time' => now(),
            'customer_id' => $customer->id ?? null,
            'sub_total' => $orderSubTotal,
            'total' => $orderTotal,
            'order_type' => $cartItemList['order_type'],
            'order_type_id' => $orderTypeModel->id ?? null,
            'custom_order_type_name' => $orderTypeModel->order_type_name ?? 'dine_in',
            'status' => 'pending_verification',
            'order_status' => $this->restaurant->auto_confirm_orders ? 'confirmed' : 'placed',
            'placed_via' => 'kiosk',
            'tax_mode' => $taxMode,
            'total_tax_amount' => $orderTotalTaxAmount,
            'pickup_date' => $cartItemList['order_type'] === 'pickup' ? now() : null,
            'kiosk_id' => $this->kioskId,
            'loyalty_points_redeemed' => $this->loyaltyPointsRedeemed ?? 0,
            'loyalty_discount_amount' => $this->loyaltyDiscountAmount ?? 0,
            'stamp_discount_amount' => $this->stampDiscountAmount ?? 0,
        ]);
        
        $transactionId = uniqid('KIOSK_TXN_', true);

        $kot = Kot::create([
            'branch_id' => $this->shopBranch->id,
            'kot_number' => (Kot::generateKotNumber($this->shopBranch) + 1),
            'order_id' => $order->id,
            'note' => null,
            'token_number' => Kot::generateTokenNumber($this->shopBranch->id, $order->order_type_id),
            'transaction_id' => $transactionId,
        ]);

        // Create order items
        foreach ($cartItemList['items'] as $item) {
            $orderItem = OrderItem::create([
                'branch_id' => $this->shopBranch->id,
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item']['id'],
                'menu_item_variation_id' => $item['variation']['id'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'amount' => $item['amount'],
                'transaction_id' => $transactionId,
                'note' => null,
                'tax_amount' => $item['tax_amount'] ?? null,
                'tax_percentage' => $item['tax_percentage'] ?? null,
                'tax_breakup' => !empty($item['tax_breakup']) ? json_encode($item['tax_breakup']) : null,
            ]);

            $kotItem = KotItem::create([
                'kot_id' => $kot->id,
                'menu_item_id' => $item['menu_item']['id'],
                'menu_item_variation_id' => $item['variation']['id'] ?? null,
                'quantity' => $item['quantity'],
                'transaction_id' => $transactionId,
                'note' => null,
            ]);

            if (!empty($item['modifiers'])) {
                $modifierOptionIds = collect($item['modifiers'])->pluck('id')->all();
                $kotItem->modifierOptions()->sync($modifierOptionIds);
                $orderItem->modifierOptions()->sync($modifierOptionIds);
            }
        }

        // Create order taxes
        if ($cartItemList['tax_mode'] === 'order') {
            $taxes = Tax::withoutGlobalScopes()->where('restaurant_id', $this->restaurant->id)->get();
            foreach ($taxes as $tax) {
                OrderTax::firstOrCreate([
                    'order_id' => $order->id,
                    'tax_id' => $tax->id,
                ]);
            }
        }

        // Redeem loyalty points/stamps through handler after items are created.
        $handler = $this->loyaltyHandler();
        if ($handler) {
            $handler->processLoyaltyRedemptionForOrder($order, $customer);
            $handler->processStampRedemptionForOrder($order, $customer);
        }

        // Refresh order to ensure payment amount is based on the final total
        $order->refresh();
        $order->load(['items', 'taxes.tax']);

        // Dispatch order created events
        NewOrderCreated::dispatch($order);
        event(new OrderUpdated($order, 'created'));

        $kioskService->clearKioskCart($this->shopBranch->id);

        // Initiate payment gateway based on selection
        $this->initiatePaymentGateway($order, $this->selectedOnlineGateway);
    }

    private function initiatePaymentGateway($order, $gateway)
    {
        switch ($gateway) {
            case 'razorpay':
                $this->initiateRazorpayPayment($order);
                break;
            case 'stripe':
                $this->initiateStripePayment($order);
                break;
            case 'flutterwave':
                $this->initiateFlutterwavePayment($order);
                break;
            case 'paypal':
                $this->initiatePaypalPayment($order);
                break;
            case 'payfast':
                $this->initiatePayfastPayment($order);
                break;
            case 'paystack':
                $this->initiatePaystackPayment($order);
                break;
            case 'xendit':
                $this->initiateXenditPayment($order);
                break;
            case 'epay':
                $this->initiateEpayPayment($order);
                break;
            case 'mollie':
                $this->initiateMolliePayment($order);
                break;
            case 'tap':
                $this->initiateTapPayment($order);
                break;
            default:
                // Fallback to order confirmation
                return $this->redirect(route('kiosk.order-confirmation', $order->uuid), true);
        }
    }

    private function initiateRazorpayPayment($order)
    {
        $payment = RazorpayPayment::create([
            'order_id' => $order->id,
            'amount' => $order->total
        ]);

        $orderData = [
            'amount' => (int) round($order->total * 100),
            'currency' => $this->restaurant->currency->currency_code
        ];

        $apiKey = $this->paymentGateway->razorpay_key;
        $secretKey = $this->paymentGateway->razorpay_secret;

        $api = new Api($apiKey, $secretKey);
        $razorpayOrder = $api->order->create($orderData);
        $payment->razorpay_order_id = $razorpayOrder->id;
        $payment->save();

        $this->dispatch('kioskPaymentInitiated', payment: $payment, order: $order);
    }

    private function initiateStripePayment($order)
    {
        $payment = StripePayment::create([
            'order_id' => $order->id,
            'amount' => $order->total
        ]);

        $this->dispatch('kioskStripePaymentInitiated', payment: $payment, order: $order);
    }

    private function initiateFlutterwavePayment($order)
    {
        try {
            $apiSecret = $this->paymentGateway->flutterwave_secret;
            $amount = $order->total;
            $tx_ref = "txn_" . time();

            $user = $this->customer ?? $this->restaurant;

            $data = [
                "tx_ref" => $tx_ref,
                "amount" => $amount,
                "currency" => $this->restaurant->currency->currency_code,
                "redirect_url" => route('flutterwave.success'),
                "payment_options" => "card",
                "customer" => [
                    "email" => $user->email ?? 'no-email@example.com',
                    "name" => $user->name ?? 'Guest',
                    "phone_number" => $user->phone ?? '0000000000',
                ],
            ];
            $response = Http::withHeaders([
                "Authorization" => "Bearer $apiSecret",
                "Content-Type" => "application/json"
            ])->post("https://api.flutterwave.com/v3/payments", $data);

            $responseData = $response->json();

            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                FlutterwavePayment::create([
                    'order_id' => $order->id,
                    'flutterwave_payment_id' => $tx_ref,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['data']['link']);
            } else {
                return redirect()->route('flutterwave.failed')->withErrors(['error' => 'Payment initiation failed', 'message' => $responseData]);
            }
        } catch (\Exception $e) {
            Log::error('Flutterwave payment error: ' . $e->getMessage());
            return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Payment initiation failed');
        }
    }

    private function initiatePaypalPayment($order)
    {
        $amount = $order->total;
        $currency = strtoupper($this->restaurant->currency->currency_code);

        $unsupportedCurrencies = ['INR'];
        if (in_array($currency, $unsupportedCurrencies)) {
            return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Currency not supported by PayPal.');
        }

        $clientId = $this->paymentGateway->paypal_payment_client_id;
        $secret = $this->paymentGateway->paypal_payment_secret;

        $paypalPayment = new PaypalPayment();
        $paypalPayment->order_id = $order->id;
        $paypalPayment->amount = $amount;
        $paypalPayment->payment_status = 'pending';
        $paypalPayment->save();

        $returnUrl = route('paypal.success');
        $cancelUrl = route('paypal.cancel');

        $paypalData = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => $currency,
                    "value" => number_format($amount, 2, '.', '')
                ],
                "reference_id" => (string)$paypalPayment->id
            ]],
            "application_context" => [
                "return_url" => $returnUrl,
                "cancel_url" => $cancelUrl
            ]
        ];

        $auth = base64_encode("$clientId:$secret");

        $response = Http::withHeaders([
            'Authorization' => "Basic $auth",
            'Content-Type' => 'application/json'
        ])->post('https://api-m.sandbox.paypal.com/v2/checkout/orders', $paypalData);

        if ($response->successful()) {
            $paypalResponse = $response->json();
            $paypalPayment->paypal_payment_id = $paypalResponse['id'];
            $paypalPayment->payment_status = 'pending';
            $paypalPayment->save();

            $approvalLink = collect($paypalResponse['links'])->firstWhere('rel', 'approve')['href'];
            return redirect($approvalLink);
        }
        
        $paypalPayment->payment_status = 'failed';
        $paypalPayment->save();
        return redirect()->route('paypal.cancel');
    }

    private function initiatePayfastPayment($order)
    {
        $isSandbox = $this->paymentGateway->payfast_mode === 'sandbox';
        $merchantId = $isSandbox ? $this->paymentGateway->test_payfast_merchant_id : $this->paymentGateway->payfast_merchant_id;
        $merchantKey = $isSandbox ? $this->paymentGateway->test_payfast_merchant_key : $this->paymentGateway->payfast_merchant_key;
        $passphrase = $isSandbox ? $this->paymentGateway->test_payfast_passphrase : $this->paymentGateway->payfast_passphrase;
        $amount = number_format($order->total, 2, '.', '');
        $itemName = "Order Payment #{$order->id}";
        $reference = 'pf_' . time();
        
        $data = [
            'merchant_id' => $merchantId,
            'merchant_key' => $merchantKey,
            'return_url' => route('payfast.success', ['reference' => $reference]),
            'cancel_url' => route('payfast.failed', ['reference' => $reference]),
            'notify_url' => route('payfast.notify', ['company' => $this->restaurant->hash, 'reference' => $reference]),
            'name_first' => $this->customer->name ?? 'Guest',
            'email_address' => $this->customer->email ?? 'guest@example.com',
            'm_payment_id' => $order->id,
            'amount' => $amount,
            'item_name' => $itemName,
        ];

        $signature = $this->generateSignature($data, $passphrase);
        $data['signature'] = $signature;

        AdminPayfastPayment::create([
            'order_id' => $order->id,
            'payfast_payment_id' => $reference,
            'amount' => $amount,
            'payment_status' => 'pending',
        ]);

        $payfastBaseUrl = $isSandbox ? 'https://sandbox.payfast.co.za/eng/process' : 'https://api.payfast.co.za/eng/process';
        $redirectUrl = $payfastBaseUrl . '?' . http_build_query($data);
        return redirect($redirectUrl);
    }

    private function generateSignature($data, $passPhrase)
    {
        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        $getString = substr($pfOutput, 0, -1);
        if ($passPhrase !== null) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }
        return md5($getString);
    }

    private function initiatePaystackPayment($order)
    {
        try {
            $secretKey = $this->paymentGateway->paystack_secret_data;
            $amount = $order->total;
            $reference = "psk_" . time();
            
            $data = [
                "reference" => $reference,
                "amount" => (int)($amount * 100),
                "email" => $this->customer->email ?? 'guest@example.com',
                "currency" => $this->restaurant->currency->currency_code,
                "callback_url" => route('paystack.success'),
                "metadata" => [
                    "cancel_action" => route('paystack.failed', ['reference' => $reference])
                ]
            ];

            $response = Http::withHeaders([
                "Authorization" => "Bearer $secretKey",
                "Content-Type" => "application/json"
            ])->post("https://api.paystack.co/transaction/initialize", $data);

            $responseData = $response->json();
            if (isset($responseData['status']) && $responseData['status'] === true) {
                AdminPaystackPayment::create([
                    'order_id' => $order->id,
                    'paystack_payment_id' => $reference,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['data']['authorization_url']);
            } else {
                return redirect()->route('paystack.failed');
            }
        } catch (\Exception $e) {
            Log::error('Paystack payment error: ' . $e->getMessage());
            return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Payment initiation failed');
        }
    }

    private function initiateXenditPayment($order)
    {
        try {
            $secretKey = $this->paymentGateway->xendit_secret_key;
            $amount = $order->total;
            $externalId = 'xendit_' . time();

            $user = $this->customer ?? auth()->user();

            $data = [
                'external_id' => $externalId,
                'amount' => $amount,
                'description' => 'Order Payment #' . $order->id,
                'currency' => $this->restaurant->currency->currency_code,
                'success_redirect_url' => route('xendit.success', ['external' => $externalId]),
                'failure_redirect_url' => route('xendit.failed', ['external' => $externalId]),
                'payment_methods' => ['CREDIT_CARD', 'BCA', 'BNI', 'BSI', 'BRI', 'MANDIRI', 'OVO', 'DANA', 'LINKAJA', 'SHOPEEPAY'],
                'should_send_email' => true,
                'customer' => [
                    'given_names' => $user->name ?? 'Guest',
                    'email' => $user->email ?? 'guest@example.com',
                    'mobile_number' => $user->phone ?? '+6281234567890',
                ],
                'items' => [
                    [
                        'name' => 'Order #' . $order->id,
                        'quantity' => 1,
                        'price' => $amount,
                        'category' => 'FOOD_AND_BEVERAGE'
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json'
            ])->post('https://api.xendit.co/v2/invoices', $data);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                XenditPayment::create([
                    'order_id' => $order->id,
                    'xendit_payment_id' => $externalId,
                    'xendit_invoice_id' => $responseData['id'],
                    'xendit_external_id' => $externalId,
                    'amount' => $amount,
                    'payment_status' => 'pending',
                ]);

                return redirect($responseData['invoice_url']);
            } else {
                return redirect()->route('xendit.failed')->with('error', 'Xendit payment initiation failed');
            }
        } catch (\Exception $e) {
            Log::error('Xendit payment error: ' . $e->getMessage());
            return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Payment initiation failed');
        }
    }

    private function initiateEpayPayment($order)
    {
        try {
            $amount = $order->total;
            $isSandbox = $this->paymentGateway->epay_mode === 'sandbox';

            $clientId = $isSandbox ? $this->paymentGateway->test_epay_client_id : $this->paymentGateway->epay_client_id;
            $clientSecret = $isSandbox ? $this->paymentGateway->test_epay_client_secret : $this->paymentGateway->epay_client_secret;
            $terminalId = $isSandbox ? $this->paymentGateway->test_epay_terminal_id : $this->paymentGateway->epay_terminal_id;

            if (!$clientId || !$clientSecret || !$terminalId) {
                return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Epay credentials are not configured.');
            }

            $secretHash = bin2hex(random_bytes(16));

            $epayPayment = EpayPayment::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'payment_status' => 'pending',
                'epay_secret_hash' => $secretHash,
            ]);

            $paymentIdStr = (string)$epayPayment->id;
            $timestampSuffix = substr((string)time(), -4);
            $invoiceIdBase = $paymentIdStr . $timestampSuffix;

            if (strlen($invoiceIdBase) < 6) {
                $invoiceId = str_pad($invoiceIdBase, 6, '0', STR_PAD_LEFT);
            } elseif (strlen($invoiceIdBase) > 15) {
                $invoiceId = substr($invoiceIdBase, -15);
            } else {
                $invoiceId = $invoiceIdBase;
            }

            $epayPayment->epay_invoice_id = $invoiceId;
            $epayPayment->save();

            $tokenResponse = $this->getEpayAccessToken($isSandbox, $invoiceId, $secretHash, $amount);
            if (!$tokenResponse || !isset($tokenResponse['access_token'])) {
                $epayPayment->payment_status = 'failed';
                $epayPayment->save();
                return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Failed to authenticate with Epay.');
            }

            $epayPayment->epay_access_token = json_encode($tokenResponse);
            $epayPayment->save();

            session([
                'epay_invoice_id' => $invoiceId,
                'epay_order_id' => $order->id,
                'epay_payment_id' => $epayPayment->id,
            ]);

            $epayPayment->load(['order.customer']);

            $this->dispatch('kioskEpayPaymentInitiated', payment: $epayPayment, order: $order);
        } catch (\Exception $e) {
            Log::error('Epay Payment Initiation Error: ' . $e->getMessage());
            return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Payment initiation failed');
        }
    }

    private function getEpayAccessToken($isSandbox, $invoiceId, $secretHash, $amount)
    {
        $clientId = $isSandbox ? $this->paymentGateway->test_epay_client_id : $this->paymentGateway->epay_client_id;
        $clientSecret = $isSandbox ? $this->paymentGateway->test_epay_client_secret : $this->paymentGateway->epay_client_secret;
        $terminalId = $isSandbox ? $this->paymentGateway->test_epay_terminal_id : $this->paymentGateway->epay_terminal_id;

        $tokenUrl = $isSandbox
            ? 'https://test-epay-oauth.epayment.kz/oauth2/token'
            : 'https://epay-oauth.homebank.kz/oauth2/token';

        $currency = strtoupper($this->restaurant->currency->currency_code);
        $postLink = route('epay.webhook', ['hash' => $this->restaurant->hash]);
        $failurePostLink = route('epay.webhook', ['hash' => $this->restaurant->hash]);

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'scope' => 'payment',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'invoiceID' => $invoiceId,
            'secret_hash' => $secretHash,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'terminal' => $terminalId,
            'postLink' => $postLink,
            'failurePostLink' => $failurePostLink,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Epay Token Error: ' . json_encode($response->json()));
        return null;
    }

    private function initiateMolliePayment($order)
    {
        try {
            $isSandbox = $this->paymentGateway->mollie_mode === 'test';
            $apiKey = $isSandbox ? $this->paymentGateway->test_mollie_key : $this->paymentGateway->live_mollie_key;
            $amount = $order->total;
            $currency = $this->restaurant->currency->currency_code;
            
            $mollie = new MollieApiClient();
            $mollie->setApiKey($apiKey);

            $amountValue = number_format($amount, 2, '.', '');
            
            $payment = $mollie->payments->create([
                "amount" => [
                    "currency" => $currency,
                    "value" => $amountValue,
                ],
                "description" => "Order Payment #" . $order->id,
                "redirectUrl" => route('mollie.success', ['order_id' => $order->id]),
                "webhookUrl" => route('mollie.webhook', ['hash' => $this->restaurant->hash]),
            ]);

            AdminMolliePayment::create([
                'order_id' => $order->id,
                'mollie_payment_id' => $payment->id,
                'amount' => $amount,
                'payment_status' => 'pending',
            ]);

            return redirect($payment->getCheckoutUrl());
        } catch (\Exception $e) {
            Log::error('Mollie payment error: ' . $e->getMessage());
            return redirect()->route('kiosk.order-confirmation', $order->uuid)->with('error', 'Payment initiation failed');
        }
    }

    private function initiateTapPayment($order)
    {
        try {
            $amount = $order->total;
            $isSandbox = $this->paymentGateway->tap_mode === 'sandbox';

            $secretKey = $isSandbox ? $this->paymentGateway->test_tap_secret_key : $this->paymentGateway->live_tap_secret_key;
            $publicKey = $isSandbox ? $this->paymentGateway->test_tap_public_key : $this->paymentGateway->live_tap_public_key;
            $merchantId = $this->paymentGateway->tap_merchant_id;

            if (!$secretKey || !$publicKey || !$merchantId) {
                session()->flash('flash.banner', 'Tap credentials are not configured.');
                session()->flash('flash.bannerStyle', 'warning');
                return redirect()->route('kiosk.order-confirmation', $order->uuid);
            }

            $currency = strtoupper($this->restaurant->currency->currency_code);
            $customer = $this->customer ?? $order->customer;

            // Create payment record first
            $tapPayment = TapPayment::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'payment_status' => 'pending',
            ]);

            // Prepare charge data for Tap Charge API
            $chargeData = [
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
                'threeDSecure' => true,
                'save_card' => false,
                'description' => 'Order Payment #' . $order->id,
                'statement_descriptor' => 'Order #' . $order->id,
                'metadata' => [
                    'udf1' => 'Order ID: ' . $order->id,
                    'udf2' => 'Restaurant: ' . $this->restaurant->name,
                ],
                'reference' => [
                    'transaction' => 'txn_' . $order->id,
                    'order' => 'ord_' . $order->id,
                ],
                'receipt' => [
                    'email' => false,
                    'sms' => false,
                ],
                'customer' => [
                    'first_name' => $customer->name ?? 'Guest',
                    'email' => $customer->email ?? 'guest@example.com',
                    'phone' => [
                        'country_code' => $customer->phone_code ?? '966',
                        'number' => $customer->phone ?? '000000000',
                    ],
                ],
                'merchant' => [
                    'id' => $merchantId,
                ],
                'source' => [
                    'id' => 'src_all',
                ],
                'redirect' => [
                    'url' => route('tap.success', ['order_id' => $order->id]),
                ],
                'post' => [
                    'url' => route('tap.webhook', ['hash' => $this->restaurant->hash]),
                ],
            ];

            // Make API call to Tap Charge API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.tap.company/v2/charges', $chargeData);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['id'])) {
                // Update payment record with charge ID
                $tapPayment->tap_payment_id = $responseData['id'];
                $tapPayment->save();

                // Store order ID in session for fallback
                session(['tap_order_id' => $order->id]);

                $checkoutUrl = $responseData['transaction']['url'] ?? null;

                if ($checkoutUrl) {
                    return redirect()->away($checkoutUrl);
                } else {
                    if (isset($responseData['status']) && $responseData['status'] === 'CAPTURED') {
                        return redirect()->route('tap.success', ['order_id' => $order->id, 'tap_id' => $responseData['id']]);
                    } else {
                        session()->flash('flash.banner', 'Payment initiation failed. Please try again.');
                        session()->flash('flash.bannerStyle', 'danger');
                        return redirect()->route('kiosk.order-confirmation', $order->uuid);
                    }
                }
            } else {
                // Payment initiation failed
                $tapPayment->payment_status = 'failed';
                $tapPayment->payment_error_response = $responseData;
                $tapPayment->save();

                $errorMessage = $responseData['errors'][0]['message'] ?? 'Payment initiation failed. Please try again.';
                session()->flash('flash.banner', $errorMessage);
                session()->flash('flash.bannerStyle', 'danger');
                return redirect()->route('kiosk.order-confirmation', $order->uuid);
            }
        } catch (\Exception $e) {
            Log::error('Tap Payment Initiation Error: ' . $e->getMessage());
            session()->flash('flash.banner', 'Payment initiation failed: ' . $e->getMessage());
            session()->flash('flash.bannerStyle', 'danger');
            return redirect()->route('kiosk.order-confirmation', $order->uuid);
        }
    }

    public function redeemLoyaltyPoints(): void
    {
        $handler = $this->loyaltyHandler();
        if ($handler) {
            $handler->redeemLoyaltyPoints();
        }
    }

    public function removeLoyaltyRedemption(): void
    {
        $handler = $this->loyaltyHandler();
        if ($handler) {
            $handler->removeLoyaltyRedemption();
        }
    }

    public function redeemStamps($stampRuleId = null): void
    {
        $handler = $this->loyaltyHandler();
        if ($handler) {
            $handler->redeemStamps($stampRuleId);
        }
    }

    public function removeStampRedemption($stampRuleId = null): void
    {
        $handler = $this->loyaltyHandler();
        if ($handler) {
            $handler->removeStampRedemption($stampRuleId);
        }
    }

    public function render()
    {
        $kioskService = new KioskCartService();
        $cartItemList = $kioskService->getKioskCartSummary($this->shopBranch->id);
        $availability = RestaurantAvailabilityService::getAvailability($this->restaurant, $this->shopBranch);

        $subtotal = $cartItemList['sub_total'];
        $taxMode = $cartItemList['tax_mode'];
        $cartCount = $cartItemList['count'];
        
        // Ensure customer and loyalty data are loaded
        if (!$this->customerId && ($this->customerName || $this->customerEmail || $this->customerPhone)) {
            $this->loadCustomerAndLoyalty();
        }
        
        // Also try to load customer from session if not already loaded
        if (!$this->customerId) {
            $customerInfo = session('customerInfo', []);
            $email = $customerInfo['email'] ?? null;
            $phone = $customerInfo['phone'] ?? null;
            
            // Fix: If email is empty but phone looks like an email, use phone as email
            if (empty($email) && !empty($phone) && filter_var($phone, FILTER_VALIDATE_EMAIL)) {
                $email = $phone;
                $phone = null;
            }
            
            if (!empty($email) || !empty($phone)) {
                // First try by email
                if (!empty($email)) {
                    $this->customer = Customer::where('email', $email)
                        ->where('restaurant_id', $this->restaurant->id)
                        ->first();
                }
                
                // If not found by email, try by phone
                if (!$this->customer && !empty($phone)) {
                    $this->customer = Customer::where('phone', $phone)
                        ->where('restaurant_id', $this->restaurant->id)
                        ->first();
                }
                
                // If still not found and phone looks like email, try phone as email
                if (!$this->customer && !empty($phone) && filter_var($phone, FILTER_VALIDATE_EMAIL)) {
                    $this->customer = Customer::where('email', $phone)
                        ->where('restaurant_id', $this->restaurant->id)
                        ->first();
                }
                
                if ($this->customer) {
                    $this->customerId = $this->customer->id;
                    // Update component properties
                    if (empty($this->customerEmail) && !empty($this->customer->email)) {
                        $this->customerEmail = $this->customer->email;
                    }
                    if (empty($this->customerPhone) && !empty($this->customer->phone)) {
                        $this->customerPhone = $this->customer->phone;
                    }
                }
            }
        }
        
        // Load loyalty points and stamps if enabled and customer exists (using trait method)
        $handler = $this->loyaltyHandler();
        if ($handler && $handler->isLoyaltyEnabled() && $this->customerId) {
            if ($handler->isPointsEnabledForKiosk()) {
                if ($this->loyaltyPointsRedeemed == 0 && $this->loyaltyDiscountAmount == 0) {
                    $handler->loadLoyaltyPoints();
                }
            }

            if ($handler->isStampsEnabledForKiosk()) {
                if (empty($this->customerStamps)) {
                    $handler->loadCustomerStamps();
                }
            }
        }
        
        // Calculate totals with loyalty discount (using trait method)
        if ($handler) {
            $totals = $handler->calculateTotalsWithLoyaltyDiscount($cartItemList);
        } else {
            $totals = $this->calculateTotalsWithoutLoyalty($cartItemList);
        }
        $subtotal = $totals['subtotal']; // Original subtotal before discounts
        $discountedSubtotal = $totals['discountedSubtotal'] ?? $cartItemList['sub_total']; // Subtotal after stamp discount
        $total = $totals['total'];
        $totalTaxAmount = $totals['totalTaxAmount'];
        $taxBreakdown = $totals['taxBreakdown'];
        $serviceTotal = $totals['serviceTotal'] ?? 0;
        $chargeBreakdown = $totals['chargeBreakdown'] ?? [];

        return view('kiosk::livewire.kiosk.payment-method', [
            'cartItemList' => $cartItemList,
            'subtotal' => $subtotal,
            'discountedSubtotal' => $discountedSubtotal,
            'total' => $total,
            'totalTaxAmount' => $totalTaxAmount,
            'taxBreakdown' => $taxBreakdown,
            'serviceTotal' => $serviceTotal,
            'chargeBreakdown' => $chargeBreakdown,
            'taxMode' => $taxMode,
            'cartCount' => $cartCount,
            'isRestaurantOpenForOrders' => (bool) ($availability['is_open'] ?? true),
            'restaurantClosedMessage' => RestaurantAvailabilityService::getMessage($availability, $this->restaurant),
        ]);
    }
}
