<?php

namespace Modules\Whatsapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Kot;
use App\Models\Role;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppPhoneResolver;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Modules\Whatsapp\Services\WhatsAppHelperService;

class SendKotNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $kotId;

    /**
     * Create a new job instance.
     */
    public function __construct($kotId)
    {
        $this->kotId = $kotId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppNotificationService $notificationService, WhatsAppHelperService $helperService)
    {
        try {

            $kot = Kot::query()->with([
                'order' => function ($q) {
                    $q->with(['branch.restaurant', 'orderType', 'table']);
                },
            ])->find($this->kotId);

            if (!$kot) {
                return;
            }

            $order = $kot->order;

            if (!$order) {
                return;
            }

            // Retry mechanism: Check if more items are being added
            // If we find new items, wait a bit more and check again
            $maxRetries = 3; // Increased from 2 to 3
            $retryDelay = 3; // Increased from 2 to 3 seconds
            $previousItemCount = 0;
            $previousKotCount = 0;
            $currentKotIds = [];

            for ($retry = 0; $retry < $maxRetries; $retry++) {
                // Query DIRECTLY from database tables (not through relationships) to get accurate counts
                $currentKotIds = \App\Models\Kot::where('order_id', $order->id)
                    ->where('branch_id', $order->branch_id)
                    ->pluck('id')
                    ->all();
                $currentKotCount = count($currentKotIds);
                $currentItemCount = $currentKotCount === 0
                    ? 0
                    : \App\Models\KotItem::whereIn('kot_id', $currentKotIds)->count();

                // If KOT count or item count  increased, wait a bit more
                if (($currentKotCount > $previousKotCount || $currentItemCount > $previousItemCount) && $retry < $maxRetries - 1) {
                    sleep($retryDelay);
                    $previousItemCount = $currentItemCount;
                    $previousKotCount = $currentKotCount;
                } else {
                    // No new items or max retries reached, proceed
                    break;
                }
            }

            $order->loadMissing(['table', 'orderType', 'branch.restaurant']);
            $kot->loadMissing([
                'items.menuItem',
                'items.modifierOptions' => function ($query) {
                    $query->select('modifier_options.id', 'modifier_options.name', 'modifier_options.modifier_group_id');
                },
            ]);

            // Check if already sent
            $sentKey = 'kot_notification_sent_' . $kot->id;

            if (cache()->has($sentKey)) {
                return;
            }

            // Mark as sent immediately
            cache()->put($sentKey, true, 3600);

            $order = $kot->order;
            if (!$order) {
                return;
            }

            $restaurantId = $order->branch->restaurant_id ?? null;
            if (!$restaurantId) {
                return;
            }

            // Check if WhatsApp module is in restaurant's package
            if (function_exists('restaurant_modules')) {
                $restaurant = $order->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        return;
                    }
                }
            }

            $preference = WhatsAppNotificationPreference::firstEnabledKitchenStaff($restaurantId);

            if (!$preference) {
                return;
            }

            // Get Chef role for this restaurant
            $chefRole = Role::where('restaurant_id', $restaurantId)
                ->where('display_name', 'Chef')
                ->first();

            $kitchenStaff = collect();

            if ($chefRole) {
                // Get all chefs with mobile numbers
                $kitchenStaff = $helperService->getUsersByRoles($restaurantId, [$chefRole->id]);
            }

            // If no chefs found, try to get staff/waiter roles as fallback
            if ($kitchenStaff->isEmpty()) {

                // Try to find Staff or Waiter roles
                $staffRoles = Role::where('restaurant_id', $restaurantId)
                    ->whereIn('display_name', ['Staff', 'Waiter', 'Kitchen Staff'])
                    ->pluck('id')
                    ->toArray();

                if (!empty($staffRoles)) {
                    $kitchenStaff = $helperService->getUsersByRoles($restaurantId, $staffRoles);
                }
            }

            // If still no staff found, get all users with staff-related roles
            if ($kitchenStaff->isEmpty()) {

                // Get all users with phone numbers (excluding admin roles)
                $kitchenStaff = \App\Models\User::where('restaurant_id', $restaurantId)
                    ->whereNotNull('phone_number')
                    ->whereHas('roles', function ($q) {
                        $q->where('display_name', '!=', 'Admin');
                    })
                    ->get();
            }

            if ($kitchenStaff->isEmpty()) {
                return;
            }

            // CRITICAL: Get ALL items from ALL KOTs in this order
            // Multiple KOTs can be created for the same order (one per kitchen place)
            // We need to wait and retry to ensure ALL KOTs are created before collecting items
            // Query DIRECTLY from kot_items table to ensure we get everything

            // Reuse KOT ids collected during the retry loop when still accurate
            $allKotIds = $currentKotIds !== [] ? $currentKotIds : \App\Models\Kot::where('order_id', $order->id)->pluck('id')->toArray();

            // Now query ALL kot_items DIRECTLY from database for ALL KOTs
            // Use fresh() to bypass any query cache and get the latest data
            $allKotItems = \App\Models\KotItem::whereIn('kot_id', $allKotIds)
                ->with([
                    'menuItem',
                    'modifierOptions' => function ($modifierQuery) {
                        $modifierQuery->select('modifier_options.id', 'modifier_options.name', 'modifier_options.modifier_group_id');
                    }
                ])
                ->get();

            // Log detailed information about what we found
            $itemsByKot = [];
            foreach ($allKotItems as $item) {
                $kotId = $item->kot_id;
                if (!isset($itemsByKot[$kotId])) {
                    $itemsByKot[$kotId] = [];
                }
                $itemsByKot[$kotId][] = [
                    'id' => $item->id,
                    'menu_item_id' => $item->menu_item_id,
                    'quantity' => $item->quantity,
                ];
            }



            // ALWAYS check OrderItems - items might be in order but not yet in KOTs
            // This can happen if items are added after KOT creation or if there's a delay
            // Also, OrderItems are created when order status is 'billed', so they might exist even if KOT items don't
            $orderItems = \App\Models\OrderItem::where('order_id', $order->id)
                ->with([
                    'menuItem',
                    'modifierOptions' => function ($modifierQuery) {
                        $modifierQuery->select('modifier_options.id', 'modifier_options.name', 'modifier_options.modifier_group_id');
                    }
                ])
                ->get();

            // Also check CartItems - items might be in cart session but not yet converted to KOT items
            // CartSession has order_id, so we can find cart items for this order
            $cartItems = collect();
            $cartSessions = \App\Models\CartSession::where('order_id', $order->id)
                ->with([
                    'cartItems.menuItem',
                    'cartItems.menuItemVariation',
                    'cartItems.modifiers' => function ($modifierQuery) {
                        $modifierQuery->select('modifier_options.id', 'modifier_options.name', 'modifier_options.modifier_group_id');
                    }
                ])
                ->get();

            foreach ($cartSessions as $cartSession) {
                if ($cartSession->cartItems && $cartSession->cartItems->isNotEmpty()) {
                    $cartItems = $cartItems->merge($cartSession->cartItems);
                }
            }

            $orderItemsCount = $orderItems->count();
            $cartItemsCount = $cartItems->count();

            // ALWAYS merge OrderItems with KOT items to ensure we get everything
            // Create a collection that combines both sources
            $allItems = collect();

            // First, add all KOT items
            foreach ($allKotItems as $kotItem) {
                $allItems->push((object)[
                    'id' => $kotItem->id,
                    'menu_item_id' => $kotItem->menu_item_id,
                    'menu_item_variation_id' => $kotItem->menu_item_variation_id ?? null,
                    'quantity' => $kotItem->quantity,
                    'note' => $kotItem->note ?? null,
                    'menuItem' => $kotItem->menuItem,
                    'modifierOptions' => $kotItem->modifierOptions ?? collect(),
                ]);
            }

            // Then add OrderItems that aren't already in KOTs
            // Match by menu_item_id, variation_id, and quantity to avoid duplicates
            foreach ($orderItems as $orderItem) {
                // Check if this item already exists in KOT items
                $exists = $allKotItems->contains(function ($kotItem) use ($orderItem) {
                    return $kotItem->menu_item_id == $orderItem->menu_item_id
                        && ($kotItem->menu_item_variation_id ?? null) == ($orderItem->menu_item_variation_id ?? null)
                        && $kotItem->quantity == $orderItem->quantity;
                });

                if (!$exists) {

                    $allItems->push((object)[
                        'id' => $orderItem->id,
                        'menu_item_id' => $orderItem->menu_item_id,
                        'menu_item_variation_id' => $orderItem->menu_item_variation_id ?? null,
                        'quantity' => $orderItem->quantity,
                        'note' => $orderItem->note ?? null,
                        'menuItem' => $orderItem->menuItem,
                        'modifierOptions' => $orderItem->modifierOptions ?? collect(),
                    ]);
                }
            }

            // Also add CartItems that aren't already in KOTs or OrderItems
            // These are items that might be in the cart but not yet converted to KOT items
            foreach ($cartItems as $cartItem) {
                // Check if this item already exists in KOT items or OrderItems
                $existsInKot = $allKotItems->contains(function ($kotItem) use ($cartItem) {
                    return $kotItem->menu_item_id == $cartItem->menu_item_id
                        && ($kotItem->menu_item_variation_id ?? null) == ($cartItem->menu_item_variation_id ?? null)
                        && $kotItem->quantity == $cartItem->quantity;
                });

                $existsInOrder = $orderItems->contains(function ($orderItem) use ($cartItem) {
                    return $orderItem->menu_item_id == $cartItem->menu_item_id
                        && ($orderItem->menu_item_variation_id ?? null) == ($cartItem->menu_item_variation_id ?? null)
                        && $orderItem->quantity == $cartItem->quantity;
                });

                if (!$existsInKot && !$existsInOrder) {

                    // Get modifiers from cart item
                    $cartItemModifiers = collect();
                    if ($cartItem->modifiers && $cartItem->modifiers->isNotEmpty()) {
                        $cartItemModifiers = $cartItem->modifiers;
                    }

                    $allItems->push((object)[
                        'id' => $cartItem->id,
                        'menu_item_id' => $cartItem->menu_item_id,
                        'menu_item_variation_id' => $cartItem->menu_item_variation_id ?? null,
                        'quantity' => $cartItem->quantity,
                        'note' => null, // CartItems don't have notes
                        'menuItem' => $cartItem->menuItem,
                        'modifierOptions' => $cartItemModifiers,
                    ]);
                }
            }

            // Prefer order items when available because they represent the full order item list.
            // Fall back to the merged KOT/cart collection only when order items are unavailable.
            $allKotItems = $orderItems->isNotEmpty() ? $orderItems : $allItems;

            // Final fallback to current KOT items if still empty
            if ($allKotItems->isEmpty()) {
                $allKotItems = $kot->items;
            }

            $totalItemsCount = $allKotItems->count();

            // Prepare variables
            $tableNumber = $order->table->table_code ?? 'N/A';
            $orderType = $order->orderType->order_type_name ?? 'N/A';

            // Get ALL items from ALL KOTs with menu item names, modifiers (including extra toppings, dips & sauces), and notes
            // Use filter to remove any null/empty items, then map
            $itemsListArray = [];
            foreach ($allKotItems as $item) {
                // Handle both KotItem models and stdClass objects (from OrderItems/CartItems)
                // KotItem is an Eloquent model, so access properties directly
                $menuItem = null;
                $menuItemId = null;
                $quantity = null;
                $note = null;
                $modifierOptions = collect();

                // Check if it's a KotItem model (has menuItem relationship)
                if ($item instanceof \App\Models\KotItem) {
                    $menuItem = $item->menuItem;
                    $menuItemId = $item->menu_item_id;
                    $quantity = $item->quantity;
                    $note = $item->note;
                    $modifierOptions = $item->modifierOptions ?? collect();
                } else {
                    // Handle stdClass objects (from OrderItems/CartItems merge)
                    $menuItem = isset($item->menuItem) ? $item->menuItem : null;
                    $menuItemId = isset($item->menu_item_id) ? $item->menu_item_id : null;
                    $quantity = isset($item->quantity) ? $item->quantity : null;
                    $note = isset($item->note) ? $item->note : null;
                    $modifierOptions = isset($item->modifierOptions) ? $item->modifierOptions : collect();
                }

                // If menuItem is not loaded, try to load it
                if (!$menuItem && $menuItemId) {
                    $menuItem = \App\Models\MenuItem::find($menuItemId);
                }

                // Skip if no menu item
                if (!$menuItem) {
                    continue;
                }

                $itemName = $menuItem->item_name ?? $menuItem->variation ?? 'Unknown Item';
                $itemText = $itemName . ' x' . $quantity;

                // Collect all modifiers (including extra toppings, dips & sauces)
                $modifiersList = [];
                if ($modifierOptions && (is_countable($modifierOptions) ? $modifierOptions->count() : 0) > 0) {
                    // Use pivot modifier_option_name if available, otherwise use name from ModifierOption
                    foreach ($modifierOptions as $modifier) {
                        $modifierName = null;
                        // Check if it's a KotItemModifierOption or OrderItemModifierOption pivot
                        if (is_object($modifier) && isset($modifier->pivot)) {
                            $modifierName = $modifier->pivot->modifier_option_name ?? $modifier->name ?? 'Unknown Modifier';
                        } else {
                            $modifierName = is_object($modifier) && isset($modifier->name) ? $modifier->name : 'Unknown Modifier';
                        }

                        // Handle translation arrays
                        if (is_array($modifierName)) {
                            $modifierName = $modifierName[app()->getLocale()] ?? reset($modifierName);
                        }
                        $modifiersList[] = $modifierName;
                    }
                }

                // Add modifiers to item text if any
                if (!empty($modifiersList)) {
                    $modifiersText = implode(', ', $modifiersList);
                    $itemText .= ' (' . $modifiersText . ')';
                }

                // Add item note if available (might contain extra instructions)
                if (!empty($note)) {
                    $itemText .= ' [Note: ' . $note . ']';
                }

                // Add to array instead of returning
                $itemsListArray[] = $itemText;
            }

            // Join all items with comma and space
            $itemsList = !empty($itemsListArray) ? implode(', ', $itemsListArray) : 'No items';

            if (empty($itemsList)) {
                $itemsList = 'No items';
            }

            $priority = $kot->priority ?? 'Normal';
            if ($order->order_type === 'delivery' || $order->order_type === 'pickup') {
                $priority = 'High';
            }

            // Determine if this is a new KOT or regenerated/updated KOT
            $timeDiff = $kot->created_at->diffInSeconds($kot->updated_at);
            $kotStatus = ($timeDiff <= 5) ? 'New KOT' : 'KOT Updated';

            // Ensure itemsList is a string and not empty
            if (empty($itemsList) || !is_string($itemsList)) {
                $itemsList = 'No items';
            }

            $variables = [
                $kot->kot_number ?? 'N/A',                    // [0] - KOT number
                $order->show_formatted_order_number ?? 'N/A', // [1] - Order number
                $tableNumber,                                 // [2] - Table number
                $orderType,                                   // [3] - Order type
                $itemsList,                                   // [4] - Items list (THIS IS THE KEY ONE)
                $kot->created_at->format('d M, Y H:i'),       // [5] - Time
                $priority,                                    // [6] - Priority
                $kotStatus,                                   // [7] - Status: "New KOT" or "KOT Updated"
                $order->id ?? null,                           // [8] - Order ID for button URL
                $order->branch->restaurant->hash ?? null,     // [9] - Restaurant hash for URL (if needed)
            ];

            // Send notification to all kitchen staff
            foreach ($kitchenStaff as $staff) {
                $formattedPhone = WhatsAppPhoneResolver::fromUser($staff);
                if ($formattedPhone) {
                    $notificationService->send(
                        $restaurantId,
                        'kitchen_notification',
                        $formattedPhone,
                        $variables,
                        'en',
                        'staff'
                    );
                } else {
                }
            }
        } catch (\Exception $e) {
        }
    }
}
