<?php

namespace Modules\MultiPOS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Models\Branch;
use App\Models\Printer;
use App\Models\Order;
use App\Models\Table;
use App\Models\OrderType;
use App\Models\MenuItem;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MultiPOSController extends Controller
{
    /**
     * Display the MultiPOS dashboard
     */
    public function index()
    {
        abort_if(!in_array('MultiPOS', restaurant_modules()), 403);

        $currentBranch = branch();
        $restaurant = $currentBranch->restaurant;

        // Get statistics for MultiPOS dashboard
        $stats = [
            'total_terminals' => $this->getTotalTerminals($currentBranch->id),
            'active_orders' => $this->getActiveOrders($currentBranch->id),
            'today_revenue' => $this->getTodayRevenue($currentBranch->id),
            'total_tables' => $this->getTotalTables($currentBranch->id),
            'pos_usage' => [
                'active' => \Modules\MultiPOS\Entities\PosMachine::forBranch($currentBranch->id)->active()->count(),
                'pending' => \Modules\MultiPOS\Entities\PosMachine::forBranch($currentBranch->id)->pending()->count(),
                'limit' => optional($restaurant->package)->multipos_limit,
            ],
        ];

        return view('multipos::index', compact('stats', 'currentBranch', 'restaurant'));
    }

    /**
     * Display POS terminals management
     */
    public function terminals()
    {
        abort_if(!in_array('MultiPOS', restaurant_modules()), 403);

        $currentBranch = branch();

        // Get all POS terminals/places for this branch
        $terminals = $this->getPOSTerminals($currentBranch->id);

        return view('multipos::terminals', compact('terminals', 'currentBranch'));
    }

    /**
     * Display MultiPOS settings
     */
    public function settings()
    {
        abort_if(!in_array('MultiPOS', restaurant_modules()), 403);

        $currentBranch = branch();

        return view('multipos::settings', compact('currentBranch'));
    }

    /**
     * API: Get terminals
     */
    public function apiTerminals()
    {
        abort_if(!in_array('MultiPOS', restaurant_modules()), 403);

        $currentBranch = branch();
        $terminals = $this->getPOSTerminals($currentBranch->id);

        return response()->json([
            'success' => true,
            'data' => $terminals
        ]);
    }

    /**
     * API: Store new terminal
     */
    public function storeTerminal(Request $request)
    {
        abort_if(!in_array('MultiPOS', restaurant_modules()), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:food,beverage,general',
            'printer_id' => 'nullable|exists:printers,id',
            'is_active' => 'boolean',
        ]);

        $currentBranch = branch();

        // Create new POS terminal/place
        $terminal = $currentBranch->orderPlaces()->create([
            'name' => $request->name,
            'type' => $request->type,
            'printer_id' => $request->printer_id,
            'is_active' => $request->is_active ?? true,
            'is_default' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terminal created successfully',
            'data' => $terminal
        ]);
    }

    /**
     * API: Update terminal
     */
    public function updateTerminal(Request $request, $id)
    {
        abort_if(!in_array('MultiPOS', restaurant_modules()), 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:food,beverage,general',
            'printer_id' => 'nullable|exists:printers,id',
            'is_active' => 'boolean',
        ]);

        $currentBranch = branch();
        $terminal = $currentBranch->orderPlaces()->findOrFail($id);

        $terminal->update([
            'name' => $request->name,
            'type' => $request->type,
            'printer_id' => $request->printer_id,
            'is_active' => $request->is_active ?? $terminal->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terminal updated successfully',
            'data' => $terminal
        ]);
    }

    /**
     * API: Delete terminal
     */
    public function deleteTerminal($id)
    {
        abort_if(!in_array('MultiPOS', restaurant_modules()), 403);

        $currentBranch = branch();
        $terminal = $currentBranch->orderPlaces()->findOrFail($id);

        // Check if terminal is default
        if ($terminal->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default terminal'
            ], 400);
        }

        // Check if terminal has active orders
        $activeOrders = Order::where('order_place_id', $id)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->count();

        if ($activeOrders > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete terminal with active orders'
            ], 400);
        }

        $terminal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Terminal deleted successfully'
        ]);
    }

    /**
     * Get total terminals count for branch
     */
    private function getTotalTerminals($branchId)
    {
        return DB::table('order_places')
            ->where('branch_id', $branchId)
            ->count();
    }

    /**
     * Get active orders count for branch
     */
    private function getActiveOrders($branchId)
    {
        return Order::where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->count();
    }

    /**
     * Get today's revenue for branch
     */
    private function getTodayRevenue($branchId)
    {
        return Order::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('total');
    }

    /**
     * Get total tables count for branch
     */
    private function getTotalTables($branchId)
    {
        return Table::where('branch_id', $branchId)->count();
    }

    /**
     * Get POS terminals for branch
     */
    private function getPOSTerminals($branchId)
    {
        return DB::table('order_places')
            ->leftJoin('printers', 'order_places.printer_id', '=', 'printers.id')
            ->where('order_places.branch_id', $branchId)
            ->select(
                'order_places.*',
                'printers.name as printer_name',
                'printers.is_active as printer_active'
            )
            ->get();
    }
}
