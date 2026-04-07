<div x-data="kioskApp()" class="min-h-screen">

    @php($currentKioskId = session('kiosk_id'))

    @if (empty($currentKioskId))
        @php($kiosks = \Modules\Kiosk\Entities\Kiosk::where('branch_id', $shopBranch->id)->where('is_active', true)->get())
        <div class="min-h-screen flex items-center justify-center p-6">
            <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">

                @if ($kiosks->count() == 0)
                <div class="text-center py-8 space-y-6">
                    <!-- Empty State Icon -->
                    <div class="flex justify-center">
                        <div class="w-20 h-20 rounded-full bg-skin-base/[.2] dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                            <svg class="w-10 h-10 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Empty State Text -->
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ trans('kiosk::modules.settings.no_kiosks') }}
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 max-w-sm mx-auto">
                            {{ trans('kiosk::modules.settings.no_kiosks_subheading') }}
                        </p>
                    </div>
                    
                    <!-- Call to Action Button -->
                    <div class="flex justify-center pt-2">
                        <x-primary-link href="{{ route('settings.index').'?tab=kioskSettings' }}" 
                           class="inline-flex items-center gap-2 px-6 py-3 text-white font-medium rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            {{ trans('kiosk::modules.settings.add_kiosk_button') }}
                        </x-primary-link>
                    </div>
                </div>
                @else
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ trans('kiosk::modules.settings.selector_heading') }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('kiosk::modules.settings.selector_subheading') }}</p>
                <div>
                    <label for="kioskSelect" class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">{{ trans('kiosk::modules.settings.selector_label') }}</label>
                    <select id="kioskSelect" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">{{ trans('kiosk::modules.settings.selector_placeholder') }}</option>
                        @foreach ($kiosks as $k)
                            <option value="{{ $k->code }}">{{ $k->name }} ({{ $k->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 rounded bg-skin-base text-white text-sm" onclick="(function(){
                        var sel = document.getElementById('kioskSelect');
                        if(!sel.value){ return; }
                        var params = new URLSearchParams(window.location.search);
                        params.set('kiosk', sel.value);
                        var next = window.location.pathname + '?' + params.toString();
                        window.location.href = next;
                    })()">{{ trans('kiosk::modules.settings.selector_continue') }}</button>
                </div>
                @endif

            </div>
        </div>
    @endif

    
    @livewire('kiosk::kiosk.welcome', ['restaurant' => $restaurant, 'shopBranch' => $shopBranch])

    @livewire('kiosk::kiosk.order-type', ['restaurant' => $restaurant, 'shopBranch' => $shopBranch])

    @livewire('kiosk::kiosk.menu', ['restaurant' => $restaurant, 'shopBranch' => $shopBranch])

    @livewire('kiosk::kiosk.item-customisation', ['restaurant' => $restaurant, 'shopBranch' => $shopBranch])

    @livewire('kiosk::kiosk.cart-summary', ['restaurant' => $restaurant, 'shopBranch' => $shopBranch, 'kioskId' => session('kiosk_id')])

    @livewire('kiosk::kiosk.payment-method', ['restaurant' => $restaurant, 'shopBranch' => $shopBranch, 'kioskId' => session('kiosk_id')])


    @push('scripts')
    <script>
        function kioskApp() {
            return {
                // 🟡 1. Welcome Screen
                currentScreen: 'welcome',
                
                // 🟡 2. Order Type Selection
                orderType: null,
                tableNumber: null,
                
                // 🟡 3. Menu Browsing
                searchQuery: '',
                showCart: false,
                selectedCategory: 'burgers',
                
                // 🟡 4. Item Selection & Customization
                cart: [],
                selectedItem: null,
                selectedVariant: null,
                itemQuantity: 1,
                
                // 🟡 5. Cart Summary
                customerInfo: {
                    name: '',
                    email: '',
                    phone: '',
                    pickupTime: '30'
                },
                
                // 🟡 6. Payment Method Selection
                paymentMethod: null,
                
                // 🟡 7. Order Confirmation
                orderNumber: null,


                // 🟡 1. Welcome Screen Methods
                startOrder() {
                    this.currentScreen = 'order-type';
                },

                // 🟡 2. Order Type Selection Methods
                selectOrderType(type) {
                    this.orderType = type;
                    // if (type === 'dine-in') {
                    //     this.currentScreen = 'table-entry';
                    // } else {
                    //     this.currentScreen = 'menu';
                    // }
                    this.currentScreen = 'menu';
                },

                // 🟡 Table Entry Methods
                scanQR() {
                    // Simulate QR scanning
                    this.tableNumber = Math.floor(Math.random() * 20) + 1;
                },

                confirmTable() {
                    if (this.tableNumber) {
                        this.currentScreen = 'menu';
                    }
                },

                // 🟡 3. Menu Browsing Methods
                get filteredItems() {
                    let items = this.menuItems.filter(item => item.category === this.selectedCategory);
                    if (this.searchQuery) {
                        items = items.filter(item => 
                            item.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                            item.description.toLowerCase().includes(this.searchQuery.toLowerCase())
                        );
                    }
                    return items;
                },

                // 🟡 4. Item Selection & Customization Methods
                selectItem() {
                    // this.selectedItem = { ...item };
                    // this.selectedVariant = item.variants ? item.variants[0].id : null;
                    // this.itemQuantity = 1;
                    this.currentScreen = 'item-customization';
                },

                selectVariant(variant) {
                    this.selectedVariant = variant.id;
                },

                increaseQuantity() {
                    this.itemQuantity++;
                },

                decreaseQuantity() {
                    if (this.itemQuantity > 1) {
                        this.itemQuantity--;
                    }
                },

                get totalItemPrice() {
                    if (!this.selectedItem) return 0;
                    
                    let totalPrice = this.selectedItem.price;
                    
                    // Add variant price
                    if (this.selectedItem.variants && this.selectedVariant) {
                        const variant = this.selectedItem.variants.find(v => v.id === this.selectedVariant);
                        if (variant) totalPrice += variant.price;
                    }

                    // Add addon prices
                    if (this.selectedItem.addons) {
                        this.selectedItem.addons.forEach(addon => {
                            if (addon.selected) {
                                totalPrice += addon.price;
                            }
                        });
                    }

                    return (totalPrice * this.itemQuantity).toFixed(2);
                },

                addToCartFromCustomization() {
                    if (!this.selectedItem) return;

                    // Calculate total price with variants and addons
                    let totalPrice = this.selectedItem.price;
                    
                    // Add variant price
                    if (this.selectedItem.variants && this.selectedVariant) {
                        const variant = this.selectedItem.variants.find(v => v.id === this.selectedVariant);
                        if (variant) totalPrice += variant.price;
                    }

                    // Add addon prices
                    let addonNames = [];
                    if (this.selectedItem.addons) {
                        this.selectedItem.addons.forEach(addon => {
                            if (addon.selected) {
                                totalPrice += addon.price;
                                addonNames.push(addon.name);
                            }
                        });
                    }

                    // Create cart item
                    const cartItem = {
                        id: this.selectedItem.id,
                        name: this.selectedItem.name,
                        price: totalPrice,
                        quantity: this.itemQuantity,
                        image: this.selectedItem.image,
                        variant: this.selectedItem.variants ? this.selectedItem.variants.find(v => v.id === this.selectedVariant)?.name : null,
                        addons: addonNames,
                        removals: this.selectedItem.removals ? this.selectedItem.removals.filter(r => r.selected).map(r => r.name) : []
                    };

                    // Check if item already exists in cart
                    const existingIndex = this.cart.findIndex(cartItem => cartItem.id === this.selectedItem.id);
                    if (existingIndex >= 0) {
                        this.cart[existingIndex].quantity += this.itemQuantity;
                    } else {
                        this.cart.push(cartItem);
                    }

                    // Reset and go back to menu
                    this.selectedItem = null;
                    this.selectedVariant = null;
                    this.itemQuantity = 1;
                    this.currentScreen = 'menu';
                    this.showCart = true;
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                updateQuantity(index, change) {
                    const item = this.cart[index];
                    const newQuantity = item.quantity + change;
                    if (newQuantity > 0) {
                        item.quantity = newQuantity;
                    } else {
                        this.removeFromCart(index);
                    }
                },

                // 🟡 5. Cart Summary Methods
                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);
                },

                get tax() {
                    return (this.subtotal * 0.08).toFixed(2);
                },

                get total() {
                    return (parseFloat(this.subtotal) + parseFloat(this.tax)).toFixed(2);
                },

                get isCustomerInfoValid() {
                    return this.customerInfo.name && 
                           this.customerInfo.email && 
                           this.customerInfo.phone;
                },

                saveCustomerInfo() {
                    this.currentScreen = 'payment';
                },

                // 🟡 6. Payment Method Selection Methods
                selectPaymentMethod(method) {
                    this.paymentMethod = method;
                },

                processPayment() {
                    // Livewire PaymentMethod component now creates the order and emits 'kiosk-order-confirmed'.
                },

                // 🟡 7. Order Confirmation Methods
                startNewOrder() {
                    // Reset all state
                    this.currentScreen = 'welcome';
                    this.orderType = null;
                    this.tableNumber = null;
                    this.searchQuery = '';
                    this.cart = [];
                    this.selectedItem = null;
                    this.selectedVariant = null;
                    this.itemQuantity = 1;
                    this.customerInfo = {
                        name: '',
                        email: '',
                        phone: '',
                        pickupTime: '30'
                    };
                    this.paymentMethod = null;
                    this.orderNumber = null;
                    
                    // Reset menu items
                    this.menuItems.forEach(item => {
                        if (item.addons) {
                            item.addons.forEach(addon => addon.selected = false);
                        }
                        if (item.removals) {
                            item.removals.forEach(removal => removal.selected = false);
                        }
                    });
                },

                proceedToCheckout() {
                    this.currentScreen = 'customer-info';
                }
            }
        }
    </script>
    @endpush

</div>