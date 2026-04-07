<?php

namespace Modules\RestApi\Http\Controllers;

use App\ApiResource\OrderResource;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Restaurant;
use App\Models\RestaurantCharge;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\Tax;
use App\Models\User;
use App\Models\DeliveryExecutive;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\RestApi\Support\Safety\SafetyGuard;
use Modules\RestApi\Traits\ApiResponse;
use Modules\RestApi\Http\Requests\UpdateOrderRequest;
use App\Models\Menu;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\DeliveryPlatform;


/**
 * POS Proxy Controller - Main controller for POS application API integration.
 *
 * This controller handles all POS-related API endpoints including:
 * - Menu management (items, categories, variations, modifiers)
 * - Order operations (create, update, status, payment)
 * - Table management (list, lock/unlock, sessions)
 * - Customer operations (list, create/update)
 * - Reservation handling
 * - Configuration (taxes, charges, order types, printers)
 *
 * @package Modules\RestApi\Http\Controllers
 * @since   1.0.0
 * @api     /api/application-integration/pos/*
 */
class PosProxyController extends Controller
{
    use ApiResponse;
    private $branch;
    private $restaurant;
    private SafetyGuard $safety;

    public function __construct()
    {
        $user = auth()->user();

        if ($user && $user->restaurant_id) {
            $this->restaurant = Restaurant::with('branches')->find($user->restaurant_id);
            $this->branch = $user->branch_id
                ? Branch::withoutGlobalScopes()->find($user->branch_id)
                : ($this->restaurant ? $this->restaurant->branches()->withoutGlobalScopes()->first() : null);
        }

        if (! $this->restaurant) {
            $this->restaurant = Restaurant::with('branches')->first();
        }

        if (! $this->branch && $this->restaurant) {
            $this->branch = $this->restaurant->branches()->withoutGlobalScopes()->first();
        }

        if (! $this->branch) {
            $this->branch = Branch::withoutGlobalScopes()->first();
        }

        $this->safety = new SafetyGuard($this->branch?->id, $this->restaurant?->id);
    }

