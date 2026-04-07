<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $restaurant;
    protected $branch;

    public function __construct()
    {
        $user = auth()->user();

        if ($user && $user->restaurant_id) {
            $this->restaurant = Restaurant::with('branches')->find($user->restaurant_id);
            $this->branch = $user->branch_id
                ? Branch::find($user->branch_id)
                : ($this->restaurant ? $this->restaurant->branches->first() : null);
        }

        if (! $this->restaurant) {
            $this->restaurant = Restaurant::with('branches')->first();
        }

        if (! $this->branch && $this->restaurant) {
            $this->branch = $this->restaurant->branches()->first();
        }
    }

    public function catalog()
    {
        $pos = app(PosProxyController::class);

        return response()->json([
            'menus' => $pos->getMenus()->getData(true),
            'categories' => $pos->getCategories()->getData(true),
            'items' => $pos->getMenuItems()->getData(true),
        ]);
    }

    public function placeOrder(Request $request)
    {
        // Reuse POS logic to ensure parity with in-venue flows
        $pos = app(PosProxyController::class);

        // Force placed_via to app
        $request->merge(['actions' => $request->input('actions', []), 'placed_via' => 'app']);

        return $pos->submitOrder($request);
    }

    public function myOrders(Request $request)
    {
        $customerId = $request->query('customer_id');
        if (! $customerId) {
            return response()->json([]);
        }

        $orders = Order::where('customer_id', $customerId)
            ->when($this->branch, fn($q) => $q->where('branch_id', $this->branch->id))
            ->with(['items', 'charges', 'taxes', 'table'])
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($orders);
    }
}