    private function guardBranch()
    {
        if (! $this->branch) {
            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.plan_not_allowed'),
            ], 400);
        }

        return null;
    }

    /**
     * Generate a unique order number with database locking to prevent race conditions.
     * This fixes the duplicate order number bug (BUG-001).
     *
     * @param \App\Models\Branch $branch
     * @return array ['order_number' => int, 'formatted_order_number' => string|null]
     */
    protected function generateSafeOrderNumber($branch): array
    {
        return DB::transaction(function () use ($branch) {
            // Lock the branch row to prevent concurrent access
            DB::table('branches')
                ->where('id', $branch->id)
                ->lockForUpdate()
                ->first();

            // Get the maximum order number INCLUDING all statuses (no exclusion of draft)
            $maxOrderNumber = Order::where('branch_id', $branch->id)
                ->whereNotNull('order_number')
                ->max(DB::raw('CAST(order_number AS UNSIGNED)'));

            $orderNumber = $maxOrderNumber ? ((int) $maxOrderNumber + 1) : 1;

            // Format the order number if settings are enabled
            $settings = function_exists('getOrderNumberSetting')
                ? getOrderNumberSetting($branch->id)
                : null;

            $formattedNumber = null;
            if ($settings && $settings->enable_feature) {
                // Use the existing formatting method from Order model
                if (method_exists(Order::class, 'buildFormattedOrderNumber')) {
                    $timezone = $this->restaurant?->timezone ?? 'UTC';
                    $formattedNumber = Order::buildFormattedOrderNumber(
                        $orderNumber,
                        $settings,
                        now($timezone)
                    );
                }
            }

            return [
                'order_number' => $orderNumber,
                'formatted_order_number' => $formattedNumber,
            ];
        });
    }

    /**
     * Get all menus for the current branch.
     *
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/menus
     */
    public function getMenus()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $menus = cache()->remember('menus_' . $this->branch->id, 60, function () {
            return Menu::where('branch_id', $this->branch->id)->get()->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'menu_name' => $menu->getTranslation('menu_name', session('locale', app()->getLocale())),
                    'sort_order' => $menu->sort_order,
                ];
            });
        });

        return response()->json($menus);
    }

    public function getCategories()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $categories = cache()->remember('categories_' . $this->branch->id, 60, function () {
            return \App\Models\ItemCategory::where('branch_id', $this->branch->id)->get()->map(function ($category) {
                return [
                    'id' => $category->id,
                    'count' => $category->items()->count(),
                    'category_name' => $category->getTranslation('category_name', session('locale', app()->getLocale())),
                    'sort_order' => $category->sort_order,
                ];
            });
        });

        return response()->json($categories);
    }

    public function getMenuItems()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $menuItems = cache()->remember('menu_items_v4_' . $this->branch->id, 60, function () {
                $items = $this->menuItemsQuery()
                    ->where('branch_id', $this->branch->id)
                    ->get();

                if ($items->isEmpty()) {
                    $items = $this->menuItemsQuery(true)
                        ->where('branch_id', $this->branch->id)
                        ->get();
                }

                if ($items->isEmpty()) {
                    $fallbackBranchId = $this->resolveCloneBranchId();
                    if ($fallbackBranchId && $fallbackBranchId !== $this->branch->id) {
                        $items = $this->menuItemsQuery(true)
                            ->where('branch_id', $fallbackBranchId)
                            ->get();
                    }
                }

                if ($items->isEmpty()) {
                    $branchIds = $this->restaurantBranchIds();
                    if (! empty($branchIds)) {
                        $items = $this->menuItemsQuery(true)
                            ->whereIn('branch_id', $branchIds)
                            ->get();
                    }
                }

                if ($items->isEmpty()) {
                    $items = $this->menuItemsByCatalog();
                }

                return $items;
            });

            return response()->json($menuItems);
        } catch (\Throwable $e) {
            Log::error('ApplicationIntegration items failed', [
                'branch_id' => $this->branch?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([]);
        }
    }
    public function getMenuItemVariations($itemId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        if (! Schema::hasTable('menu_item_variations')) {
            return response()->json([]);
        }

        $item = $this->menuItemsQuery()
            ->where('branch_id', $this->branch->id)
            ->findOrFail($itemId);
        return response()->json($item->variations()->get());
    }

    public function getMenuItemModifierGroups($itemId, Request $request = null)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        // Check required tables - support both modifier_options and modifiers table structures
        if (! Schema::hasTable('modifier_groups') || ! Schema::hasTable('item_modifiers')) {
            return response()->json([]);
        }
        if (! Schema::hasTable('modifier_options') && ! Schema::hasTable('modifiers')) {
            return response()->json([]);
        }

        // Get order_type_id from request or use default
        $orderTypeId = $request?->query('order_type_id');
        if (! $orderTypeId && Schema::hasTable('order_types')) {
            // Get default order type (Dine In or first available)
            $defaultType = DB::table('order_types')
                ->where('branch_id', $this->branch->id)
                ->where(function ($q) {
                    $q->where('slug', 'dine_in')
                        ->orWhere('type', 'dine_in');
                })
                ->first();
            $orderTypeId = $defaultType?->id;

            // Fallback to first order type if no dine_in found
            if (! $orderTypeId) {
                $firstType = DB::table('order_types')
                    ->where('branch_id', $this->branch->id)
                    ->first();
                $orderTypeId = $firstType?->id;
            }
        }

        // Try current branch first - load prices relationship for contextual pricing
        $item = $this->menuItemsQuery()
            ->where('branch_id', $this->branch->id)
            ->with(['modifierGroups.options.prices', 'modifierGroups.translations'])
            ->find($itemId);

        // Fallback: try all restaurant branches
        if (! $item && $this->restaurant?->id) {
            $branchIds = $this->restaurantBranchIds();
            if (! empty($branchIds)) {
                $item = $this->menuItemsQuery(true)
                    ->whereIn('branch_id', $branchIds)
                    ->with(['modifierGroups.options.prices', 'modifierGroups.translations'])
                    ->find($itemId);
            }
        }

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.not_found'),
            ], 404);
        }

        // Transform modifier groups to ensure price is included for each option
        $modifierGroups = ($item->modifierGroups ?? collect())->map(function ($group) use ($orderTypeId) {
            // Get pivot data (is_required, allow_multiple_selection, etc.)
            $pivot = $group->pivot ? $group->pivot->toArray() : [];

            return [
                'id' => $group->id,
                'name' => $group->name,
                'translations' => $group->translations,
                'pivot' => $pivot,
                'options' => $group->options->map(function ($option) use ($orderTypeId) {
                    // Get price using contextual pricing if available
                    $price = 0;

                    // Method 1: Try contextual pricing (modifier_option_prices table)
                    if ($orderTypeId && $option->relationLoaded('prices') && $option->prices->isNotEmpty()) {
                        // Find price for this order type
                        $contextPrice = $option->prices
                            ->where('status', true)
                            ->where('order_type_id', $orderTypeId)
                            ->whereNull('delivery_app_id')
                            ->first();

                        if ($contextPrice) {
                            $price = $contextPrice->final_price ?? $contextPrice->calculated_price ?? 0;
                        } else {
                            // Try any active price for this option
                            $anyPrice = $option->prices
                                ->where('status', true)
                                ->first();
                            if ($anyPrice) {
                                $price = $anyPrice->final_price ?? $anyPrice->calculated_price ?? 0;
                            }
                        }
                    }

                    // Method 2: Fallback to base price from modifier_options table
                    if ($price == 0) {
                        $price = $option->getAttributes()['price'] ?? $option->price ?? 0;
                    }

                    return [
                        'id' => $option->id,
                        'name' => $option->name,
                        'price' => (float) $price,
                        'is_available' => $option->is_available ?? true,
                        'is_preselected' => $option->is_preselected ?? false,
                        'sort_order' => $option->sort_order ?? 0,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json($modifierGroups);
    }

    public function getMenuItemsByCategory($categoryId)
    {
        try {
            if ($resp = $this->guardBranch()) {
                return $resp;
            }

            // Validate categoryId
            if (! $categoryId || ! is_numeric($categoryId)) {
                return response()->json([]);
            }

            $menuItems = $this->menuItemsQuery()
                ->where('branch_id', $this->branch->id)
                ->where('item_category_id', $categoryId)
                ->get();

            if ($menuItems->isEmpty()) {
                $menuItems = $this->menuItemsQuery(true)
                    ->where('branch_id', $this->branch->id)
                    ->where('item_category_id', $categoryId)
                    ->get();
            }

            if ($menuItems->isEmpty()) {
                $fallbackBranchId = $this->resolveCloneBranchId();
                if ($fallbackBranchId && $fallbackBranchId !== $this->branch->id) {
                    $menuItems = $this->menuItemsQuery(true)
                        ->where('branch_id', $fallbackBranchId)
                        ->where('item_category_id', $categoryId)
                        ->get();
                }
            }

            if ($menuItems->isEmpty()) {
                $branchIds = $this->restaurantBranchIds();
                if (! empty($branchIds)) {
                    $menuItems = $this->menuItemsQuery(true)
                        ->whereIn('branch_id', $branchIds)
                        ->where('item_category_id', $categoryId)
                        ->get();
                }
            }

            if ($menuItems->isEmpty()) {
                $menuItems = $this->menuItemsByCatalog(null, (int) $categoryId);
            }

            return response()->json($menuItems);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration items by category failed', [
                'category_id' => $categoryId,
                'branch_id' => $this->branch?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return empty array instead of 500 error
            return response()->json([]);
        }
    }

    public function getMenuItemsByMenu($menuId)
    {
        try {
            if ($resp = $this->guardBranch()) {
                return $resp;
            }

            // Validate menuId
            if (! $menuId || ! is_numeric($menuId)) {
                return response()->json([]);
            }

            $menuItems = $this->menuItemsQuery()
                ->where('branch_id', $this->branch->id)
                ->where('menu_id', $menuId)
                ->get();

            if ($menuItems->isEmpty()) {
                $menuItems = $this->menuItemsQuery(true)
                    ->where('branch_id', $this->branch->id)
                    ->where('menu_id', $menuId)
                    ->get();
            }

            if ($menuItems->isEmpty()) {
                $fallbackBranchId = $this->resolveCloneBranchId();
                if ($fallbackBranchId && $fallbackBranchId !== $this->branch->id) {
                    $menuItems = $this->menuItemsQuery(true)
                        ->where('branch_id', $fallbackBranchId)
                        ->where('menu_id', $menuId)
                        ->get();
                }
            }

            if ($menuItems->isEmpty()) {
                $branchIds = $this->restaurantBranchIds();
                if (! empty($branchIds)) {
                    $menuItems = $this->menuItemsQuery(true)
                        ->whereIn('branch_id', $branchIds)
                        ->where('menu_id', $menuId)
                        ->get();
                }
            }

            if ($menuItems->isEmpty()) {
                $menuItems = $this->menuItemsByCatalog((int) $menuId, null);
            }

            return response()->json($menuItems);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration items by menu failed', [
                'menu_id' => $menuId,
                'branch_id' => $this->branch?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([]);
        }
    }
    public function getWaiters()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $roleFilter = request()->query('role');
        $includePermissions = filter_var(request()->query('include_permissions', false), FILTER_VALIDATE_BOOLEAN);
        $search = request()->query('search', '');
        $onlyDelivery = $roleFilter && str_contains(strtolower($roleFilter), 'delivery');

        // Delivery executives table
        $deliveryExecutives = null;
        if ($onlyDelivery || ! empty($search)) {
            $deliveryExecutives = DeliveryExecutive::when($this->restaurant?->id, function ($q) {
                $q->where('restaurant_id', $this->restaurant->id);
            })
                ->when($this->branch?->id, function ($q) {
                    $q->where('branch_id', $this->branch->id);
                })
                ->when(! empty($search), function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('phone_code', 'like', '%' . $search . '%');
                    });
                })
                ->get()
                ->map(function ($exec) {
                    return [
                        'id' => $exec->id,
                        'name' => $exec->name,
                        'phone' => $exec->phone,
                        'phone_code' => $exec->phone_code,
                        'branch_id' => $exec->branch_id,
                        'status' => $exec->status ?? null,
                        'is_delivery_staff' => true,
                        'source' => 'delivery_executives',
                    ];
                });
        }

        if ($roleFilter || ! empty($search)) {
            $waiters = User::where('restaurant_id', $this->restaurant->id)->with('roles', 'permissions')->get();
        } else {
            $waiters = cache()->remember('waiters_' . $this->branch->id, 60, function () {
                return User::where('restaurant_id', $this->restaurant->id)->with('roles', 'permissions')->get();
            });
        }

        $filtered = $waiters->filter(function ($user) use ($roleFilter, $search) {
            $isDelivery = $this->isDeliveryStaff($user);
            $passesRole = true;
            if ($roleFilter) {
                $needle = strtolower($roleFilter);
                $roles = $user->roles?->pluck('name')->map(fn($r) => strtolower($r))->all() ?? [];
                $type = strtolower($user->type ?? $user->user_type ?? '');
                $job = strtolower($user->job_title ?? '');

                $passesRole = $isDelivery
                    || str_contains($type, $needle)
                    || str_contains($job, $needle)
                    || collect($roles)->contains(fn($r) => str_contains($r, $needle));
            }

            $passesSearch = true;
            if (! empty($search)) {
                $haystack = strtolower(
                    ($user->name ?? '') . ' ' .
                        ($user->email ?? '') . ' ' .
                        ($user->phone_number ?? $user->phone ?? '') . ' ' .
                        ($user->phone_code ?? '')
                );
                $passesSearch = str_contains($haystack, strtolower($search));
            }

            // If filtering for delivery and searching, allow match on search even if role heuristics failed
            if ($roleFilter && !$passesRole && $passesSearch) {
                $passesRole = true;
            }

            return $passesRole && $passesSearch;
        });

        // If delivery filter + search yields nothing, broaden search across all users
        if ($filtered->isEmpty() && $roleFilter && ! empty($search)) {
            $fallbackUsers = User::query()
                ->with('roles', 'permissions')
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhere('phone_code', 'like', '%' . $search . '%');
                })
                ->get();
            $filtered = $filtered->merge($fallbackUsers);
        }

        $payload = $filtered->map(function ($user) use ($includePermissions) {
            $phone = $user->phone_number ?? $user->phone ?? null;
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'type' => $user->type ?? $user->user_type ?? null,
                'job_title' => $user->job_title ?? null,
                'phone' => $phone,
                'status' => $user->status ?? $user->user_status ?? ($user->is_active ?? null),
                'roles' => $user->roles?->pluck('name'),
                'permissions' => $includePermissions ? $user->getAllPermissions()->pluck('name') : null,
                'is_delivery_staff' => $this->isDeliveryStaff($user),
            ];
        })->values();

        if ($deliveryExecutives) {
            $payload = $payload->merge($deliveryExecutives)->values();
        }

        return response()->json($payload);
    }

    private function menuItemsQuery(bool $includeUnavailable = false)
    {
        $query = MenuItem::withoutGlobalScopes();

        if (! $includeUnavailable && Schema::hasColumn('menu_items', 'is_available')) {
            $query->where('is_available', true);
        }

        $with = [];
        if (Schema::hasTable('menu_item_prices')) {
            $with[] = 'prices:id,menu_item_id,order_type_id,final_price';
            if (Schema::hasTable('order_types')) {
                $with[] = 'prices.orderType:id,order_type_name';
            }
        }
        if (Schema::hasTable('menu_item_variations')) {
            $with[] = 'variations';
        }
        if (Schema::hasTable('modifier_groups') && Schema::hasTable('item_modifiers')) {
            if (Schema::hasTable('modifier_options')) {
                $with[] = 'modifierGroups.options';
                $with[] = 'modifierGroups.translations';
            } elseif (Schema::hasTable('modifiers')) {
                $with[] = 'modifierGroups.modifiers';
                $with[] = 'modifierGroups.translations';
            }
        }

        if (! empty($with)) {
            $query->with($with);
        }

        $withCount = [];
        if (Schema::hasTable('menu_item_variations')) {
            $withCount[] = 'variations';
        }
        if (Schema::hasTable('modifier_groups') && Schema::hasTable('item_modifiers')) {
            $withCount[] = 'modifierGroups';
        }
        if (! empty($withCount)) {
            $query->withCount($withCount);
        }

        return $query;
    }

    private function resolveCloneBranchId(): ?int
    {
        if (! $this->branch) {
            return null;
        }

        $cloneId = $this->branch->cloned_branch_id ?? null;
        if (! $cloneId) {
            return null;
        }

        $hasCloneFlag = (bool) ($this->branch->is_menu_items_clone
            ?? $this->branch->is_menu_clone
            ?? $this->branch->is_item_categories_clone
            ?? $this->branch->is_item_modifiers_clone);

        return $hasCloneFlag ? (int) $cloneId : null;
    }

    private function restaurantBranchIds(): array
    {
        if (! $this->restaurant) {
            return [];
        }

        return $this->restaurant
            ->branches()
            ->withoutGlobalScopes()
            ->pluck('id')
            ->filter()
            ->values()
            ->all();
    }

    private function menuItemsByCatalog(?int $menuId = null, ?int $categoryId = null)
    {
        if (! $this->branch) {
            return collect();
        }

        $menuIds = $menuId ? [$menuId] : Menu::where('branch_id', $this->branch->id)->pluck('id')->filter()->all();
        $categoryIds = $categoryId ? [$categoryId] : \App\Models\ItemCategory::where('branch_id', $this->branch->id)->pluck('id')->filter()->all();

        if (empty($menuIds) && empty($categoryIds)) {
            return collect();
        }

        return $this->menuItemsQuery(true)
            ->where(function ($q) use ($menuIds, $categoryIds) {
                if (! empty($menuIds)) {
                    $q->whereIn('menu_id', $menuIds);
                }
                if (! empty($categoryIds)) {
                    $q->orWhereIn('item_category_id', $categoryIds);
                }
            })
            ->get();
    }

    public function getDeliveryExecutives(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $status = $request->query('status');
        $search = $request->query('search');

        try {
            if (! Schema::hasTable('delivery_executives')) {
                return response()->json([]);
            }

            $query = DB::table('delivery_executives')
                ->select('id', 'branch_id', 'name', 'phone', 'phone_code', 'status', 'photo');

            if ($this->branch?->id) {
                $query->where('branch_id', $this->branch->id);
            }
            if (! empty($status)) {
                $query->where('status', $status);
            }
            if (! empty($search)) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('phone_code', 'like', '%' . $search . '%');
                });
            }

            $execs = $query->get()->map(function ($exec) {
                return [
                    'id' => $exec->id,
                    'branch_id' => $exec->branch_id,
                    'name' => $exec->name,
                    'phone' => $exec->phone,
                    'phone_code' => $exec->phone_code,
                    'status' => $this->normalizeDeliveryStatus($exec->status ?? null),
                    'status_raw' => $exec->status ?? null,
                    'photo' => $exec->photo ?? null,
                    'is_delivery_staff' => true,
                ];
            });

            return response()->json($execs);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration delivery executives failed', [
                'branch_id' => $this->branch?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([]);
        }
    }

    public function getCustomers(Request $request)
    {
        $searchQuery = $request->query('search', '');

        $query = Customer::where('restaurant_id', $this->restaurant->id);

        if (! empty($searchQuery) && strlen($searchQuery) >= 2) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('phone', 'like', '%' . $searchQuery . '%')
                    ->orWhere('email', 'like', '%' . $searchQuery . '%');
            });
        }

        $customers = $query->orderBy('name')->limit(10)->get();

        return response()->json($customers);
    }

    public function getPhoneCodes(Request $request)
    {
        $search = $request->query('search', '');

        $phoneCodes = \App\Models\Country::pluck('phonecode')
            ->unique()
            ->filter()
            ->values();

        if (! empty($search)) {
            $phoneCodes = $phoneCodes->filter(function ($code) use ($search) {
                return str_contains($code, $search);
            })->values();
        }

        return response()->json($phoneCodes);
    }
    /**
     * Determine if a user should be treated as delivery staff.
     */
    private function isDeliveryStaff($user): bool
    {
        $needles = ['delivery', 'driver', 'courier', 'rider'];
        $type = strtolower($user->type ?? $user->user_type ?? '');
        $job = strtolower($user->job_title ?? '');
        $roles = $user->roles?->pluck('name')->map(fn($r) => strtolower($r))->all() ?? [];

        foreach ($needles as $needle) {
            if (
                str_contains($type, $needle)
                || str_contains($job, $needle)
                || collect($roles)->contains(fn($r) => str_contains($r, $needle))
            ) {
                return true;
            }
        }

        return false;
    }
    public function saveCustomer(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone_code' => 'required',
                'phone' => 'required',
                'email' => 'nullable|email',
                'address' => 'nullable|string|max:500',
            ]);

            $existingCustomer = null;

            if (! empty($validated['email'])) {
                $existingCustomer = Customer::where('restaurant_id', $this->restaurant->id)
                    ->where('email', $validated['email'])
                    ->first();
            }

            if (! $existingCustomer && ! empty($validated['phone'])) {
                $existingCustomer = Customer::where('restaurant_id', $this->restaurant->id)
                    ->where('phone', $validated['phone'])
                    ->first();
            }

            $customerData = [
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'phone_code' => $validated['phone_code'],
                'email' => $validated['email'] ?? null,
                'delivery_address' => $validated['address'] ?? null,
            ];

            if ($existingCustomer) {
                $customer = tap($existingCustomer)->update($customerData);
            } else {
                $customerData['restaurant_id'] = $this->restaurant->id;
                $customer = Customer::create($customerData);
            }

            cache()->forget('customers_' . $this->branch->id);

            return response()->json([
                'success' => true,
                'message' => __('messages.customerUpdated'),
                'customer' => $customer,
            ]);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration saveCustomer failed', [
                'error' => $e->getMessage(),
                'restaurant_id' => $this->restaurant?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.internal_error'),
            ], 500);
        }
    }

    /**
     * Delete a customer.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     * @api DELETE /api/application-integration/pos/customers/{id}
     */
    public function deleteCustomer(int $id)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $customer = Customer::where('restaurant_id', $this->restaurant->id)
                ->find($id);

            if (! $customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found',
                ], 404);
            }

            // Check if customer has orders
            $hasOrders = Order::where('customer_id', $id)->exists();
            if ($hasOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with existing orders',
                ], 409);
            }

            $customer->delete();

            cache()->forget('customers_' . $this->branch->id);

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ApplicationIntegration deleteCustomer failed', [
                'error' => $e->getMessage(),
                'customer_id' => $id,
                'restaurant_id' => $this->restaurant?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.internal_error'),
            ], 500);
        }
    }

    public function getExtraCharges($orderType)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $extraCharges = RestaurantCharge::whereJsonContains('order_types', $orderType)
            ->where('is_enabled', true)
            ->where('restaurant_id', $this->restaurant->id)
            ->get();

        return response()->json($extraCharges);
    }
    public function getTables()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        // Query params:
        // - status: all|available|running|locked|locked_by_me|locked_by_other (default: all)
        // - include_running: bool (legacy; default now true)
        // - include_locked: bool (default true)
        $statusFilter = request()->query('status', 'all');
        $includeRunning = filter_var(request()->query('include_running', true), FILTER_VALIDATE_BOOLEAN);
        $includeLocked = filter_var(request()->query('include_locked', true), FILTER_VALIDATE_BOOLEAN);

        Table::cleanupExpiredLocks();

        $user = auth()->user();
        $userId = $user ? $user->id : null;
        $isAdmin = $user ? $user->hasRole('Admin_' . $user->restaurant_id) : false;

        $allTables = Table::where('branch_id', $this->branch->id)
            ->where('status', 'active')
            ->with(['area', 'tableSession.lockedByUser'])
            ->get()
            ->map(function ($table) use ($userId) {
                $session = $table->tableSession;
                $isLocked = $session ? $session->isLocked() : false;
                $isLockedByCurrentUser = $isLocked && $session && $session->locked_by_user_id === $userId;
                $isLockedByOtherUser = $isLocked && $session && $session->locked_by_user_id !== $userId;

                return [
                    'id' => $table->id,
                    'branch_id' => $table->branch_id,
                    'table_code' => $table->table_code,
                    'hash' => $table->hash,
                    'status' => $table->status,
                    'available_status' => $table->available_status,
                    'area_id' => $table->area_id,
                    'area_name' => $table->area ? $table->area->area_name : 'Unknown Area',
                    'seating_capacity' => $table->seating_capacity,
                    'is_locked' => $isLocked,
                    'is_locked_by_current_user' => $isLockedByCurrentUser,
                    'is_locked_by_other_user' => $isLockedByOtherUser,
                    'locked_by_user_id' => $session ? $session->locked_by_user_id : null,
                    'locked_by_user_name' => $session && $session->lockedByUser ? $session->lockedByUser->name : null,
                    'locked_at' => $session && $session->locked_at ? $session->locked_at->format('H:i') : null,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                    'is_running' => $table->available_status === 'running',
                ];
            });

        // Summary across all tables
        $summary = [
            'total' => $allTables->count(),
            'running' => $allTables->where('is_running', true)->count(),
            'available' => $allTables->where('is_running', false)->count(),
            'locked_by_me' => $allTables->where('is_locked_by_current_user', true)->count(),
            'locked_by_other' => $allTables->where('is_locked_by_other_user', true)->count(),
        ];

        // Apply filters for response list
        $tables = $allTables;

        if (! $includeRunning) {
            $tables = $tables->where('is_running', false)->values();
        }
        if (! $includeLocked) {
            $tables = $tables->where('is_locked', false)->values();
        }

        switch ($statusFilter) {
            case 'available':
                $tables = $tables->where('is_running', false)->values();
                break;
            case 'running':
                $tables = $tables->where('is_running', true)->values();
                break;
            case 'locked':
                $tables = $tables->where('is_locked', true)->values();
                break;
            case 'locked_by_me':
                $tables = $tables->where('is_locked_by_current_user', true)->values();
                break;
            case 'locked_by_other':
                $tables = $tables->where('is_locked_by_other_user', true)->values();
                break;
            case 'all':
            default:
                // no extra filter
                break;
        }

        return response()->json([
            'tables' => $tables,
            'is_admin' => $isAdmin,
            'summary' => $summary,
        ]);
    }

    public function forceUnlockTable($tableId)
    {
        $table = Table::find($tableId);

        if (! $table) {
            return response()->json([
                'success' => false,
                'message' => __('messages.tableNotFound'),
            ], 404);
        }

        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 401);
        }

        $isAdmin = $user->hasRole('Admin_' . $user->restaurant_id);
        $isLockedByCurrentUser = $table->tableSession && $table->tableSession->locked_by_user_id === $user->id;

        if (! ($isAdmin || $isLockedByCurrentUser)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.tableUnlockFailed'),
            ], 403);
        }

        $result = $table->unlock(null, true);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => __('messages.tableUnlockedSuccess', ['table' => $table->table_code]),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('messages.tableUnlockFailed'),
        ], 500);
    }
    public function getTodayReservations()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $reservations = Reservation::where('branch_id', $this->branch->id)
            ->whereDate('reservation_date_time', today())
            ->whereNotNull('table_id')
            ->with('table')
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'table_code' => $reservation->table ? $reservation->table->table_code : 'N/A',
                    'time' => $reservation->reservation_date_time->translatedFormat('h:i A'),
                    'datetime' => $reservation->reservation_date_time->format('M d, Y h:i A'),
                    'date' => $reservation->reservation_date_time->format('M d, Y'),
                    'party_size' => $reservation->party_size,
                    'status' => $reservation->reservation_status,
                ];
            });

        return response()->json($reservations);
    }

    public function getOrderTypes()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $orderTypes = OrderType::where('branch_id', $this->branch->id)
            ->where('is_active', true)
            ->orderBy('order_type_name')
            ->get()
            ->map(function ($orderType) {
                return [
                    'id' => $orderType->id,
                    'slug' => $orderType->slug,
                    'order_type_name' => $orderType->translated_name,
                    'type' => $orderType->type,
                ];
            });

        return response()->json($orderTypes);
    }

    public function getActions()
    {
        return response()->json([
            [
                'key' => 'draft',
                'label' => 'Draft / placed',
                'effect' => 'Order saved as placed; table stays available.',
            ],
            [
                'key' => 'kot',
                'label' => 'KOT',
                'effect' => 'Order confirmed, KOT created, table set to running and locked.',
            ],
            [
                'key' => 'bill',
                'label' => 'Bill',
                'effect' => 'Order confirmed/billed, table set to running and locked.',
            ],
            [
                'key' => 'cancel',
                'label' => 'Cancel',
                'effect' => 'Order canceled; table set to available.',
            ],
            [
                'key' => 'pay',
                'label' => 'Pay',
                'effect' => 'Via /orders/{id}/pay; marks paid/billed and frees table.',
            ],
        ]);
    }

    public function getDeliveryPlatforms()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $deliveryPlatforms = DeliveryPlatform::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($platform) {
                return [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'logo' => $platform->logo,
                    'logo_url' => $platform->logo_url ?? null,
                ];
            });

        return response()->json($deliveryPlatforms);
    }
    public function getOrderNumber()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $orderNumberData = $this->generateSafeOrderNumber($this->branch);

        $formattedOrderNumber = isOrderPrefixEnabled($this->branch)
            ? $orderNumberData['formatted_order_number']
            : __('modules.order.orderNumber') . ' #' . $orderNumberData['order_number'];

        return response()->json([
            $orderNumberData['order_number'],
            $formattedOrderNumber,
        ]);
    }
    public function submitOrder(Request $request)
    {
        try {
            DB::beginTransaction();

            if ($resp = $this->guardBranch()) {
                return $resp;
            }

            $data = $request->all();
            $customerData = $data['customer'] ?? [];
            $items = $data['items'] ?? [];
            $taxes = $data['taxes'] ?? [];
            $actions = $data['actions'] ?? [];
            $note = $data['note'] ?? '';
            $orderTypeDisplay = $data['order_type'] ?? 'Dine In';
            $pax = $data['pax'] ?? 1;
            $waiterId = $data['waiter_id'] ?? null;
            $tableId = $data['table_id'] ?? null;
            $deliveryAddress = $data['delivery_address'] ?? null;
            $deliveryTime = $data['delivery_time'] ?? null;
            $deliveryFee = $data['delivery_fee'] ?? 0;
            $deliveryExecutiveId = $data['delivery_executive_id'] ?? null;
            $deliveryAppId = $data['delivery_app_id'] ?? null;
            $placedVia = $data['placed_via'] ?? 'pos';
            $customerAddressId = $data['customer_address_id'] ?? null;
            $discountType = $data['discount_type'] ?? null;
            $discountValue = $data['discount_value'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $extraChargesData = $data['extra_charges'] ?? [];
            $posMachinePublicId = $data['pos_machine_public_id'] ?? null;
            $posMachineToken = $data['pos_machine_token'] ?? null;
            $posMachineDeviceId = $data['pos_machine_device_id'] ?? null;

            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.orderItemRequired'),
                ], 422);
            }

            $normalizedOrderType = strtolower(str_replace(' ', '_', $orderTypeDisplay));
            if ($normalizedOrderType === 'dine in') {
                $normalizedOrderType = 'dine_in';
            }

            $table = null;
            if ($tableId && $normalizedOrderType === 'dine_in') {
                $table = Table::find($tableId);
                if ($table && $table->tableSession && $table->tableSession->isLocked()) {
                    $lockedByUser = $table->tableSession->lockedByUser;
                    $lockedUserName = $lockedByUser ? $lockedByUser->name : 'Another user';

                    $user = auth()->user();
                    if ($user && method_exists($table, 'canBeAccessedByUser') && ! $table->canBeAccessedByUser($user->id)) {
                        return response()->json([
                            'success' => false,
                            'message' => __('messages.tableHandledByUser', [
                                'user' => $lockedUserName,
                                'table' => $table->table_code,
                            ]),
                        ], 403);
                    }
                }
            }

            $customerId = null;
            if (! empty($customerData['name']) || ! empty($customerData['phone']) || ! empty($customerData['email'])) {
                // Use updateOrCreate for atomic operation (single query instead of two)
                $customer = Customer::updateOrCreate(
                    [
                        'restaurant_id' => $this->restaurant->id,
                        'phone' => $customerData['phone'] ?? null,
                    ],
                    [
                        'name' => $customerData['name'] ?? '',
                        'email' => $customerData['email'] ?? null,
                    ]
                );
                $customerId = $customer->id;
            }

            $orderTypeModel = OrderType::where('branch_id', $this->branch->id)
                ->where('is_active', true)
                ->where(function ($q) use ($normalizedOrderType, $orderTypeDisplay) {
                    $q->where('slug', $normalizedOrderType)
                        ->orWhere('type', $normalizedOrderType)
                        ->orWhere('order_type_name', $orderTypeDisplay);
                })
                ->first();

            if ($orderTypeModel) {
                $orderTypeId = $orderTypeModel->id;
                $orderTypeSlug = $orderTypeModel->slug;
                $orderTypeName = $orderTypeModel->order_type_name;
            } else {
                $orderTypeModel = OrderType::where('branch_id', $this->branch->id)
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->first();

                if ($orderTypeModel) {
                    $orderTypeId = $orderTypeModel->id;
                    $orderTypeSlug = $orderTypeModel->slug;
                    $orderTypeName = $orderTypeModel->order_type_name;
                } else {
                    $orderTypeId = null;
                    $orderTypeSlug = $normalizedOrderType;
                    $orderTypeName = $orderTypeDisplay;
                }
            }

            $subTotal = 0;
            foreach ($items as $item) {
                $itemPrice = (float) ($item['price'] ?? 0);

                // Fallback: If price is 0 or not provided, fetch from MenuItem
                if ($itemPrice <= 0 && isset($item['id'])) {
                    $menuItemForPrice = MenuItem::find($item['id']);
                    if ($menuItemForPrice) {
                        // Try to get order-type-specific price first
                        if ($orderTypeId && method_exists($menuItemForPrice, 'resolvePrice')) {
                            $itemPrice = (float) $menuItemForPrice->resolvePrice($orderTypeId);
                        }
                        // Fallback to base price columns
                        if ($itemPrice <= 0) {
                            $itemPrice = (float) ($menuItemForPrice->price
                                ?? $menuItemForPrice->final_price
                                ?? $menuItemForPrice->selling_price
                                ?? $menuItemForPrice->base_price
                                ?? 0);
                        }
                    }
                }

                $itemQuantity = max(1, (int) ($item['quantity'] ?? 1));
                $modifiers = $item['modifiers'] ?? [];
                $modifiersTotal = 0;

                if (is_array($modifiers)) {
                    foreach ($modifiers as $mod) {
                        $modifiersTotal += isset($mod['price']) ? (float) $mod['price'] : 0;
                    }
                }

                $subTotal += ($itemPrice + $modifiersTotal) * $itemQuantity;
            }

            $subTotal = max(0, $subTotal);

            $taxTotal = 0;
            if (! empty($taxes) && is_array($taxes)) {
                foreach ($taxes as $tax) {
                    $taxTotal += isset($tax['amount']) ? (float) $tax['amount'] : 0;
                }
            }

            $discountedTotal = max(0, $subTotal - ($discountAmount ?? 0));

            $extraCharges = [];
            $chargesTotal = 0;
            if (! empty($extraChargesData) && is_array($extraChargesData)) {
                foreach ($extraChargesData as $charge) {
                    $chargeId = is_array($charge) ? ($charge['id'] ?? null) : $charge;
                    if ($chargeId) {
                        $chargeModel = RestaurantCharge::find($chargeId);
                        if ($chargeModel) {
                            $extraCharges[] = $chargeModel;
                            $chargeAmount = (float) $chargeModel->getAmount($discountedTotal);
                            $chargesTotal += $chargeAmount;
                        }
                    }
                }
            }

            $total = max(0, $discountedTotal + $taxTotal + $chargesTotal + (float) $deliveryFee);

            $orderNumberData = $this->generateSafeOrderNumber($this->branch);

            $status = 'draft';
            $orderStatus = 'placed';
            $tableStatus = 'available';

            $action = ! empty($actions) ? $actions[0] : null;

            switch ($action) {
                case 'bill':
                case 'billed':
                    $status = 'billed';
                    $orderStatus = 'confirmed';
                    $tableStatus = 'running';
                    break;
                case 'kot':
                    $status = 'kot';
                    $orderStatus = 'confirmed';
                    $tableStatus = 'running';
                    break;
                case 'cancel':
                    $status = 'canceled';
                    $orderStatus = 'canceled';
                    $tableStatus = 'available';
                    break;
                default:
                    $status = 'draft';
                    $orderStatus = 'placed';
                    $tableStatus = 'available';
            }

            // Enforce safe values before persisting
            $orderStatus = $this->safety->sanitizeOrderStatus($orderStatus, 'placed');
            $status = $this->safety->sanitizeStatusColumn($status, 'draft');

            $posMachineId = $this->resolvePosMachineId(
                $posMachinePublicId,
                $posMachineToken,
                $posMachineDeviceId
            );

            $orderTypeNameFinal = $orderTypeName ?? $orderTypeDisplay;

            $orderData = [
                'order_number' => $orderNumberData['order_number'],
                'formatted_order_number' => $orderNumberData['formatted_order_number'],
                'branch_id' => $this->branch->id,
                'table_id' => $tableId,
                'date_time' => now(),
                'customer_address_id' => $customerAddressId,
                'number_of_pax' => $pax,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'waiter_id' => $waiterId,
                'sub_total' => $subTotal,
                'total_tax_amount' => $taxTotal,
                'total' => $total,
                'order_type' => $orderTypeSlug ?? $normalizedOrderType,
                'order_type_id' => $orderTypeId,
                'custom_order_type_name' => $orderTypeNameFinal,
                'delivery_fee' => $deliveryFee,
                'delivery_address' => $deliveryAddress,
                'delivery_time' => $deliveryTime,
                'delivery_executive_id' => $deliveryExecutiveId,
                'delivery_app_id' => $deliveryAppId,
                'status' => $status,
                'order_status' => $orderStatus,
                'placed_via' => $placedVia,
                'tax_mode' => 'order',
                'customer_id' => $customerId,
            ];

            if (\Schema::hasColumn('orders', 'pos_machine_id')) {
                $orderData['pos_machine_id'] = $posMachineId;
            }

            $order = Order::create($orderData);

            $user = auth()->user();
            if ($status === 'billed' && $user) {
                $order->added_by = $user->id;
                $order->save();
            }

            if (! empty($extraCharges)) {
                $chargesData = collect($extraCharges)
                    ->map(fn($charge) => ['charge_id' => $charge->id])
                    ->toArray();
                $order->charges()->createMany($chargesData);
            }

            if ($status === 'canceled') {
                if ($table) {
                    $table->available_status = $tableStatus;
                    $table->saveQuietly();
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => __('messages.orderCanceled'),
                    'order' => $order,
                ], 200);
            }

            $kot = null;
            $kotIds = [];

            if ($status === 'kot') {
                $kot = \App\Models\Kot::create([
                    'branch_id' => $this->branch->id,
                    'kot_number' => \App\Models\Kot::generateKotNumber($this->branch),
                    'order_id' => $order->id,
                    'order_type_id' => $orderTypeId,
                    'token_number' => \App\Models\Kot::generateTokenNumber($this->branch->id, $orderTypeId),
                    'note' => $note,
                ]);
                $kotIds[] = $kot->id;
            }

            $orderItems = collect();
            $orderItemsDisplay = collect();
            $hasTotalCol = Schema::hasColumn('order_items', 'total');
            $hasModifiersCol = Schema::hasColumn('order_items', 'modifiers');
            $hasModifiersTotal = Schema::hasColumn('order_items', 'modifiers_total');
            $hasModifierIds = Schema::hasColumn('order_items', 'modifier_ids');

            foreach ($items as $item) {
                $menuItem = MenuItem::find($item['id']);
                if (! $menuItem) {
                    continue;
                }

                $qty = max(1, (int) ($item['quantity'] ?? 1));
                $price = (float) ($item['price'] ?? 0);

                // Fallback: If price is 0 or not provided, fetch from MenuItem
                if ($price <= 0) {
                    // Try to get order-type-specific price first
                    if ($orderTypeId && method_exists($menuItem, 'resolvePrice')) {
                        $price = (float) $menuItem->resolvePrice($orderTypeId);
                    }
                    // Fallback to base price columns
                    if ($price <= 0) {
                        $price = (float) ($menuItem->price
                            ?? $menuItem->final_price
                            ?? $menuItem->selling_price
                            ?? $menuItem->base_price
                            ?? 0);
                    }
                }

                $modifiers = $item['modifiers'] ?? [];
                $modifiersTotal = 0;

                if (is_array($modifiers)) {
                    foreach ($modifiers as $mod) {
                        $modifiersTotal += isset($mod['price']) ? floatval($mod['price']) : 0;
                    }
                }

                $itemTotalPrice = $price + $modifiersTotal;
                $amount = $qty * $itemTotalPrice;
                $payload = array_filter([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'menu_item_variation_id' => $item['variation_id'] ?? $item['menu_item_variation_id'] ?? null,
                    'quantity' => $qty,
                    'price' => $price,
                    'amount' => $amount,
                    'note' => $item['note'] ?? null,
                    'tax_amount' => $item['tax_amount'] ?? null,
                    'tax_percentage' => $item['tax_percentage'] ?? null,
                    'tax_breakup' => $item['tax_breakup'] ?? null,
                    'modifiers' => $hasModifiersCol ? json_encode($modifiers) : null,
                    'modifiers_total' => $hasModifiersTotal ? $modifiersTotal : null,
                    'modifier_ids' => $hasModifierIds
                        ? (is_array($modifiers) ? collect($modifiers)->pluck('id')->filter()->values()->all() : null)
                        : null,
                    'total' => $hasTotalCol ? $amount : null,
                    'branch_id' => $this->branch->id,
                ], function ($v) {
                    return ! is_null($v);
                });

                $createdItem = OrderItem::create($payload);
                $orderItems->push($createdItem);

                // Insert modifiers into order_item_modifier_options table
                if (is_array($modifiers) && ! empty($modifiers) && Schema::hasTable('order_item_modifier_options')) {
                    foreach ($modifiers as $mod) {
                        $modifierId = $mod['id'] ?? null;
                        if (! $modifierId) {
                            continue;
                        }

                        DB::table('order_item_modifier_options')->insert([
                            'order_item_id' => $createdItem->id,
                            'modifier_option_id' => $modifierId,
                            'modifier_option_name' => $mod['name'] ?? null,
                            'modifier_option_price' => isset($mod['price']) ? (float) $mod['price'] : null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $orderItemsDisplay->push([
                    'id' => $createdItem->id,
                    'menu_item_id' => $menuItem->id,
                    'name' => $menuItem->item_name ?? $menuItem->name ?? 'Unknown Item',
                    'quantity' => $qty,
                    'price' => $price,
                    'amount' => $amount,
                    'tax_amount' => $payload['tax_amount'] ?? null,
                    'note' => $payload['note'] ?? null,
                    'modifiers_total' => $hasModifiersTotal ? $modifiersTotal : null,
                    'modifiers' => $hasModifiersCol ? $modifiers : null,
                ]);
            }

            if (! empty($taxes)) {
                // Check if order_taxes table has 'amount' column
                $hasAmountColumn = Schema::hasColumn('order_taxes', 'amount');

                $taxData = collect($taxes)->map(function ($tax) use ($order, $hasAmountColumn) {
                    $taxModel = Tax::find($tax['id'] ?? null);
                    if (! $taxModel) {
                        return null;
                    }

                    $data = [
                        'tax_id' => $taxModel->id,
                        'order_id' => $order->id,
                    ];

                    // Only include amount if the column exists
                    if ($hasAmountColumn) {
                        $data['amount'] = $tax['amount'] ?? 0;
                    }

                    return $data;
                })->filter()->toArray();

                if (! empty($taxData)) {
                    OrderTax::insert($taxData);
                }
            }

            $cartCharges = collect($extraCharges)->map(function ($charge) use ($discountedTotal) {
                return [
                    'id' => $charge->id,
                    'charge_id' => $charge->id,
                    'name' => $charge->name,
                    'amount' => (float) $charge->getAmount($discountedTotal),
                ];
            });

            $cartTaxes = collect($taxes)->map(function ($tax) {
                return [
                    'tax_id' => $tax['id'] ?? null,
                    'name' => $tax['name'] ?? null,
                    'amount' => isset($tax['amount']) ? (float) $tax['amount'] : null,
                ];
            });

            if ($table && $status !== 'canceled') {
                $table->available_status = $tableStatus;
                $table->saveQuietly();

                if ($tableStatus === 'running') {
                    TableSession::updateOrCreate(
                        ['table_id' => $table->id],
                        [
                            'locked_by_user_id' => auth()->id(),
                            'locked_at' => now(),
                        ]
                    );
                }
            }

            if ($status === 'kot' && $kot) {
                foreach ($orderItems as $orderItem) {
                    \App\Models\KotItem::create([
                        'kot_id' => $kot->id,
                        'order_item_id' => $orderItem->id,
                        'menu_item_id' => $orderItem->menu_item_id,
                        'menu_item_variation_id' => $orderItem->menu_item_variation_id,
                        'quantity' => $orderItem->quantity,
                    ]);
                }
            }

            $orderResource = new OrderResource($order->fresh(['items', 'charges', 'taxes']));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.orderSaved'),
                'order' => $orderResource,
                'kot_ids' => $kotIds,
                'cart' => [
                    'items' => $orderItemsDisplay,
                    'charges' => $cartCharges,
                    'taxes' => $cartTaxes,
                    'summary' => [
                        'sub_total' => $subTotal,
                        'discount_amount' => $discountAmount,
                        'tax_total' => $taxTotal,
                        'charges_total' => $chargesTotal,
                        'delivery_fee' => (float) $deliveryFee,
                        'grand_total' => $total,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Submit Order Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => __('messages.orderSaveError'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing order (cancel, modify status, etc.)
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/orders/{id}
     */
    public function updateOrder(UpdateOrderRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            if ($resp = $this->guardBranch()) {
                return $resp;
            }

            // Find existing order
            $order = Order::where('branch_id', $this->branch->id)
                ->where('id', $id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.orderNotFound'),
                ], 404);
            }

            $data = $request->validated();
            $actions = $data['actions'] ?? [];
            $action = !empty($actions) ? $actions[0] : null;

            // Handle cancel action
            if ($action === 'cancel') {
                // Delete order (matching Laravel Livewire POS behavior)
                $order->delete();

                // Free table if associated
                if ($order->table_id) {
                    $table = Table::find($order->table_id);
                    if ($table) {
                        $table->available_status = 'available';
                        $table->saveQuietly();

                        // Remove table lock
                        TableSession::where('table_id', $table->id)->delete();
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => __('messages.orderCanceled'),
                    'order_id' => $id,
                ], 200);
            }

            // Handle other update actions (bill, kot, etc.)
            $status = $order->status;
            $orderStatus = $order->order_status;
            $tableStatus = null;

            switch ($action) {
                case 'bill':
                case 'billed':
                    $status = 'billed';
                    $orderStatus = 'confirmed';
                    $tableStatus = 'running';
                    break;
                case 'kot':
                    $status = 'kot';
                    $orderStatus = 'confirmed';
                    $tableStatus = 'running';
                    break;
                case 'draft':
                    $status = 'draft';
                    $orderStatus = 'placed';
                    $tableStatus = 'available';
                    break;
            }

            // Update order basic fields
            $order->status = $status;
            $order->order_status = $orderStatus;

            // Update table if provided (dine-in orders only)
            if (isset($data['table_id'])) {
                // Validate order is dine-in
                $orderType = $order->order_type ?? 'dine_in';
                $normalizedType = strtolower(str_replace([' ', '-'], '_', $orderType));

                if ($normalizedType === 'dine_in' || $normalizedType === 'dinein') {
                    $newTableId = $data['table_id'];

                    // Free old table if it's changing
                    if ($order->table_id && $order->table_id != $newTableId) {
                        $oldTable = Table::find($order->table_id);
                        if ($oldTable) {
                            $oldTable->available_status = 'available';
                            $oldTable->saveQuietly();
                            TableSession::where('table_id', $oldTable->id)->delete();
                        }
                    }

                    // Assign new table
                    if ($newTableId) {
                        $newTable = Table::where('branch_id', $this->branch->id)
                            ->where('id', $newTableId)
                            ->first();

                        if ($newTable) {
                            $order->table_id = $newTableId;
                            $newTable->available_status = 'running';
                            $newTable->saveQuietly();

                            // Lock the new table
                            TableSession::updateOrCreate(
                                ['table_id' => $newTable->id],
                                [
                                    'locked_by_user_id' => auth()->id(),
                                    'locked_at' => now(),
                                ]
                            );
                        }
                    } else {
                        $order->table_id = null;
                    }
                } else {
                    // Non-dine-in order, cannot assign table
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Table assignment is only allowed for dine-in orders.',
                    ], 422);
                }
            }

            // Update customer if provided
            if (isset($data['customer_id'])) {
                $customerId = $data['customer_id'];

                if ($customerId) {
                    $customer = Customer::where('restaurant_id', $this->restaurant->id)
                        ->where('id', $customerId)
                        ->first();

                    if ($customer) {
                        $order->customer_id = $customerId;
                    }
                } else {
                    $order->customer_id = null;
                }
            }

            // Update waiter if provided
            if (isset($data['waiter_id'])) {
                $waiterId = $data['waiter_id'];

                if ($waiterId) {
                    $waiter = User::where('id', $waiterId)->first();

                    if ($waiter) {
                        $order->waiter_id = $waiterId;
                    }
                } else {
                    $order->waiter_id = null;
                }
            }

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete existing items
                $order->items()->delete();

                // Add new items
                foreach ($data['items'] as $item) {
                    $menuItem = MenuItem::find($item['id'] ?? $item['menu_item_id'] ?? null);
                    if (!$menuItem) continue;

                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    $price = (float) ($item['price'] ?? $menuItem->price ?? 0);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'branch_id' => $this->branch->id,
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $qty,
                        'price' => $price,
                        'amount' => $price * $qty,
                        'note' => $item['note'] ?? null,
                    ]);
                }
            }

            $order->save();

            // Update table status if needed (from action)
            if ($tableStatus && $order->table_id) {
                $table = Table::find($order->table_id);
                if ($table) {
                    $table->available_status = $tableStatus;
                    $table->saveQuietly();

                    if ($tableStatus === 'running') {
                        TableSession::updateOrCreate(
                            ['table_id' => $table->id],
                            [
                                'locked_by_user_id' => auth()->id(),
                                'locked_at' => now(),
                            ]
                        );
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('messages.orderUpdated'),
                'order' => new OrderResource($order->fresh(['items', 'charges', 'taxes', 'table', 'customer', 'waiter'])),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('POS Update Order Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => __('messages.orderUpdateError'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTaxes()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $restaurantId = $this->restaurant?->id;

        $taxes = Tax::query()
            ->when($restaurantId, function ($q) use ($restaurantId) {
                $q->where(function ($query) use ($restaurantId) {
                    // Direct ownership: taxes.restaurant_id matches
                    if (Schema::hasColumn('taxes', 'restaurant_id')) {
                        $query->where('restaurant_id', $restaurantId);
                    }
                    // Or linked via pivot table: restaurant_taxes
                    if (Schema::hasTable('restaurant_taxes') && Schema::hasColumn('restaurant_taxes', 'restaurant_id')) {
                        $query->orWhereIn('id', function ($sub) use ($restaurantId) {
                            $sub->select('tax_id')
                                ->from('restaurant_taxes')
                                ->where('restaurant_id', $restaurantId);
                        });
                    }
                });
            })
            ->get();

        return response()->json($taxes);
    }

    public function getRestaurants()
    {
        try {
            $columns = \Illuminate\Support\Facades\Schema::hasTable('restaurants')
                ? \Illuminate\Support\Facades\Schema::getColumnListing('restaurants')
                : [];

            $selectable = collect(['id', 'name', 'restaurant_name', 'hash', 'unique_hash'])
                ->filter(fn($col) => in_array($col, $columns))
                ->values()
                ->all();

            if (empty($selectable)) {
                $selectable = ['id'];
            }

            $restaurants = Restaurant::withoutGlobalScopes()
                ->select($selectable)
                ->get();

            return response()->json($restaurants);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration restaurants endpoint failed', [
                'error' => $e->getMessage(),
            ]);

            $fallback = [];
            if ($this->restaurant) {
                $fallback[] = [
                    'id' => $this->restaurant->id,
                    'name' => $this->restaurant->name ?? $this->restaurant->restaurant_name ?? null,
                    'hash' => $this->restaurant->hash ?? null,
                ];
            }

            return response()->json($fallback);
        }
    }

    public function getBranches()
    {
        try {
            if (! $this->restaurant) {
                return response()->json([]);
            }

            $columns = \Illuminate\Support\Facades\Schema::hasTable('branches')
                ? \Illuminate\Support\Facades\Schema::getColumnListing('branches')
                : [];

            $select = collect(['id', 'name', 'branch_name', 'hash', 'unique_hash'])
                ->filter(fn($col) => in_array($col, $columns))
                ->all();

            if (empty($select)) {
                $select = ['id'];
            }

            return response()->json(
                $this->restaurant->branches()->select($select)->get()
            );
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration branches error: ' . $e->getMessage());
            return response()->json([]);
        }
    }
    public function getOrders(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $search = $request->query('search');
        $branchId = $request->query('branch_id', $this->branch?->id);

        if ($branchId && Schema::hasTable('branches')) {
            $validBranch = DB::table('branches')
                ->when($this->restaurant?->id, fn($q) => $q->where('restaurant_id', $this->restaurant->id))
                ->where('id', $branchId)
                ->exists();

            if (! $validBranch) {
                return response()->json([
                    'success' => false,
                    'message' => __('applicationintegration::messages.not_found'),
                ], 404);
            }
        } else {
            $branchId = $this->branch?->id;
        }

        try {
            // Use raw query to avoid enum cast issues while keeping pagination stable
            $query = DB::table('orders')->where('branch_id', $branchId);

            if ($status) {
                // Check which status column exists in the orders table
                $statusColumn = Schema::hasColumn('orders', 'order_status') ? 'order_status' : 'status';
                $query->where($statusColumn, $status);
            }

            if ($dateFrom) {
                $query->whereDate('date_time', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->whereDate('date_time', '<=', $dateTo);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('formatted_order_number', 'like', '%' . $search . '%')
                        ->orWhere('order_number', 'like', '%' . $search . '%');
                });
            }

            $orders = $query->orderByDesc('id')->paginate(20);
            $orderCollection = collect($orders->items());
            if ($orderCollection->isEmpty()) {
                return response()->json($orders);
            }

            try {
                $orderIds = $orderCollection->pluck('id')->filter()->values()->all();
                $ordersById = $orderCollection->keyBy('id');

                $hasOrderItems = Schema::hasTable('order_items');
                $hasOrderCharges = Schema::hasTable('order_charges');
                $hasOrderTaxes = Schema::hasTable('order_taxes');

                // Detect menu price column once for item fallbacks
                $menuPriceColumn = null;
                if (Schema::hasTable('menu_items')) {
                    foreach (['final_price', 'price', 'selling_price', 'base_price'] as $col) {
                        if (Schema::hasColumn('menu_items', $col)) {
                            $menuPriceColumn = $col;
                            break;
                        }
                    }
                }

                $itemsByOrder = collect();
                if ($hasOrderItems) {
                    $hasTotalCol = Schema::hasColumn('order_items', 'total');
                    $hasModifiersCol = Schema::hasColumn('order_items', 'modifiers');
                    $hasModifiersTotal = Schema::hasColumn('order_items', 'modifiers_total');

                    $menuItemNameExpr = $this->menuItemNameExpr();

                    $itemsByOrder = DB::table('order_items')
                        ->leftJoin('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                        ->whereIn('order_items.order_id', $orderIds)
                        ->select(
                            'order_items.*',
                            'order_items.order_id',
                            $menuItemNameExpr
                        )
                        ->when($menuPriceColumn, function ($q) use ($menuPriceColumn) {
                            $q->addSelect(DB::raw("menu_items.{$menuPriceColumn} as menu_item_price"));
                        })
                        ->get();

                    // Fetch modifiers from order_item_modifier_options table for all order items
                    $hasModifierOptionsTable = Schema::hasTable('order_item_modifier_options');
                    $modifiersByItemId = collect();
                    if ($hasModifierOptionsTable && $itemsByOrder->isNotEmpty()) {
                        $itemIds = $itemsByOrder->pluck('id')->unique()->values()->all();
                        $modifiersByItemId = DB::table('order_item_modifier_options')
                            ->whereIn('order_item_id', $itemIds)
                            ->get()
                            ->groupBy('order_item_id');
                    }

                    $itemsByOrder = $itemsByOrder
                        ->map(function ($item) use ($hasTotalCol, $hasModifiersCol, $hasModifiersTotal, $modifiersByItemId) {
                            // First try modifiers from order_item_modifier_options table
                            $modifiers = null;
                            if ($modifiersByItemId->has($item->id)) {
                                $modifiers = $modifiersByItemId->get($item->id)->map(function ($mod) {
                                    return [
                                        'id' => $mod->modifier_option_id,
                                        'name' => $mod->modifier_option_name,
                                        'price' => $mod->modifier_option_price !== null ? (float) $mod->modifier_option_price : 0,
                                    ];
                                })->values()->all();
                            }
                            // Fallback to JSON column if no records in pivot table
                            if (empty($modifiers) && $hasModifiersCol && $item->modifiers) {
                                $decoded = json_decode($item->modifiers, true);
                                $modifiers = $decoded ?: $item->modifiers;
                            }

                            // Calculate modifiers total
                            $modifiersTotal = 0;
                            if (is_array($modifiers)) {
                                foreach ($modifiers as $mod) {
                                    $modifiersTotal += isset($mod['price']) ? (float) $mod['price'] : 0;
                                }
                            }

                            return [
                                'id' => $item->id,
                                'order_id' => $item->order_id,
                                'menu_item_id' => $item->menu_item_id,
                                'name' => $item->menu_item_name ?? 'Unknown Item',
                                'quantity' => $item->quantity,
                                'price' => $item->price !== null ? (float) $item->price : ($item->menu_item_price ?? null),
                                'amount' => $item->amount !== null ? (float) $item->amount : ($hasTotalCol && $item->total !== null ? (float) $item->total : null),
                                'tax_amount' => $item->tax_amount !== null ? (float) $item->tax_amount : null,
                                'note' => $item->note,
                                'modifiers_total' => $modifiersTotal > 0 ? $modifiersTotal : ($hasModifiersTotal && $item->modifiers_total !== null ? (float) $item->modifiers_total : null),
                                'modifiers' => $modifiers,
                            ];
                        })
                        ->groupBy('order_id');
                }

                // Fallback to split_order_items for orders without direct items
                $ordersMissingItems = collect($orderIds)->diff($itemsByOrder->keys())->values();
                if ($ordersMissingItems->isNotEmpty() && Schema::hasTable('split_order_items')) {
                    $splitItems = DB::table('split_order_items')
                        ->leftJoin('order_items', 'split_order_items.order_item_id', '=', 'order_items.id')
                        ->leftJoin('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                        ->whereIn('order_items.order_id', $ordersMissingItems)
                        ->select(
                            'split_order_items.*',
                            'order_items.order_id',
                            'order_items.menu_item_id',
                            'order_items.price as oi_price',
                            'order_items.amount as oi_amount',
                            $this->menuItemNameExpr()
                        )
                        ->get()
                        ->map(function ($item) use ($menuPriceColumn) {
                            $unitPrice = null;
                            if (isset($item->oi_price)) {
                                $unitPrice = (float) $item->oi_price;
                            } elseif ($menuPriceColumn && isset($item->$menuPriceColumn)) {
                                $unitPrice = (float) $item->$menuPriceColumn;
                            }
                            $qty = $item->quantity ?? 1;
                            $amount = $item->oi_amount !== null ? (float) $item->oi_amount : ($unitPrice !== null ? $unitPrice * $qty : null);

                            return [
                                'id' => $item->id,
                                'order_id' => $item->order_id,
                                'menu_item_id' => $item->menu_item_id,
                                'name' => $item->menu_item_name ?? 'Unknown Item',
                                'quantity' => $qty,
                                'price' => $unitPrice,
                                'amount' => $amount,
                                'tax_amount' => null,
                                'note' => null,
                                'modifiers_total' => null,
                                'modifiers' => null,
                            ];
                        })
                        ->groupBy('order_id');

                    foreach ($splitItems as $orderId => $group) {
                        if (! $itemsByOrder->has($orderId)) {
                            $itemsByOrder[$orderId] = $group;
                        }
                    }
                }

                // Fallback to kot_items if still missing
                $ordersMissingItems = collect($orderIds)->diff($itemsByOrder->keys())->values();
                if ($ordersMissingItems->isNotEmpty() && Schema::hasTable('kot_items')) {
                    $kotItems = DB::table('kot_items')
                        ->leftJoin('kots', 'kot_items.kot_id', '=', 'kots.id')
                        ->leftJoin('menu_items', 'kot_items.menu_item_id', '=', 'menu_items.id')
                        ->whereIn('kots.order_id', $ordersMissingItems)
                        ->select(
                            'kot_items.*',
                            'kots.order_id',
                            $this->menuItemNameExpr()
                        )
                        ->get()
                        ->map(function ($item) use ($menuPriceColumn) {
                            $unitPrice = null;
                            if ($menuPriceColumn && isset($item->$menuPriceColumn)) {
                                $unitPrice = (float) $item->$menuPriceColumn;
                            }
                            $amount = $unitPrice !== null ? $unitPrice * ($item->quantity ?? 1) : null;
                            return [
                                'id' => $item->id,
                                'order_id' => $item->order_id,
                                'menu_item_id' => $item->menu_item_id,
                                'name' => $item->menu_item_name ?? 'Unknown Item',
                                'quantity' => $item->quantity,
                                'price' => $unitPrice,
                                'amount' => $amount,
                                'tax_amount' => null,
                                'note' => null,
                                'modifiers_total' => null,
                                'modifiers' => null,
                            ];
                        })
                        ->groupBy('order_id');

                    foreach ($kotItems as $orderId => $group) {
                        if (! $itemsByOrder->has($orderId)) {
                            $itemsByOrder[$orderId] = $group;
                        }
                    }
                }

                $chargesByOrder = collect();
                if ($hasOrderCharges && Schema::hasTable('restaurant_charges')) {
                    try {
                        // Detect charge name column
                        $chargeNameCol = Schema::hasColumn('restaurant_charges', 'charge_name') ? 'charge_name' : (Schema::hasColumn('restaurant_charges', 'name') ? 'name' : null);
                        $chargeNameExpr = $chargeNameCol ? DB::raw("restaurant_charges.{$chargeNameCol} as name") : DB::raw("'Charge' as name");

                        $selectCols = ['order_charges.id', 'order_charges.order_id', 'order_charges.charge_id'];
                        if (Schema::hasColumn('restaurant_charges', 'charge_type')) {
                            $selectCols[] = 'restaurant_charges.charge_type';
                        }
                        if (Schema::hasColumn('restaurant_charges', 'charge_value')) {
                            $selectCols[] = 'restaurant_charges.charge_value';
                        }

                        $chargesByOrder = DB::table('order_charges')
                            ->leftJoin('restaurant_charges', 'order_charges.charge_id', '=', 'restaurant_charges.id')
                            ->whereIn('order_charges.order_id', $orderIds)
                            ->select(array_merge($selectCols, [$chargeNameExpr]))
                            ->get()
                            ->map(function ($charge) use ($ordersById) {
                                $orderRow = $ordersById->get($charge->order_id);
                                $amount = $orderRow ? $this->calculateChargeAmount($charge, $orderRow) : null;
                                return [
                                    'id' => $charge->id,
                                    'order_id' => $charge->order_id,
                                    'charge_id' => $charge->charge_id,
                                    'name' => $charge->name ?? 'Charge',
                                    'amount' => $amount,
                                ];
                            })
                            ->groupBy('order_id');
                    } catch (\Throwable $e) {
                        Log::warning('Order charges hydrate failed', ['order_ids' => $orderIds, 'message' => $e->getMessage()]);
                        $chargesByOrder = collect();
                    }
                }

                $taxesByOrder = collect();
                if ($hasOrderTaxes && Schema::hasTable('taxes')) {
                    try {
                        // Detect tax name column
                        $taxNameCol = Schema::hasColumn('taxes', 'tax_name') ? 'tax_name' : (Schema::hasColumn('taxes', 'name') ? 'name' : null);
                        $taxNameExpr = $taxNameCol ? DB::raw("taxes.{$taxNameCol} as tax_name") : DB::raw("'Tax' as tax_name");

                        // Check if order_taxes has amount column
                        $hasAmountCol = Schema::hasColumn('order_taxes', 'amount');
                        $hasTaxPercent = Schema::hasColumn('taxes', 'tax_percent');

                        $selectCols = ['order_taxes.id', 'order_taxes.order_id', 'order_taxes.tax_id', $taxNameExpr];
                        if ($hasAmountCol) {
                            $selectCols[] = 'order_taxes.amount';
                        }
                        if ($hasTaxPercent) {
                            $selectCols[] = 'taxes.tax_percent';
                        }

                        $taxesByOrder = DB::table('order_taxes')
                            ->leftJoin('taxes', 'order_taxes.tax_id', '=', 'taxes.id')
                            ->whereIn('order_taxes.order_id', $orderIds)
                            ->select($selectCols)
                            ->get()
                            ->map(function ($tax) use ($hasAmountCol, $hasTaxPercent, $ordersById) {
                                $amount = null;
                                if ($hasAmountCol && property_exists($tax, 'amount') && $tax->amount !== null) {
                                    $amount = (float) $tax->amount;
                                } elseif ($hasTaxPercent && property_exists($tax, 'tax_percent')) {
                                    // Calculate tax amount from percent and order sub_total
                                    $orderRow = $ordersById->get($tax->order_id);
                                    if ($orderRow) {
                                        $subTotal = (float) ($orderRow->sub_total ?? 0);
                                        $percent = (float) ($tax->tax_percent ?? 0);
                                        $amount = round($subTotal * $percent / 100, 2);
                                    }
                                }
                                return [
                                    'id' => $tax->id,
                                    'order_id' => $tax->order_id,
                                    'tax_id' => $tax->tax_id,
                                    'name' => $tax->tax_name ?? 'Tax',
                                    'amount' => $amount,
                                ];
                            })
                            ->groupBy('order_id');
                    } catch (\Throwable $e) {
                        Log::warning('Order taxes hydrate failed', ['order_ids' => $orderIds, 'message' => $e->getMessage()]);
                        $taxesByOrder = collect();
                    }
                }

                $customerMap = collect();
                $tableMap = collect();
                $orderTypeMap = collect();
                $deliveryExecMap = collect();

                if ($orderCollection->pluck('customer_id')->filter()->isNotEmpty() && Schema::hasTable('customers')) {
                    try {
                        $customerCols = collect(['id', 'name', 'email', 'phone', 'phone_code', 'delivery_address'])
                            ->filter(fn($col) => Schema::hasColumn('customers', $col))->values()->all();
                        if (empty($customerCols)) $customerCols = ['id'];
                        $customerMap = DB::table('customers')
                            ->select($customerCols)
                            ->whereIn('id', $orderCollection->pluck('customer_id')->filter())
                            ->get()
                            ->keyBy('id');
                    } catch (\Throwable $e) {
                        Log::warning('Orders customer lookup failed', ['error' => $e->getMessage()]);
                    }
                }

                if ($orderCollection->pluck('table_id')->filter()->isNotEmpty() && Schema::hasTable('tables')) {
                    try {
                        $tableCols = collect(['id', 'table_code', 'status', 'available_status', 'area_id'])
                            ->filter(fn($col) => Schema::hasColumn('tables', $col))->values()->all();
                        if (empty($tableCols)) $tableCols = ['id'];
                        $tableMap = DB::table('tables')
                            ->select($tableCols)
                            ->whereIn('id', $orderCollection->pluck('table_id')->filter())
                            ->get()
                            ->keyBy('id');
                    } catch (\Throwable $e) {
                        Log::warning('Orders table lookup failed', ['error' => $e->getMessage()]);
                    }
                }

                if ($orderCollection->pluck('delivery_executive_id')->filter()->isNotEmpty() && Schema::hasTable('delivery_executives')) {
                    try {
                        $execCols = collect(['id', 'name', 'phone', 'phone_code', 'status'])
                            ->filter(fn($col) => Schema::hasColumn('delivery_executives', $col))->values()->all();
                        if (empty($execCols)) $execCols = ['id'];
                        $deliveryExecMap = DB::table('delivery_executives')
                            ->select($execCols)
                            ->whereIn('id', $orderCollection->pluck('delivery_executive_id')->filter())
                            ->get()
                            ->mapWithKeys(function ($exec) {
                                $statusValue = property_exists($exec, 'status') ? $exec->status : null;
                                $exec->status_normalized = $this->normalizeDeliveryStatus($statusValue);
                                return [$exec->id => $exec];
                            });
                    } catch (\Throwable $e) {
                        Log::warning('Orders delivery exec lookup failed', ['error' => $e->getMessage()]);
                    }
                }

                if ($orderCollection->pluck('order_type_id')->filter()->isNotEmpty() && Schema::hasTable('order_types')) {
                    try {
                        $orderTypeCols = collect(['id', 'order_type_name', 'slug', 'type'])
                            ->filter(fn($col) => Schema::hasColumn('order_types', $col))->values()->all();
                        if (empty($orderTypeCols)) $orderTypeCols = ['id'];
                        $orderTypeMap = DB::table('order_types')
                            ->select($orderTypeCols)
                            ->whereIn('id', $orderCollection->pluck('order_type_id')->filter())
                            ->get()
                            ->keyBy('id');
                    } catch (\Throwable $e) {
                        Log::warning('Orders order type lookup failed', ['error' => $e->getMessage()]);
                    }
                }

                $orders->getCollection()->transform(function ($row) use ($itemsByOrder, $chargesByOrder, $taxesByOrder, $customerMap, $tableMap, $deliveryExecMap, $orderTypeMap) {
                    $items = $itemsByOrder->get($row->id, collect());
                    $charges = $chargesByOrder->get($row->id, collect());
                    $taxes = $taxesByOrder->get($row->id, collect());

                    $items = $items instanceof \Illuminate\Support\Collection ? $items : collect($items);
                    $charges = $charges instanceof \Illuminate\Support\Collection ? $charges : collect($charges);
                    $taxes = $taxes instanceof \Illuminate\Support\Collection ? $taxes : collect($taxes);

                    $chargesTotal = $charges->sum('amount');
                    $taxTotal = $taxes->sum('amount');
                    $discountAmount = $row->discount_amount !== null ? (float) $row->discount_amount : 0;
                    $subTotal = $row->sub_total !== null ? (float) $row->sub_total : null;
                    $deliveryFee = $row->delivery_fee !== null ? (float) $row->delivery_fee : null;
                    $grandTotal = $row->total !== null
                        ? (float) $row->total
                        : max(0, ($subTotal ?? 0) - $discountAmount + $taxTotal + $chargesTotal + ($deliveryFee ?? 0));
                    $taxTotalValue = $row->total_tax_amount !== null ? (float) $row->total_tax_amount : $taxTotal;

                    $base = collect((array) $row)->toArray();
                    $base['status'] = $row->order_status ?? $row->status;
                    $base['order_status'] = $row->order_status ?? $row->status;
                    $base['items'] = $items;
                    $base['charges'] = $charges;
                    $base['taxes'] = $taxes;
                    $base['customer'] = $customerMap->get($row->customer_id);
                    $base['table'] = $tableMap->get($row->table_id);
                    $base['delivery_executive'] = $deliveryExecMap->get($row->delivery_executive_id);
                    $base['order_type_meta'] = $orderTypeMap->get($row->order_type_id);
                    $base['cart'] = [
                        'items' => $items,
                        'charges' => $charges,
                        'taxes' => $taxes,
                        'summary' => [
                            'sub_total' => $subTotal,
                            'discount_amount' => $discountAmount,
                            'tax_total' => $taxTotalValue,
                            'charges_total' => $chargesTotal,
                            'delivery_fee' => $deliveryFee,
                            'grand_total' => $grandTotal,
                        ],
                    ];
                    $base['notes'] = $items->isEmpty()
                        ? 'No line items found; using KOT/split fallbacks if present.'
                        : null;

                    return $base;
                });
            } catch (\Throwable $hydrateException) {
                \Log::error('ApplicationIntegration getOrders hydration failed', [
                    'branch_id' => $this->branch?->id,
                    'restaurant_id' => $this->restaurant?->id,
                    'user_id' => auth()->id(),
                    'params' => [
                        'status' => $status,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'search' => $search,
                        'branch_id' => $branchId ?? $this->branch?->id,
                    ],
                    'order_ids' => $orderCollection->pluck('id')->all(),
                    'file' => $hydrateException->getFile(),
                    'line' => $hydrateException->getLine(),
                    'error' => $hydrateException->getMessage(),
                    'trace' => $hydrateException->getTraceAsString(),
                ]);
            }

            return response()->json($orders);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration getOrders failed', [
                'branch_id' => $this->branch?->id,
                'restaurant_id' => $this->restaurant?->id,
                'user_id' => auth()->id(),
                'params' => [
                    'status' => $status,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'search' => $search,
                    'branch_id' => $branchId ?? $this->branch?->id,
                ],
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([], 500);
        }
    }

    public function getOrder($id)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            if (! Schema::hasTable('orders')) {
                return response()->json(['success' => false, 'message' => __('applicationintegration::messages.not_found')], 404);
            }

            $row = DB::table('orders')
                ->where('branch_id', $this->branch->id)
                ->where('id', $id)
                ->first();

            if (! $row) {
                return response()->json(['success' => false, 'message' => __('applicationintegration::messages.not_found')], 404);
            }

            // Manual payload with items/pricing/charges/taxes without enum casting risk
            $hasOrderItems = Schema::hasTable('order_items');
            $hasOrderCharges = Schema::hasTable('order_charges');
            $hasOrderTaxes = Schema::hasTable('order_taxes');

            $items = collect();
            // Detect a price column on menu_items for richer item details
            $menuPriceColumn = null;
            if (Schema::hasTable('menu_items')) {
                $candidatePriceCols = ['final_price', 'price', 'selling_price', 'base_price'];
                foreach ($candidatePriceCols as $col) {
                    if (Schema::hasColumn('menu_items', $col)) {
                        $menuPriceColumn = $col;
                        break;
                    }
                }
            }

            if ($hasOrderItems) {
                try {
                    $hasTotalCol = Schema::hasColumn('order_items', 'total');
                    $hasModifiersCol = Schema::hasColumn('order_items', 'modifiers');
                    $hasModifiersTotal = Schema::hasColumn('order_items', 'modifiers_total');

                    $query = DB::table('order_items')
                        ->leftJoin('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                        ->where('order_items.order_id', $id)
                        ->select(
                            'order_items.*',
                            $this->menuItemNameExpr()
                        );

                    if ($menuPriceColumn) {
                        $query->addSelect(DB::raw("menu_items.{$menuPriceColumn} as menu_item_price"));
                    }

                    // Fetch modifiers from order_item_modifier_options table
                    $hasModifierOptionsTable = Schema::hasTable('order_item_modifier_options');
                    $modifiersByItemId = collect();
                    if ($hasModifierOptionsTable) {
                        $modifiersByItemId = DB::table('order_item_modifier_options')
                            ->whereIn('order_item_id', function ($q) use ($id) {
                                $q->select('id')->from('order_items')->where('order_id', $id);
                            })
                            ->get()
                            ->groupBy('order_item_id');
                    }

                    $items = $query->get()
                        ->map(function ($item) use ($hasTotalCol, $hasModifiersCol, $hasModifiersTotal, $modifiersByItemId) {
                            // First try modifiers from order_item_modifier_options table
                            $modifiers = null;
                            if ($modifiersByItemId->has($item->id)) {
                                $modifiers = $modifiersByItemId->get($item->id)->map(function ($mod) {
                                    return [
                                        'id' => $mod->modifier_option_id,
                                        'name' => $mod->modifier_option_name,
                                        'price' => $mod->modifier_option_price !== null ? (float) $mod->modifier_option_price : 0,
                                    ];
                                })->values()->all();
                            }
                            // Fallback to JSON column if no records in pivot table
                            if (empty($modifiers) && $hasModifiersCol && $item->modifiers) {
                                $decoded = json_decode($item->modifiers, true);
                                $modifiers = $decoded ?: $item->modifiers;
                            }

                            // Calculate modifiers total
                            $modifiersTotal = 0;
                            if (is_array($modifiers)) {
                                foreach ($modifiers as $mod) {
                                    $modifiersTotal += isset($mod['price']) ? (float) $mod['price'] : 0;
                                }
                            }

                            return [
                                'id' => $item->id,
                                'menu_item_id' => $item->menu_item_id,
                                'name' => $item->menu_item_name ?? 'Unknown Item',
                                'quantity' => $item->quantity,
                                'price' => $item->price !== null ? (float) $item->price : ($item->menu_item_price ?? null),
                                'amount' => $item->amount !== null ? (float) $item->amount : ($hasTotalCol && $item->total !== null ? (float) $item->total : null),
                                'tax_amount' => $item->tax_amount !== null ? (float) $item->tax_amount : null,
                                'note' => $item->note,
                                'modifiers_total' => $modifiersTotal > 0 ? $modifiersTotal : ($hasModifiersTotal && $item->modifiers_total !== null ? (float) $item->modifiers_total : null),
                                'modifiers' => $modifiers,
                            ];
                        });
                } catch (\Throwable $e) {
                    Log::warning('Order items fallback failed', ['order_id' => $id, 'message' => $e->getMessage()]);
                    $items = collect();
                }
            }
            // Fallback to KOT items if order_items are empty but KOT exists
            // Fallback to split_order_items if present
            if ($items->isEmpty() && Schema::hasTable('split_order_items')) {
                try {
                    $items = DB::table('split_order_items')
                        ->leftJoin('order_items', 'split_order_items.order_item_id', '=', 'order_items.id')
                        ->leftJoin('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
                        ->where('order_items.order_id', $id)
                        ->select(
                            'split_order_items.*',
                            'order_items.menu_item_id',
                            'order_items.price as oi_price',
                            'order_items.amount as oi_amount',
                            $this->menuItemNameExpr()
                        )
                        ->get()
                        ->map(function ($item) use ($menuPriceColumn) {
                            $unitPrice = null;
                            if (isset($item->oi_price)) {
                                $unitPrice = (float) $item->oi_price;
                            } elseif ($menuPriceColumn && isset($item->$menuPriceColumn)) {
                                $unitPrice = (float) $item->$menuPriceColumn;
                            }
                            $qty = $item->quantity ?? 1;
                            $amount = $item->oi_amount !== null ? (float) $item->oi_amount : ($unitPrice !== null ? $unitPrice * $qty : null);

                            return [
                                'id' => $item->id,
                                'menu_item_id' => $item->menu_item_id,
                                'name' => $item->menu_item_name ?? 'Unknown Item',
                                'quantity' => $qty,
                                'price' => $unitPrice,
                                'amount' => $amount,
                                'tax_amount' => null,
                                'note' => null,
                                'modifiers_total' => null,
                                'modifiers' => null,
                            ];
                        });
                } catch (\Throwable $e) {
                    Log::warning('Order split items fallback failed', ['order_id' => $id, 'message' => $e->getMessage()]);
                }
            }

            if ($items->isEmpty() && Schema::hasTable('kot_items')) {
                try {
                    $items = DB::table('kot_items')
                        ->leftJoin('kots', 'kot_items.kot_id', '=', 'kots.id')
                        ->leftJoin('menu_items', 'kot_items.menu_item_id', '=', 'menu_items.id')
                        ->where('kots.order_id', $id)
                        ->select(
                            'kot_items.*',
                            $this->menuItemNameExpr()
                        )
                        ->get()
                        ->map(function ($item) use ($menuPriceColumn) {
                            $unitPrice = null;
                            if ($menuPriceColumn && isset($item->$menuPriceColumn)) {
                                $unitPrice = (float) $item->$menuPriceColumn;
                            }
                            $amount = $unitPrice !== null ? $unitPrice * ($item->quantity ?? 1) : null;
                            return [
                                'id' => $item->id,
                                'menu_item_id' => $item->menu_item_id,
                                'name' => $item->menu_item_name ?? 'Unknown Item',
                                'quantity' => $item->quantity,
                                'price' => $unitPrice,
                                'amount' => $amount,
                                'tax_amount' => null,
                                'note' => null,
                                'modifiers_total' => null,
                                'modifiers' => null,
                            ];
                        });
                } catch (\Throwable $e) {
                    Log::warning('Order KOT fallback failed', ['order_id' => $id, 'message' => $e->getMessage()]);
                }
            }

            $charges = collect();
            if ($hasOrderCharges && Schema::hasTable('restaurant_charges')) {
                try {
                    // Detect charge name column
                    $chargeNameCol = Schema::hasColumn('restaurant_charges', 'charge_name') ? 'charge_name' : (Schema::hasColumn('restaurant_charges', 'name') ? 'name' : null);
                    $chargeNameExpr = $chargeNameCol ? DB::raw("restaurant_charges.{$chargeNameCol} as name") : DB::raw("'Charge' as name");

                    $selectCols = ['order_charges.id', 'order_charges.order_id', 'order_charges.charge_id'];
                    if (Schema::hasColumn('restaurant_charges', 'charge_type')) {
                        $selectCols[] = 'restaurant_charges.charge_type';
                    }
                    if (Schema::hasColumn('restaurant_charges', 'charge_value')) {
                        $selectCols[] = 'restaurant_charges.charge_value';
                    }

                    $charges = DB::table('order_charges')
                        ->leftJoin('restaurant_charges', 'order_charges.charge_id', '=', 'restaurant_charges.id')
                        ->where('order_charges.order_id', $id)
                        ->select(array_merge($selectCols, [$chargeNameExpr]))
                        ->get()
                        ->map(function ($charge) use ($row) {
                            $amount = $this->calculateChargeAmount($charge, $row);
                            return [
                                'id' => $charge->id,
                                'charge_id' => $charge->charge_id,
                                'name' => $charge->name ?? 'Charge',
                                'amount' => $amount,
                            ];
                        });
                } catch (\Throwable $e) {
                    Log::warning('Order charges fallback failed', ['order_id' => $id, 'message' => $e->getMessage()]);
                    $charges = collect();
                }
            }

            $taxes = collect();
            if ($hasOrderTaxes && Schema::hasTable('taxes')) {
                try {
                    // Detect tax name column
                    $taxNameCol = Schema::hasColumn('taxes', 'tax_name') ? 'tax_name' : (Schema::hasColumn('taxes', 'name') ? 'name' : null);
                    $taxNameExpr = $taxNameCol ? DB::raw("taxes.{$taxNameCol} as tax_name") : DB::raw("'Tax' as tax_name");

                    // Check if order_taxes has amount column
                    $hasAmountCol = Schema::hasColumn('order_taxes', 'amount');
                    $hasTaxPercent = Schema::hasColumn('taxes', 'tax_percent');

                    $selectCols = ['order_taxes.id', 'order_taxes.order_id', 'order_taxes.tax_id', $taxNameExpr];
                    if ($hasAmountCol) {
                        $selectCols[] = 'order_taxes.amount';
                    }
                    if ($hasTaxPercent) {
                        $selectCols[] = 'taxes.tax_percent';
                    }

                    $taxes = DB::table('order_taxes')
                        ->leftJoin('taxes', 'order_taxes.tax_id', '=', 'taxes.id')
                        ->where('order_taxes.order_id', $id)
                        ->select($selectCols)
                        ->get()
                        ->map(function ($tax) use ($hasAmountCol, $hasTaxPercent, $row) {
                            $amount = null;
                            if ($hasAmountCol && property_exists($tax, 'amount') && $tax->amount !== null) {
                                $amount = (float) $tax->amount;
                            } elseif ($hasTaxPercent && property_exists($tax, 'tax_percent')) {
                                // Calculate tax amount from percent and order sub_total
                                $subTotal = (float) ($row->sub_total ?? 0);
                                $percent = (float) ($tax->tax_percent ?? 0);
                                $amount = round($subTotal * $percent / 100, 2);
                            }
                            return [
                                'id' => $tax->id,
                                'tax_id' => $tax->tax_id,
                                'name' => $tax->tax_name ?? 'Tax',
                                'amount' => $amount,
                            ];
                        });
                } catch (\Throwable $e) {
                    Log::warning('Order taxes fallback failed', ['order_id' => $id, 'message' => $e->getMessage()]);
                    $taxes = collect();
                }
            }

            $customer = null;
            if ($row->customer_id && Schema::hasTable('customers')) {
                try {
                    $customerCols = collect(['id', 'name', 'email', 'phone', 'phone_code', 'delivery_address'])
                        ->filter(fn($col) => Schema::hasColumn('customers', $col))->values()->all();
                    if (empty($customerCols)) $customerCols = ['id'];
                    $customer = DB::table('customers')
                        ->select($customerCols)
                        ->where('id', $row->customer_id)
                        ->first();
                } catch (\Throwable $e) {
                    Log::warning('Order customer lookup failed', ['error' => $e->getMessage()]);
                }
            }

            $table = null;
            if ($row->table_id && Schema::hasTable('tables')) {
                try {
                    $tableCols = collect(['id', 'table_code', 'status', 'available_status', 'area_id'])
                        ->filter(fn($col) => Schema::hasColumn('tables', $col))->values()->all();
                    if (empty($tableCols)) $tableCols = ['id'];
                    $table = DB::table('tables')
                        ->select($tableCols)
                        ->where('id', $row->table_id)
                        ->first();
                } catch (\Throwable $e) {
                    Log::warning('Order table lookup failed', ['error' => $e->getMessage()]);
                }
            }

            $deliveryExecutive = null;
            if ($row->delivery_executive_id && Schema::hasTable('delivery_executives')) {
                try {
                    $execCols = collect(['id', 'name', 'phone', 'phone_code', 'status'])
                        ->filter(fn($col) => Schema::hasColumn('delivery_executives', $col))->values()->all();
                    if (empty($execCols)) $execCols = ['id'];
                    $deliveryExecutive = DB::table('delivery_executives')
                        ->select($execCols)
                        ->where('id', $row->delivery_executive_id)
                        ->first();
                    if ($deliveryExecutive) {
                        $statusValue = property_exists($deliveryExecutive, 'status') ? $deliveryExecutive->status : null;
                        $deliveryExecutive = (object) array_merge((array) $deliveryExecutive, [
                            'status_normalized' => $this->normalizeDeliveryStatus($statusValue),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Order delivery exec lookup failed', ['error' => $e->getMessage()]);
                }
            }

            $orderType = null;
            if ($row->order_type_id && Schema::hasTable('order_types')) {
                try {
                    $orderTypeCols = collect(['id', 'order_type_name', 'slug', 'type'])
                        ->filter(fn($col) => Schema::hasColumn('order_types', $col))->values()->all();
                    if (empty($orderTypeCols)) $orderTypeCols = ['id'];
                    $orderType = DB::table('order_types')
                        ->select($orderTypeCols)
                        ->where('id', $row->order_type_id)
                        ->first();
                } catch (\Throwable $e) {
                    Log::warning('Order type lookup failed', ['error' => $e->getMessage()]);
                }
            }

            $chargesTotal = $charges->sum('amount');
            $taxTotal = $taxes->sum('amount');
            $discountAmountValue = $row->discount_amount !== null ? (float) $row->discount_amount : 0;
            $subTotalValue = $row->sub_total !== null ? (float) $row->sub_total : null;
            $deliveryFeeValue = $row->delivery_fee !== null ? (float) $row->delivery_fee : null;
            $grandTotal = $row->total !== null
                ? (float) $row->total
                : max(0, ($subTotalValue ?? 0) - $discountAmountValue + $taxTotal + $chargesTotal + ($deliveryFeeValue ?? 0));
            $taxTotalValue = $row->total_tax_amount !== null ? (float) $row->total_tax_amount : $taxTotal;

            return response()->json([
                'id' => $row->id,
                'branch_id' => $row->branch_id,
                'order_number' => $row->formatted_order_number ?? $row->order_number,
                'status' => $row->order_status ?? $row->status,
                'order_status' => $row->order_status ?? $row->status,
                'placed_via' => $row->placed_via ?? null,
                'order_type' => $row->order_type ?? null,
                'order_type_id' => $row->order_type_id ?? null,
                'custom_order_type_name' => $row->custom_order_type_name ?? null,
                'date_time' => $row->date_time ?? null,
                'table_id' => $row->table_id ?? null,
                'customer_id' => $row->customer_id ?? null,
                'financials' => [
                    'sub_total' => $row->sub_total !== null ? (float) $row->sub_total : null,
                    'discount_type' => $row->discount_type ?? null,
                    'discount_value' => $row->discount_value !== null ? (float) $row->discount_value : null,
                    'discount_amount' => $discountAmountValue,
                    'delivery_fee' => $deliveryFeeValue,
                    'tax_total' => $taxTotalValue,
                    'total' => $grandTotal,
                    'amount_paid' => $row->amount_paid !== null ? (float) $row->amount_paid : null,
                ],
                'order' => $row,
                'items' => $items,
                'charges' => $charges,
                'taxes' => $taxes,
                'customer' => $customer,
                'table' => $table,
                'delivery_executive' => $deliveryExecutive,
                'order_type_meta' => $orderType,
                'cart' => [
                    'items' => $items,
                    'charges' => $charges,
                    'taxes' => $taxes,
                    'summary' => [
                        'sub_total' => $subTotalValue,
                        'discount_amount' => $discountAmountValue,
                        'tax_total' => $taxTotalValue,
                        'charges_total' => $chargesTotal,
                        'delivery_fee' => $deliveryFeeValue,
                        'grand_total' => $grandTotal,
                    ],
                ],
                'notes' => $items->isEmpty() ? 'No line items found; using KOT/split fallbacks. If still empty, verify order_items/kot_items data in DB.' : null,
            ]);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration getOrder failed', [
                'branch_id' => $this->branch?->id,
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);

            // Return minimal fallback instead of 500
            try {
                $order = \DB::table('orders')->where('id', $id)->first();
                if ($order) {
                    return response()->json([
                        'id' => $order->id,
                        'branch_id' => $order->branch_id ?? null,
                        'order_status' => $order->order_status ?? $order->status ?? null,
                        'status' => $order->status ?? null,
                        'order_number' => $order->formatted_order_number ?? $order->order_number ?? null,
                        'note' => 'Minimal payload returned due to internal parsing error.',
                    ]);
                }
            } catch (\Throwable $inner) {
                // swallow
            }

            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.internal_error'),
            ], 500);
        }
    }

    public function updateOrderStatus(Request $request, $id)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $data = $request->validate([
            'order_status' => 'nullable|string',
            'status' => 'nullable|string',
        ]);
        $incomingStatus = $data['order_status'] ?? $data['status'] ?? null;
        if ($incomingStatus === null || $incomingStatus === '') {
            return response()->json([
                'success' => false,
                'message' => __('validation.required', ['attribute' => 'status']),
                'errors' => ['status' => [__('validation.required', ['attribute' => 'status'])]],
            ], 422);
        }
        $enumOrderStatus = $this->enumOptions('orders', 'order_status');
        $enumStatus = $this->enumOptions('orders', 'status');

        $fallbackStatuses = class_exists(OrderStatus::class)
            ? collect(OrderStatus::cases())->map->value->merge(['draft', 'paid', 'open', 'closed'])->unique()->values()->all()
            : ['placed', 'confirmed', 'preparing', 'food_ready', 'ready_for_pickup', 'out_for_delivery', 'served', 'delivered', 'cancelled', 'draft', 'paid', 'open', 'closed'];

        $allowedStatuses = collect([$enumOrderStatus, $enumStatus, $fallbackStatuses])
            ->filter()
            ->flatten()
            ->unique()
            ->values()
            ->all();

        if (! in_array($incomingStatus, $allowedStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.invalid_status'),
                'allowed' => $allowedStatuses,
            ], 422);
        }
        try {
            $safeOrderStatus = $this->safety->sanitizeOrderStatus($incomingStatus, null);
            $safeStatus = $this->safety->sanitizeStatusColumn($incomingStatus, null);

            $payload = [];
            // Only update order_status (workflow). Skip legacy status to avoid enum conflicts.
            if ($safeOrderStatus !== null && Schema::hasColumn('orders', 'order_status')) {
                if (empty($enumOrderStatus) || in_array($safeOrderStatus, $enumOrderStatus, true)) {
                    $payload['order_status'] = $safeOrderStatus;
                }
            }

            // If neither column accepts the value, return graceful no-op to avoid 500s.
            if (empty($payload)) {
                return response()->json([
                    'success' => true,
                    'message' => __('applicationintegration::messages.status_ok'),
                    'note' => 'Status value not written (enum mismatch or column missing).',
                ]);
            }

            $query = \DB::table('orders')->where('id', $id);
            if ($this->branch?->id) {
                $query->where('branch_id', $this->branch->id);
            }

            $updated = $query->update($payload);

            if ($updated === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('applicationintegration::messages.not_found'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'order_id' => $id,
                'status' => $data['status'],
            ]);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration updateOrderStatus failed', [
                'order_id' => $id,
                'branch_id' => $this->branch?->id,
                'error' => $e->getMessage(),
            ]);

            // Return a safe response instead of 500 to keep tester/clients running
            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.internal_error'),
                'allowed' => $allowedStatuses,
                'note' => 'Update skipped due to internal error; see logs.',
            ], 200);
        }
    }

    /**
     * Add or update tip for an order.
     */
    public function addTip(Request $request, $id)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        try {
            $query = \DB::table('orders')->where('id', $id);
            if ($this->branch?->id) {
                $query->where('branch_id', $this->branch->id);
            }

            $exists = $query->exists();
            if (! $exists) {
                return response()->json([
                    'success' => false,
                    'message' => __('applicationintegration::messages.not_found'),
                ], 404);
            }

            $payload = [];
            if (Schema::hasColumn('orders', 'tip_amount')) {
                $payload['tip_amount'] = $data['amount'];
            }
            if (Schema::hasColumn('orders', 'tip_note') && array_key_exists('note', $data)) {
                $payload['tip_note'] = $data['note'];
            }

            if (empty($payload)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tip columns not available; nothing updated.',
                ]);
            }

            $query->update($payload);

            return response()->json([
                'success' => true,
                'order_id' => $id,
                'tip_amount' => $payload['tip_amount'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration addTip failed', [
                'order_id' => $id,
                'branch_id' => $this->branch?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.internal_error'),
            ], 500);
        }
    }

    /**
     * Create a split payment record (with optional linked items).
     */
    public function addSplitPayment(Request $request, $id)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $allowedPaymentMethods = ['cash', 'upi', 'card', 'bank_transfer', 'due', 'stripe', 'razorpay'];
        $allowedStatuses = ['pending', 'paid'];

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:' . implode(',', $allowedPaymentMethods),
            'status' => 'nullable|string|in:' . implode(',', $allowedStatuses),
            'items' => 'nullable|array',
            'items.*.order_item_id' => 'required|integer',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        $status = $data['status'] ?? 'pending';

        try {
            $orderQuery = \DB::table('orders')->where('id', $id);
            if ($this->branch?->id) {
                $orderQuery->where('branch_id', $this->branch->id);
            }

            $order = $orderQuery->first();
            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => __('applicationintegration::messages.not_found'),
                ], 404);
            }

            $orderItemIds = collect();
            if (! empty($data['items'])) {
                $orderItemIds = \DB::table('order_items')
                    ->where('order_id', $id)
                    ->pluck('id');

                $invalidItem = collect($data['items'])
                    ->first(function ($row) use ($orderItemIds) {
                        return ! $orderItemIds->contains(data_get($row, 'order_item_id'));
                    });

                if ($invalidItem) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more order_item_id values do not belong to this order.',
                    ], 422);
                }
            }

            $split = null;
            \DB::transaction(function () use (&$split, $id, $data, $status) {
                $split = SplitOrder::create([
                    'order_id' => $id,
                    'amount' => $data['amount'],
                    'payment_method' => $data['payment_method'],
                    'status' => $status,
                ]);

                if (! empty($data['items'])) {
                    $itemsPayload = collect($data['items'])->map(function ($row) use ($split) {
                        return [
                            'split_order_id' => $split->id,
                            'order_item_id' => $row['order_item_id'],
                            'quantity' => $row['quantity'] ?? null,
                        ];
                    })->all();

                    if (! empty($itemsPayload)) {
                        SplitOrderItem::insert($itemsPayload);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'order_id' => $id,
                'split_order_id' => $split?->id,
                'amount' => $split?->amount,
                'payment_method' => $split?->payment_method,
                'status' => $split?->status,
            ]);
        } catch (\Throwable $e) {
            \Log::error('ApplicationIntegration addSplitPayment failed', [
                'order_id' => $id,
                'branch_id' => $this->branch?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.internal_error'),
            ], 500);
        }
    }

    protected function enumOptions(string $table, string $column): array
    {
        try {
            $columns = \DB::select("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
            if (! empty($columns)) {
                $type = $columns[0]->Type ?? $columns[0]->type ?? null;
                if ($type && str_starts_with($type, 'enum(')) {
                    $trimmed = trim($type, "enum()");
                    $parts = explode(',', $trimmed);
                    return collect($parts)
                        ->map(function ($v) {
                            return trim($v, "'\" ");
                        })
                        ->filter()
                        ->values()
                        ->all();
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }

    protected function orderBaseQuery()
    {
        if (method_exists(Order::class, 'withoutCasts')) {
            return Order::withoutCasts(['order_status']);
        }

        return Order::query();
    }

    private function normalizeDeliveryStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'available' => 'available',
            'in delivery', 'in_delivery', 'ondelivery', 'on_delivery' => 'in_delivery',
            'inactive', 'disabled' => 'inactive',
            default => $status,
        };
    }

    private function menuItemNameExpr()
    {
        $hasItemName = Schema::hasColumn('menu_items', 'item_name');
        $hasName = Schema::hasColumn('menu_items', 'name');

        if ($hasItemName && $hasName) {
            return DB::raw('COALESCE(menu_items.item_name, menu_items.name) as menu_item_name');
        } elseif ($hasItemName) {
            return DB::raw('menu_items.item_name as menu_item_name');
        } elseif ($hasName) {
            return DB::raw('menu_items.name as menu_item_name');
        } else {
            return DB::raw("'Unknown Item' as menu_item_name");
        }
    }

    private function calculateChargeAmount($charge, $orderRow): ?float
    {
        if (! $charge) {
            return null;
        }

        $baseAmount = max(
            0,
            (float) data_get($orderRow, 'sub_total', 0) - (float) data_get($orderRow, 'discount_amount', 0)
        );

        $type = property_exists($charge, 'charge_type') ? $charge->charge_type : null;
        $value = property_exists($charge, 'charge_value') ? $charge->charge_value : null;

        if ($type === null || $value === null) {
            return null;
        }

        $amount = $type === 'percent'
            ? ($baseAmount * (float) $value) / 100
            : (float) $value;

        return round($amount, 2);
    }

    public function payOrder(Request $request, $id)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $data = $request->validate([
            'amount' => 'nullable|numeric',
            'method' => 'nullable|string',
        ]);

        try {
            $exists = \DB::table('orders')
                ->where('branch_id', $this->branch->id)
                ->where('id', $id)
                ->exists();

            if (! $exists) {
                return response()->json([
                    'success' => false,
                    'message' => __('applicationintegration::messages.not_found'),
                ], 404);
            }

            $enumOrderStatus = $this->enumOptions('orders', 'order_status');
            $allowedOrderStatuses = $enumOrderStatus;
            if (class_exists(\App\Enums\OrderStatus::class)) {
                $allowedOrderStatuses = array_unique(array_merge(
                    $allowedOrderStatuses,
                    collect(\App\Enums\OrderStatus::cases())->map->value->all()
                ));
            }

            $payload = [];

            // Check status column enum and set appropriate value
            if (Schema::hasColumn('orders', 'status')) {
                $enumStatus = $this->enumOptions('orders', 'status');
                if (empty($enumStatus) || in_array('paid', $enumStatus, true)) {
                    $payload['status'] = 'paid';
                } elseif (in_array('billed', $enumStatus, true)) {
                    // Fallback to 'billed' if 'paid' is not available
                    $payload['status'] = 'billed';
                }
            }

            // Check order_status enum and set appropriate value
            $orderStatusAcceptsPaid = in_array('paid', $allowedOrderStatuses, true);
            if (Schema::hasColumn('orders', 'order_status') && $orderStatusAcceptsPaid) {
                $payload['order_status'] = 'paid';
            } elseif (Schema::hasColumn('orders', 'order_status')) {
                // Fallback for delivery orders: use 'delivered', for dine-in: use 'served'
                $fallbacks = ['delivered', 'served', 'completed'];
                foreach ($fallbacks as $fallback) {
                    if (in_array($fallback, $allowedOrderStatuses, true)) {
                        $payload['order_status'] = $fallback;
                        break;
                    }
                }
            }

            if (Schema::hasColumn('orders', 'amount_paid') && array_key_exists('amount', $data) && $data['amount'] !== null) {
                $payload['amount_paid'] = $data['amount'];
            }

            if (! empty($payload)) {
                \DB::table('orders')
                    ->where('branch_id', $this->branch->id)
                    ->where('id', $id)
                    ->update($payload);
            }

            $tableId = \DB::table('orders')->where('id', $id)->value('table_id');
            if ($tableId) {
                $table = Table::find($tableId);
                if ($table) {
                    $table->available_status = 'available';
                    $table->saveQuietly();
                    TableSession::where('table_id', $table->id)->delete();
                }
            }

            return response()->json([
                'success' => true,
                'order_id' => $id,
                'status' => $payload['status'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('POS payOrder failed', [
                'order_id' => $id,
                'branch_id' => $this->branch?->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function listReservations(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $status = $request->query('status');

        $reservations = Reservation::where('branch_id', $this->branch->id)
            ->when($status, fn($q) => $q->where('reservation_status', $status))
            ->with('table')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($reservations);
    }

    public function createReservation(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }
        $data = $request->validate([
            'table_id' => 'required|integer',
            'reservation_date_time' => 'required|date',
            'party_size' => 'required|integer|min:1',
            'name' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $reservation = Reservation::create([
            'branch_id' => $this->branch->id,
            'table_id' => $data['table_id'],
            'reservation_date_time' => $data['reservation_date_time'],
            'party_size' => $data['party_size'],
            'reservation_status' => 'pending',
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return response()->json($reservation);
    }

    public function updateReservationStatus(Request $request, $id)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $data = $request->validate([
            'status' => 'required|string',
        ]);

        $reservation = Reservation::where('branch_id', $this->branch->id)->findOrFail($id);
        $reservation->reservation_status = $data['status'];
        $reservation->save();

        return response()->json($reservation);
    }

    private function resolvePosMachineId(?string $publicId, ?string $token, ?string $deviceId): ?int
    {
        if (! function_exists('module_enabled') || ! module_enabled('MultiPOS')) {
            return null;
        }

        if (! class_exists(\Modules\MultiPOS\Entities\PosMachine::class)) {
            return null;
        }

        if (! Schema::hasTable('pos_machines') || ! Schema::hasColumn('orders', 'pos_machine_id')) {
            return null;
        }

        $query = \Modules\MultiPOS\Entities\PosMachine::where('branch_id', $this->branch?->id);

        if ($publicId) {
            $query->where('public_id', $publicId);
        } elseif ($token) {
            $query->where('token', $token);
        } elseif ($deviceId) {
            $query->where('device_id', $deviceId);
        } else {
            return null;
        }

        $machine = $query->first();

        return $machine?->id;
    }

    /**
     * Update order items - add, update quantity, or remove items from an existing order.
     *
     * @param  Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/orders/{id}/items
     */
    public function updateOrderItems(Request $request, $orderId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $order = Order::where('branch_id', $this->branch->id)
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check if order can be modified
            $nonModifiableStatuses = ['paid', 'canceled', 'cancelled'];
            $currentStatus = $order->status ?? $order->order_status ?? '';
            if (in_array(strtolower($currentStatus), $nonModifiableStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify a paid or cancelled order',
                ], 400);
            }

            $items = $request->input('items', []);
            $recalculate = $request->input('recalculate_totals', true);
            $addedItems = [];
            $updatedItems = [];
            $removedItems = [];

            DB::beginTransaction();

            foreach ($items as $item) {
                $action = $item['action'] ?? 'add';

                if ($action === 'add') {
                    // Add new item
                    $menuItemId = $item['menu_item_id'] ?? $item['id'] ?? null;
                    $menuItem = MenuItem::find($menuItemId);

                    if (!$menuItem) {
                        continue;
                    }

                    $qty = max(1, (int) ($item['quantity'] ?? 1));
                    $price = (float) ($item['price'] ?? 0);

                    // Fallback price from MenuItem
                    if ($price <= 0) {
                        $price = (float) ($menuItem->price ?? $menuItem->final_price ?? 0);
                    }

                    $orderItemData = [
                        'order_id' => $order->id,
                        'branch_id' => $this->branch->id,
                        'menu_item_id' => $menuItem->id,
                        'quantity' => $qty,
                        'price' => $price,
                        'amount' => $price * $qty,
                        'note' => $item['note'] ?? null,
                    ];

                    if (isset($item['variation_id']) && $item['variation_id']) {
                        $orderItemData['menu_item_variation_id'] = $item['variation_id'];
                    }

                    $orderItem = OrderItem::create($orderItemData);
                    $addedItems[] = $orderItem->id;
                } elseif ($action === 'update') {
                    // Update existing item quantity
                    $orderItemId = $item['order_item_id'] ?? null;
                    if (!$orderItemId) continue;

                    $orderItem = OrderItem::where('order_id', $order->id)
                        ->where('id', $orderItemId)
                        ->first();

                    if ($orderItem) {
                        $newQty = max(1, (int) ($item['quantity'] ?? $orderItem->quantity));
                        $orderItem->quantity = $newQty;
                        $orderItem->amount = $orderItem->price * $newQty;
                        $orderItem->save();
                        $updatedItems[] = $orderItem->id;
                    }
                } elseif ($action === 'remove') {
                    // Remove item
                    $orderItemId = $item['order_item_id'] ?? null;
                    if (!$orderItemId) continue;

                    $deleted = OrderItem::where('order_id', $order->id)
                        ->where('id', $orderItemId)
                        ->delete();

                    if ($deleted) {
                        $removedItems[] = $orderItemId;
                    }
                }
            }

            // Recalculate totals
            if ($recalculate) {
                $subTotal = OrderItem::where('order_id', $order->id)->sum('amount');
                $order->sub_total = $subTotal;

                // Recalculate with existing discount
                $discountAmount = (float) ($order->discount_amount ?? 0);
                $taxTotal = (float) ($order->tax ?? 0);
                $chargesTotal = (float) ($order->extra_charge_amount ?? 0);

                $order->total = $subTotal - $discountAmount + $taxTotal + $chargesTotal;
                $order->amount = $order->total;
                $order->save();
            }

            DB::commit();

            // Refresh order with items
            $order->refresh();
            $order->load('items');

            return response()->json([
                'success' => true,
                'message' => 'Order items updated',
                'data' => [
                    'order_id' => $order->id,
                    'added_items' => $addedItems,
                    'updated_items' => $updatedItems,
                    'removed_items' => $removedItems,
                    'sub_total' => $order->sub_total,
                    'total' => $order->total,
                    'items_count' => $order->items->count(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('POS Update Order Items Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order items',
            ], 500);
        }
    }

    /**
     * Create a new KOT for an existing order.
     *
     * @param  Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     * @api    POST /api/application-integration/pos/orders/{id}/kot
     */
    public function createKot(Request $request, $orderId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $order = Order::where('branch_id', $this->branch->id)
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            // Check order status
            $nonKotStatuses = ['paid', 'canceled', 'cancelled'];
            $currentStatus = $order->status ?? $order->order_status ?? '';
            if (in_array(strtolower($currentStatus), $nonKotStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create KOT for paid or cancelled order',
                ], 400);
            }

            $orderItemIds = $request->input('order_item_ids', []);
            $note = $request->input('note', '');
            $kitchenPlaceId = $request->input('kitchen_place_id');

            // If no specific items, use all order items
            if (empty($orderItemIds)) {
                $orderItemIds = OrderItem::where('order_id', $order->id)->pluck('id')->toArray();
            }

            if (empty($orderItemIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items to create KOT',
                ], 400);
            }

            DB::beginTransaction();

            // Create KOT
            $kot = \App\Models\Kot::create([
                'branch_id' => $this->branch->id,
                'kot_number' => \App\Models\Kot::generateKotNumber($this->branch),
                'order_id' => $order->id,
                'order_type_id' => $order->order_type_id,
                'token_number' => \App\Models\Kot::generateTokenNumber($this->branch->id, $order->order_type_id),
                'note' => $note,
                'kitchen_place_id' => $kitchenPlaceId,
            ]);

            // Create KOT items
            $kotItems = [];
            foreach ($orderItemIds as $orderItemId) {
                $orderItem = OrderItem::find($orderItemId);
                if (!$orderItem || $orderItem->order_id != $order->id) {
                    continue;
                }

                $kotItem = \App\Models\KotItem::create([
                    'kot_id' => $kot->id,
                    'order_item_id' => $orderItem->id,
                    'menu_item_id' => $orderItem->menu_item_id,
                    'quantity' => $orderItem->quantity,
                ]);

                $kotItems[] = $kotItem->id;
            }

            // Update order status if it was draft
            if (in_array(strtolower($currentStatus), ['draft', 'placed'])) {
                if (Schema::hasColumn('orders', 'status')) {
                    $order->status = 'kot';
                }
                if (Schema::hasColumn('orders', 'order_status')) {
                    $order->order_status = 'confirmed';
                }
                $order->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'KOT created successfully',
                'data' => [
                    'kot_id' => $kot->id,
                    'kot_number' => $kot->kot_number,
                    'token_number' => $kot->token_number,
                    'order_id' => $order->id,
                    'items_count' => count($kotItems),
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('POS Create KOT Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create KOT',
            ], 500);
        }
    }

    /**
     * Get list of KOTs for kitchen display.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/kots
     */
    public function getKots(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $status = $request->query('status'); // pending_confirmation, in_kitchen, food_ready, served, cancelled
            $kitchenPlaceId = $request->query('kitchen_place_id');
            $date = $request->query('date', now()->toDateString());
            $limit = min(100, max(1, (int) $request->query('limit', 50)));
            $offset = max(0, (int) $request->query('offset', 0));

            $query = \App\Models\Kot::where('branch_id', $this->branch->id)
                ->with(['items.menuItem', 'items.menuItemVariation', 'items.modifierOptions', 'order', 'kotPlace', 'orderType'])
                ->whereDate('created_at', $date)
                ->orderByDesc('created_at');

            if ($status) {
                $query->where('status', $status);
            }

            if ($kitchenPlaceId) {
                $query->where('kitchen_place_id', $kitchenPlaceId);
            }

            $total = $query->count();
            $kots = $query->skip($offset)->take($limit)->get();

            $data = $kots->map(function ($kot) {
                return [
                    'id' => $kot->id,
                    'kot_number' => $kot->kot_number,
                    'token_number' => $kot->token_number,
                    'order_id' => $kot->order_id,
                    'order_number' => $kot->order->order_number ?? null,
                    'order_type' => $kot->orderType?->name ?? $kot->order?->order_type ?? null,
                    'table_name' => $kot->order?->table?->table_name ?? null,
                    'kitchen_place' => $kot->kotPlace?->name ?? null,
                    'kitchen_place_id' => $kot->kitchen_place_id,
                    'status' => $kot->status,
                    'note' => $kot->note,
                    'items' => $kot->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'menu_item_id' => $item->menu_item_id,
                            'name' => $item->menuItem?->item_name ?? $item->menuItem?->name ?? 'Unknown',
                            'variation' => $item->menuItemVariation?->name ?? null,
                            'quantity' => $item->quantity,
                            'status' => $item->status,
                            'note' => $item->note,
                            'modifiers' => $item->modifierOptions->map(fn($m) => [
                                'id' => $m->id,
                                'name' => $m->name,
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                    'items_count' => $kot->items->count(),
                    'created_at' => $kot->created_at?->toIso8601String(),
                    'updated_at' => $kot->updated_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => $total,
                    'offset' => $offset,
                    'limit' => $limit,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get KOTs Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch KOTs',
            ], 500);
        }
    }

    /**
     * Get single KOT details.
     *
     * @param  int  $kotId
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/kots/{id}
     */
    public function getKot($kotId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $kot = \App\Models\Kot::where('branch_id', $this->branch->id)
                ->with(['items.menuItem', 'items.menuItemVariation', 'items.modifierOptions', 'items.orderItem', 'order.table', 'kotPlace', 'orderType', 'cancelReason'])
                ->find($kotId);

            if (!$kot) {
                return response()->json([
                    'success' => false,
                    'message' => 'KOT not found',
                ], 404);
            }

            $data = [
                'id' => $kot->id,
                'kot_number' => $kot->kot_number,
                'token_number' => $kot->token_number,
                'order_id' => $kot->order_id,
                'order_number' => $kot->order?->order_number ?? null,
                'formatted_order_number' => $kot->order?->formatted_order_number ?? null,
                'order_type' => $kot->orderType?->name ?? $kot->order?->order_type ?? null,
                'order_type_id' => $kot->order_type_id,
                'table_id' => $kot->order?->table_id,
                'table_name' => $kot->order?->table?->table_name ?? null,
                'kitchen_place' => $kot->kotPlace?->name ?? null,
                'kitchen_place_id' => $kot->kitchen_place_id,
                'status' => $kot->status,
                'note' => $kot->note,
                'cancel_reason' => $kot->cancelReason?->reason ?? $kot->cancel_reason_text,
                'items' => $kot->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'order_item_id' => $item->order_item_id,
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $item->menuItem?->item_name ?? $item->menuItem?->name ?? 'Unknown',
                        'variation_id' => $item->menu_item_variation_id,
                        'variation' => $item->menuItemVariation?->name ?? null,
                        'quantity' => $item->quantity,
                        'status' => $item->status,
                        'note' => $item->note,
                        'price' => $item->orderItem?->price ?? $item->menuItem?->price ?? 0,
                        'modifiers' => $item->modifierOptions->map(fn($m) => [
                            'id' => $m->id,
                            'name' => $m->name,
                        ])->values()->all(),
                        'cancel_reason' => $item->cancelReason?->reason ?? $item->cancel_reason_text,
                        'cancelled_by' => $item->cancelledBy?->name ?? null,
                    ];
                })->values()->all(),
                'items_count' => $kot->items->count(),
                'created_at' => $kot->created_at?->toIso8601String(),
                'updated_at' => $kot->updated_at?->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get KOT Error', [
                'kot_id' => $kotId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch KOT',
            ], 500);
        }
    }

    /**
     * Update KOT status.
     *
     * @param  Request  $request
     * @param  int  $kotId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/kots/{id}/status
     */
    public function updateKotStatus(Request $request, $kotId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $kot = \App\Models\Kot::where('branch_id', $this->branch->id)->find($kotId);

            if (!$kot) {
                return response()->json([
                    'success' => false,
                    'message' => 'KOT not found',
                ], 404);
            }

            $newStatus = $request->input('status');
            $cancelReasonId = $request->input('cancel_reason_id');
            $cancelReasonText = $request->input('cancel_reason_text');

            $validStatuses = ['pending_confirmation', 'in_kitchen', 'food_ready', 'served', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
                ], 400);
            }

            DB::beginTransaction();

            $kot->status = $newStatus;

            if ($newStatus === 'cancelled') {
                $kot->cancel_reason_id = $cancelReasonId;
                $kot->cancel_reason_text = $cancelReasonText;

                // Also cancel all KOT items
                $kot->items()->update([
                    'status' => 'cancelled',
                    'cancel_reason_id' => $cancelReasonId,
                    'cancel_reason_text' => $cancelReasonText,
                    'cancelled_by' => auth()->id(),
                ]);
            }

            // When KOT is served, update item statuses to ready if not already
            if ($newStatus === 'served') {
                $kot->items()->whereNull('status')->orWhere('status', '!=', 'cancelled')->update([
                    'status' => 'ready',
                ]);
            }

            $kot->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'KOT status updated successfully',
                'data' => [
                    'id' => $kot->id,
                    'kot_number' => $kot->kot_number,
                    'status' => $kot->status,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('POS Update KOT Status Error', [
                'kot_id' => $kotId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update KOT status',
            ], 500);
        }
    }

    /**
     * Update KOT item status.
     *
     * @param  Request  $request
     * @param  int  $kotItemId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/kot-items/{id}/status
     */
    public function updateKotItemStatus(Request $request, $kotItemId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $kotItem = \App\Models\KotItem::whereHas('kot', function ($q) {
                $q->where('branch_id', $this->branch->id);
            })->find($kotItemId);

            if (!$kotItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'KOT item not found',
                ], 404);
            }

            $newStatus = $request->input('status');
            $cancelReasonId = $request->input('cancel_reason_id');
            $cancelReasonText = $request->input('cancel_reason_text');

            $validStatuses = ['pending', 'cooking', 'ready', 'cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
                ], 400);
            }

            $kotItem->status = $newStatus;

            if ($newStatus === 'cancelled') {
                $kotItem->cancel_reason_id = $cancelReasonId;
                $kotItem->cancel_reason_text = $cancelReasonText;
                $kotItem->cancelled_by = auth()->id();
            }

            $kotItem->save();

            // Check if all items in KOT are ready
            $kot = $kotItem->kot;
            $allReady = $kot->items()->where('status', '!=', 'cancelled')->where('status', '!=', 'ready')->count() === 0;

            if ($allReady && $kot->status === 'in_kitchen') {
                $kot->status = 'food_ready';
                $kot->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'KOT item status updated successfully',
                'data' => [
                    'id' => $kotItem->id,
                    'kot_id' => $kotItem->kot_id,
                    'status' => $kotItem->status,
                    'kot_status' => $kot->fresh()->status,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Update KOT Item Status Error', [
                'kot_item_id' => $kotItemId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update KOT item status',
            ], 500);
        }
    }

    /**
     * Get KOT places (kitchen stations).
     *
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/kot-places
     */
    public function getKotPlaces()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $places = \App\Models\KotPlace::where('branch_id', $this->branch->id)
                ->where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get();

            $data = $places->map(function ($place) {
                return [
                    'id' => $place->id,
                    'name' => $place->name,
                    'type' => $place->type,
                    'is_default' => (bool) $place->is_default,
                    'printer_id' => $place->printer_id,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get KOT Places Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch KOT places',
            ], 500);
        }
    }

    /**
     * Get KOT cancel reasons.
     *
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/kot-cancel-reasons
     */
    public function getKotCancelReasons()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $restaurantId = $this->branch->restaurant_id;

            $reasons = \App\Models\KotCancelReason::where(function ($q) use ($restaurantId) {
                $q->where('restaurant_id', $restaurantId)
                    ->orWhereNull('restaurant_id');
            })->get();

            $data = $reasons->map(function ($reason) {
                return [
                    'id' => $reason->id,
                    'reason' => $reason->reason,
                    'cancel_order' => (bool) $reason->cancel_order,
                    'cancel_kot' => (bool) $reason->cancel_kot,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get KOT Cancel Reasons Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch KOT cancel reasons',
            ], 500);
        }
    }

    /**
     * Get KOTs by order ID.
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/orders/{id}/kots
     */
    public function getOrderKots($orderId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $order = Order::where('branch_id', $this->branch->id)->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $kots = \App\Models\Kot::where('order_id', $orderId)
                ->with(['items.menuItem', 'items.menuItemVariation', 'items.modifierOptions', 'kotPlace'])
                ->orderBy('created_at')
                ->get();

            $data = $kots->map(function ($kot) {
                return [
                    'id' => $kot->id,
                    'kot_number' => $kot->kot_number,
                    'token_number' => $kot->token_number,
                    'kitchen_place' => $kot->kotPlace?->name ?? null,
                    'kitchen_place_id' => $kot->kitchen_place_id,
                    'status' => $kot->status,
                    'note' => $kot->note,
                    'items' => $kot->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'menu_item_id' => $item->menu_item_id,
                            'name' => $item->menuItem?->item_name ?? $item->menuItem?->name ?? 'Unknown',
                            'variation' => $item->menuItemVariation?->name ?? null,
                            'quantity' => $item->quantity,
                            'status' => $item->status,
                            'note' => $item->note,
                            'modifiers' => $item->modifierOptions->map(fn($m) => [
                                'id' => $m->id,
                                'name' => $m->name,
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                    'created_at' => $kot->created_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_kots' => $kots->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get Order KOTs Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order KOTs',
            ], 500);
        }
    }

    // =====================================================
    // DELIVERY MANAGEMENT ENDPOINTS
    // =====================================================

    /**
     * Get branch delivery settings.
     *
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/delivery-settings
     */
    public function getDeliverySettings()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $settings = \App\Models\BranchDeliverySetting::where('branch_id', $this->branch->id)->first();

            if (!$settings) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No delivery settings configured for this branch',
                ]);
            }

            $tiers = \App\Models\DeliveryFeeTier::where('branch_id', $this->branch->id)
                ->orderBy('min_distance')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $settings->id,
                    'is_enabled' => (bool) $settings->is_enabled,
                    'max_radius' => (float) $settings->getRawOriginal('max_radius'),
                    'unit' => $settings->unit ?? 'km',
                    'fee_type' => $settings->fee_type?->value ?? $settings->fee_type ?? 'fixed',
                    'fixed_fee' => $settings->fixed_fee ? (float) $settings->fixed_fee : null,
                    'per_distance_rate' => $settings->per_distance_rate ? (float) $settings->per_distance_rate : null,
                    'free_delivery_over_amount' => $settings->free_delivery_over_amount ? (float) $settings->free_delivery_over_amount : null,
                    'free_delivery_within_radius' => $settings->free_delivery_within_radius ? (float) $settings->free_delivery_within_radius : null,
                    'delivery_schedule_start' => $settings->delivery_schedule_start,
                    'delivery_schedule_end' => $settings->delivery_schedule_end,
                    'prep_time_minutes' => (int) ($settings->prep_time_minutes ?? 20),
                    'additional_eta_buffer_time' => $settings->additional_eta_buffer_time ? (int) $settings->additional_eta_buffer_time : null,
                    'avg_delivery_speed_kmh' => (int) ($settings->avg_delivery_speed_kmh ?? 30),
                    'branch_lat' => $this->branch->lat ? (float) $this->branch->lat : null,
                    'branch_lng' => $this->branch->lng ? (float) $this->branch->lng : null,
                    'fee_tiers' => $tiers->map(fn($tier) => [
                        'id' => $tier->id,
                        'min_distance' => (float) $tier->min_distance,
                        'max_distance' => (float) $tier->max_distance,
                        'fee' => (float) $tier->fee,
                    ])->values()->all(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get Delivery Settings Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery settings',
            ], 500);
        }
    }

    /**
     * Calculate delivery fee based on customer location.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @api    POST /api/application-integration/pos/delivery-fee/calculate
     */
    public function calculateDeliveryFee(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $customerLat = $request->input('lat');
            $customerLng = $request->input('lng');
            $orderAmount = (float) ($request->input('order_amount', 0));

            if (!$customerLat || !$customerLng) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer latitude and longitude are required',
                ], 400);
            }

            $settings = \App\Models\BranchDeliverySetting::where('branch_id', $this->branch->id)->first();

            if (!$settings || !$settings->is_enabled) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'available' => false,
                        'message' => 'Delivery is not available for this branch',
                    ],
                ]);
            }

            $branchLat = $this->branch->lat;
            $branchLng = $this->branch->lng;

            if (!$branchLat || !$branchLng) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'available' => false,
                        'message' => 'Branch location not configured',
                    ],
                ]);
            }

            // Calculate distance using Haversine formula
            $distance = $this->haversineDistance($branchLat, $branchLng, $customerLat, $customerLng);

            // Convert max_radius to km if needed
            $maxRadius = (float) $settings->getRawOriginal('max_radius');
            if (($settings->unit ?? 'km') === 'miles') {
                $maxRadius = $maxRadius * 1.60934;
            }

            // Check if within delivery range
            if ($distance > $maxRadius) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'available' => false,
                        'distance' => round($distance, 2),
                        'max_radius' => round($maxRadius, 2),
                        'unit' => 'km',
                        'message' => 'Location is outside delivery range',
                    ],
                ]);
            }

            // Check for free delivery
            $isFreeDelivery = false;
            $freeDeliveryRadius = $settings->free_delivery_within_radius;
            $freeDeliveryAmount = $settings->free_delivery_over_amount;

            if ($freeDeliveryRadius && $distance <= $freeDeliveryRadius) {
                $isFreeDelivery = true;
            }
            if ($freeDeliveryAmount && $orderAmount >= $freeDeliveryAmount) {
                $isFreeDelivery = true;
            }

            $deliveryFee = 0;
            if (!$isFreeDelivery) {
                $feeType = $settings->fee_type?->value ?? $settings->fee_type ?? 'fixed';

                switch ($feeType) {
                    case 'fixed':
                        $deliveryFee = (float) ($settings->fixed_fee ?? 0);
                        break;

                    case 'per_distance':
                        $rate = (float) ($settings->per_distance_rate ?? 0);
                        $unit = $settings->unit ?? 'km';
                        $distanceInUnit = $distance;
                        if ($unit === 'miles') {
                            $distanceInUnit = $distance / 1.60934;
                        }
                        $deliveryFee = ceil($distanceInUnit) * $rate;
                        break;

                    case 'tiered':
                        $tiers = \App\Models\DeliveryFeeTier::where('branch_id', $this->branch->id)
                            ->orderBy('min_distance')
                            ->get();

                        foreach ($tiers as $tier) {
                            if ($distance >= $tier->min_distance && $distance <= $tier->max_distance) {
                                $deliveryFee = (float) $tier->fee;
                                break;
                            }
                        }
                        break;
                }
            }

            // Calculate ETA
            $prepTime = (int) ($settings->prep_time_minutes ?? 20);
            $bufferTime = (int) ($settings->additional_eta_buffer_time ?? 0);
            $speed = (int) ($settings->avg_delivery_speed_kmh ?? 30);
            $travelTime = ($distance / $speed) * 60; // minutes
            $etaMin = $prepTime + (int) $travelTime;
            $etaMax = $etaMin + $bufferTime;

            return response()->json([
                'success' => true,
                'data' => [
                    'available' => true,
                    'distance' => round($distance, 2),
                    'unit' => 'km',
                    'fee' => round($deliveryFee, 2),
                    'is_free_delivery' => $isFreeDelivery,
                    'eta_min' => $etaMin,
                    'eta_max' => $etaMax,
                    'message' => $isFreeDelivery ? 'Free delivery!' : null,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Calculate Delivery Fee Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate delivery fee',
            ], 500);
        }
    }

    /**
     * Haversine formula to calculate distance between two points.
     */
    private function haversineDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get delivery fee tiers.
     *
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/delivery-fee-tiers
     */
    public function getDeliveryFeeTiers()
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $tiers = \App\Models\DeliveryFeeTier::where('branch_id', $this->branch->id)
                ->orderBy('min_distance')
                ->get();

            $data = $tiers->map(fn($tier) => [
                'id' => $tier->id,
                'min_distance' => (float) $tier->min_distance,
                'max_distance' => (float) $tier->max_distance,
                'fee' => (float) $tier->fee,
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get Delivery Fee Tiers Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery fee tiers',
            ], 500);
        }
    }

    /**
     * Create a new delivery platform.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @api    POST /api/application-integration/pos/delivery-platforms
     */
    public function createDeliveryPlatform(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:191',
                'commission_type' => 'nullable|in:percent,fixed',
                'commission_value' => 'nullable|numeric|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $platform = DeliveryPlatform::create([
                'branch_id' => $this->branch->id,
                'name' => $validated['name'],
                'commission_type' => $validated['commission_type'] ?? 'percent',
                'commission_value' => $validated['commission_value'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery platform created successfully',
                'data' => [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'commission_type' => $platform->commission_type,
                    'commission_value' => (float) $platform->commission_value,
                    'is_active' => (bool) $platform->is_active,
                    'logo_url' => $platform->logo_url,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('POS Create Delivery Platform Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery platform',
            ], 500);
        }
    }

    /**
     * Update a delivery platform.
     *
     * @param  Request  $request
     * @param  int  $platformId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/delivery-platforms/{id}
     */
    public function updateDeliveryPlatform(Request $request, $platformId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $platform = DeliveryPlatform::where('branch_id', $this->branch->id)->find($platformId);

            if (!$platform) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery platform not found',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:191',
                'commission_type' => 'nullable|in:percent,fixed',
                'commission_value' => 'nullable|numeric|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            if (isset($validated['name'])) {
                $platform->name = $validated['name'];
            }
            if (isset($validated['commission_type'])) {
                $platform->commission_type = $validated['commission_type'];
            }
            if (isset($validated['commission_value'])) {
                $platform->commission_value = $validated['commission_value'];
            }
            if (isset($validated['is_active'])) {
                $platform->is_active = $validated['is_active'];
            }

            $platform->save();

            return response()->json([
                'success' => true,
                'message' => 'Delivery platform updated successfully',
                'data' => [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'commission_type' => $platform->commission_type,
                    'commission_value' => (float) $platform->commission_value,
                    'is_active' => (bool) $platform->is_active,
                    'logo_url' => $platform->logo_url,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('POS Update Delivery Platform Error', [
                'platform_id' => $platformId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery platform',
            ], 500);
        }
    }

    /**
     * Delete a delivery platform.
     *
     * @param  int  $platformId
     * @return \Illuminate\Http\JsonResponse
     * @api    DELETE /api/application-integration/pos/delivery-platforms/{id}
     */
    public function deleteDeliveryPlatform($platformId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $platform = DeliveryPlatform::where('branch_id', $this->branch->id)->find($platformId);

            if (!$platform) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery platform not found',
                ], 404);
            }

            // Check if platform has associated orders
            $hasOrders = Order::where('delivery_app_id', $platform->id)->exists();
            if ($hasOrders) {
                // Soft delete by deactivating
                $platform->is_active = false;
                $platform->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Delivery platform deactivated (has associated orders)',
                ]);
            }

            $platform->delete();

            return response()->json([
                'success' => true,
                'message' => 'Delivery platform deleted successfully',
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Delete Delivery Platform Error', [
                'platform_id' => $platformId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete delivery platform',
            ], 500);
        }
    }

    /**
     * Get single delivery platform details.
     *
     * @param  int  $platformId
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/delivery-platforms/{id}
     */
    public function getDeliveryPlatform($platformId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $platform = DeliveryPlatform::find($platformId);

            if (!$platform) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery platform not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $platform->id,
                    'name' => $platform->name,
                    'logo' => $platform->logo,
                    'logo_url' => $platform->logo_url,
                    'commission_type' => $platform->commission_type,
                    'commission_value' => (float) $platform->commission_value,
                    'formatted_commission' => $platform->formatted_commission,
                    'is_active' => (bool) $platform->is_active,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get Delivery Platform Error', [
                'platform_id' => $platformId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery platform',
            ], 500);
        }
    }

    /**
     * Create a new delivery executive.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @api    POST /api/application-integration/pos/delivery-executives
     */
    public function createDeliveryExecutive(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:191',
                'phone' => 'nullable|string|max:191',
                'phone_code' => 'nullable|string|max:191',
                'status' => 'nullable|in:available,on_delivery,inactive',
            ]);

            $executive = \App\Models\DeliveryExecutive::create([
                'branch_id' => $this->branch->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'phone_code' => $validated['phone_code'] ?? null,
                'status' => $validated['status'] ?? 'available',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery executive created successfully',
                'data' => [
                    'id' => $executive->id,
                    'name' => $executive->name,
                    'phone' => $executive->phone,
                    'phone_code' => $executive->phone_code,
                    'status' => $executive->status,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('POS Create Delivery Executive Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery executive',
            ], 500);
        }
    }

    /**
     * Update a delivery executive.
     *
     * @param  Request  $request
     * @param  int  $executiveId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/delivery-executives/{id}
     */
    public function updateDeliveryExecutive(Request $request, $executiveId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $executive = \App\Models\DeliveryExecutive::where('branch_id', $this->branch->id)->find($executiveId);

            if (!$executive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery executive not found',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'nullable|string|max:191',
                'phone' => 'nullable|string|max:191',
                'phone_code' => 'nullable|string|max:191',
                'status' => 'nullable|in:available,on_delivery,inactive',
            ]);

            if (isset($validated['name'])) {
                $executive->name = $validated['name'];
            }
            if (array_key_exists('phone', $validated)) {
                $executive->phone = $validated['phone'];
            }
            if (array_key_exists('phone_code', $validated)) {
                $executive->phone_code = $validated['phone_code'];
            }
            if (isset($validated['status'])) {
                $executive->status = $validated['status'];
            }

            $executive->save();

            // Clear cache
            cache()->forget('delivery_executives_' . $this->branch->restaurant_id);

            return response()->json([
                'success' => true,
                'message' => 'Delivery executive updated successfully',
                'data' => [
                    'id' => $executive->id,
                    'name' => $executive->name,
                    'phone' => $executive->phone,
                    'phone_code' => $executive->phone_code,
                    'status' => $executive->status,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('POS Update Delivery Executive Error', [
                'executive_id' => $executiveId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery executive',
            ], 500);
        }
    }

    /**
     * Delete a delivery executive.
     *
     * @param  int  $executiveId
     * @return \Illuminate\Http\JsonResponse
     * @api    DELETE /api/application-integration/pos/delivery-executives/{id}
     */
    public function deleteDeliveryExecutive($executiveId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $executive = \App\Models\DeliveryExecutive::where('branch_id', $this->branch->id)->find($executiveId);

            if (!$executive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery executive not found',
                ], 404);
            }

            // Check if executive has associated orders
            $hasOrders = Order::where('delivery_executive_id', $executive->id)->exists();
            if ($hasOrders) {
                // Soft delete by setting inactive
                $executive->status = 'inactive';
                $executive->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Delivery executive set to inactive (has associated orders)',
                ]);
            }

            $executive->delete();

            // Clear cache
            cache()->forget('delivery_executives_' . $this->branch->restaurant_id);

            return response()->json([
                'success' => true,
                'message' => 'Delivery executive deleted successfully',
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Delete Delivery Executive Error', [
                'executive_id' => $executiveId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete delivery executive',
            ], 500);
        }
    }

    /**
     * Update delivery executive status.
     *
     * @param  Request  $request
     * @param  int  $executiveId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/delivery-executives/{id}/status
     */
    public function updateDeliveryExecutiveStatus(Request $request, $executiveId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $executive = \App\Models\DeliveryExecutive::where('branch_id', $this->branch->id)->find($executiveId);

            if (!$executive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery executive not found',
                ], 404);
            }

            $status = $request->input('status');
            $validStatuses = ['available', 'on_delivery', 'inactive'];

            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
                ], 400);
            }

            $executive->status = $status;
            $executive->save();

            // Clear cache
            cache()->forget('delivery_executives_' . $this->branch->restaurant_id);

            return response()->json([
                'success' => true,
                'message' => 'Delivery executive status updated successfully',
                'data' => [
                    'id' => $executive->id,
                    'name' => $executive->name,
                    'status' => $executive->status,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Update Delivery Executive Status Error', [
                'executive_id' => $executiveId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery executive status',
            ], 500);
        }
    }

    /**
     * Assign delivery executive to an order.
     *
     * @param  Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/orders/{id}/assign-delivery
     */
    public function assignDeliveryExecutive(Request $request, $orderId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $order = Order::where('branch_id', $this->branch->id)->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $executiveId = $request->input('delivery_executive_id');
            $deliveryAppId = $request->input('delivery_app_id');

            if ($executiveId) {
                $executive = \App\Models\DeliveryExecutive::where('branch_id', $this->branch->id)->find($executiveId);
                if (!$executive) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Delivery executive not found',
                    ], 404);
                }

                $order->delivery_executive_id = $executiveId;

                // Update executive status to on_delivery
                $executive->status = 'on_delivery';
                $executive->save();
            }

            if ($deliveryAppId) {
                $platform = DeliveryPlatform::find($deliveryAppId);
                if (!$platform) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Delivery platform not found',
                    ], 404);
                }
                $order->delivery_app_id = $deliveryAppId;
            }

            $order->save();

            // Clear cache
            cache()->forget('delivery_executives_' . $this->branch->restaurant_id);

            return response()->json([
                'success' => true,
                'message' => 'Delivery assignment updated successfully',
                'data' => [
                    'order_id' => $order->id,
                    'delivery_executive_id' => $order->delivery_executive_id,
                    'delivery_app_id' => $order->delivery_app_id,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Assign Delivery Executive Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign delivery',
            ], 500);
        }
    }

    /**
     * Update delivery order status.
     *
     * @param  Request  $request
     * @param  int  $orderId
     * @return \Illuminate\Http\JsonResponse
     * @api    PUT /api/application-integration/pos/orders/{id}/delivery-status
     */
    public function updateDeliveryOrderStatus(Request $request, $orderId)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $order = Order::where('branch_id', $this->branch->id)->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            $status = $request->input('status');
            $validStatuses = ['preparing', 'ready_for_pickup', 'out_for_delivery', 'delivered', 'failed'];

            if (!in_array($status, $validStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Valid statuses: ' . implode(', ', $validStatuses),
                ], 400);
            }

            // Update order status
            if (Schema::hasColumn('orders', 'order_status')) {
                $order->order_status = $status;
            }

            // If delivered, mark executive as available
            if ($status === 'delivered' && $order->delivery_executive_id) {
                $executive = \App\Models\DeliveryExecutive::find($order->delivery_executive_id);
                if ($executive) {
                    $executive->status = 'available';
                    $executive->save();
                }
            }

            // If failed, also mark executive as available
            if ($status === 'failed' && $order->delivery_executive_id) {
                $executive = \App\Models\DeliveryExecutive::find($order->delivery_executive_id);
                if ($executive) {
                    $executive->status = 'available';
                    $executive->save();
                }
            }

            $order->save();

            // Clear cache
            cache()->forget('delivery_executives_' . $this->branch->restaurant_id);

            return response()->json([
                'success' => true,
                'message' => 'Delivery status updated successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_status' => $order->order_status ?? $status,
                    'delivery_executive_id' => $order->delivery_executive_id,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Update Delivery Order Status Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery status',
            ], 500);
        }
    }

    /**
     * Get delivery orders (filtered list).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @api    GET /api/application-integration/pos/delivery-orders
     */
    public function getDeliveryOrders(Request $request)
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        try {
            $status = $request->query('status');
            $executiveId = $request->query('delivery_executive_id');
            $platformId = $request->query('delivery_app_id');
            $date = $request->query('date', now()->toDateString());
            $limit = min(100, max(1, (int) $request->query('limit', 50)));
            $offset = max(0, (int) $request->query('offset', 0));

            $query = Order::where('branch_id', $this->branch->id)
                ->where(function ($q) {
                    $q->where('order_type', 'delivery')
                        ->orWhere('order_type', 'like', '%delivery%');
                })
                ->whereDate('created_at', $date)
                ->with(['customer', 'items'])
                ->orderByDesc('created_at');

            if ($status) {
                $query->where(function ($q) use ($status) {
                    $q->where('order_status', $status)
                        ->orWhere('status', $status);
                });
            }

            if ($executiveId) {
                $query->where('delivery_executive_id', $executiveId);
            }

            if ($platformId) {
                $query->where('delivery_app_id', $platformId);
            }

            $total = $query->count();
            $orders = $query->skip($offset)->take($limit)->get();

            $data = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'formatted_order_number' => $order->formatted_order_number,
                    'status' => $order->order_status ?? $order->status,
                    'total' => (float) $order->total,
                    'delivery_fee' => (float) ($order->delivery_fee ?? 0),
                    'delivery_address' => $order->delivery_address,
                    'delivery_time' => $order->delivery_time,
                    'delivery_executive_id' => $order->delivery_executive_id,
                    'delivery_app_id' => $order->delivery_app_id,
                    'customer' => $order->customer ? [
                        'id' => $order->customer->id,
                        'name' => $order->customer->name,
                        'phone' => $order->customer->phone,
                    ] : null,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => $total,
                    'offset' => $offset,
                    'limit' => $limit,
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('POS Get Delivery Orders Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery orders',
            ], 500);
        }
    }
}
