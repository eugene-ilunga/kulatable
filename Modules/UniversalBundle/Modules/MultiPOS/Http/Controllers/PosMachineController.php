<?php

namespace Modules\MultiPOS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MultiPOS\Entities\PosMachine;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;

class PosMachineController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(!in_array('MultiPOS', restaurant_modules()), 403);
            return $next($request);
        });
    }

    /**
     * Display listing of POS machines
     */
    public function index()
    {

        $currentBranch = branch();
        $machines = PosMachine::with(['branch', 'creator', 'approver'])
            ->forBranch($currentBranch->id)
            ->latest()
            ->get();

        // Usage counters
        $pendingCount = PosMachine::forBranch($currentBranch->id)->pending()->count();
        $activeCount = PosMachine::forBranch($currentBranch->id)->active()->count();
        $totalCount = PosMachine::forBranch($currentBranch->id)->count();
        $limit = optional($currentBranch->restaurant->package)->multipos_limit;

        return view('multipos::machines.index', compact('machines', 'currentBranch', 'pendingCount', 'activeCount', 'totalCount', 'limit'));
    }

    /**
     * Show pending machines for approval
     */
    public function pending()
    {

        $currentBranch = branch();
        $pendingMachines = PosMachine::with(['branch', 'creator'])
            ->pending()
            ->forBranch($currentBranch->id)
            ->latest()
            ->get();

        return view('multipos::machines.pending', compact('pendingMachines', 'currentBranch'));
    }

    /**
     * Approve a pending machine
     */
    public function approve(Request $request, $id)
    {

        $machine = PosMachine::with(['branch', 'creator'])
            ->where('branch_id', branch()->id)
            ->findOrFail($id);

        if ($machine->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Machine is not pending approval'
            ]);
        }

        // Enforce branch-wise MultiPOS limit on approval (active machines)
        $branch = branch();
        $packageLimit = optional($branch->restaurant->package)->multipos_limit;
        if (!is_null($packageLimit) && $packageLimit >= 0) {
            $activeCount = PosMachine::where('branch_id', $branch->id)
                ->where('status', 'active')
                ->count();

            if ($activeCount >= $packageLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'POS machine approval limit reached for this branch (limit: ' . $packageLimit . ').'
                ]);
            }
        }

        $user = Auth::user();
        $machine->activate($user);

        return response()->json([
            'success' => true,
            'message' => 'Machine approved successfully',
            'machine' => $machine->fresh(['approver'])
        ]);
    }

    /**
     * Decline/Disable a machine
     */
    public function disable(Request $request, $id)
    {

        $machine = PosMachine::where('branch_id', branch()->id)->findOrFail($id);

        if ($machine->status === 'declined') {
            return response()->json([
                'success' => false,
                'message' => 'Machine is already declined'
            ]);
        }

        $machine->decline();

        return response()->json([
            'success' => true,
            'message' => 'Machine declined successfully',
            'machine' => $machine->fresh()
        ]);
    }

    /**
     * Update machine details
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'alias' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $machine = PosMachine::where('branch_id', branch()->id)->findOrFail($id);

        $machine->update([
            'alias' => $request->alias,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Machine updated successfully',
            'machine' => $machine->fresh()
        ]);
    }

    /**
     * Rotate machine token
     */
    public function rotateToken($id)
    {

        $machine = PosMachine::where('branch_id', branch()->id)->findOrFail($id);
        $machine->rotateToken();

        return response()->json([
            'success' => true,
            'message' => 'Token rotated successfully. Device will need to re-register.',
            'machine' => $machine->fresh()
        ]);
    }

    /**
     * Delete a machine
     */
    public function destroy($id)
    {

        $machine = PosMachine::where('branch_id', branch()->id)->findOrFail($id);

        // Check if machine has orders
        $ordersCount = $machine->orders()->count();

        if ($ordersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete machine with {$ordersCount} orders. Disable it instead."
            ]);
        }

        $deviceId = $machine->device_id;
        $branchId = $machine->branch_id;
        
        $machine->delete();

        // Cookie deletion logic:
        // Only clear cookie if the current request is from the same browser that had the machine
        // We can't clear cookies from other browsers (they're browser-specific)
        $response = response()->json([
            'success' => true,
            'message' => 'Machine deleted successfully'
        ]);

        if ($deviceId) {
            $cookieName = config('multipos.cookie.name', 'pos_token');
            $currentDeviceId = request()->cookie($cookieName);
            
            // Only clear cookie if:
            // 1. The deleted machine's device_id matches the current browser's cookie
            // 2. There are no other machines with this device_id for this branch
            $otherMachinesCount = PosMachine::where('device_id', $deviceId)
                ->where('branch_id', $branchId)
                ->count();
            
            if ($currentDeviceId === $deviceId && $otherMachinesCount === 0) {
                // This browser had the machine, and it was the last one - clear the cookie
                $response->cookie($cookieName, '', -2628000, '/', null,
                    request()->secure() ? config('multipos.cookie.secure', true) : false,
                    config('multipos.cookie.http_only', true),
                    false,
                    config('multipos.cookie.same_site', 'Strict')
                );
            }
        }

        return $response;
    }

    /**
     * Get machine statistics
     */
    public function statistics($id)
    {

        $machine = PosMachine::where('branch_id', branch()->id)->findOrFail($id);

        $stats = [
            'total_orders' => $machine->orders()->count(),
            'today_orders' => $machine->orders()->whereDate('created_at', today())->count(),
            'today_revenue' => $machine->orders()
                ->whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('total'),
            'last_seen' => $machine->last_seen_at ? $machine->last_seen_at->diffForHumans() : 'Never',
            'status' => $machine->status,
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'machine' => $machine
        ]);
    }

    /**
     * Get all machines for API
     */
    public function apiIndex()
    {

        $currentBranch = branch();
        $machines = PosMachine::with(['branch', 'creator', 'approver'])
            ->forBranch($currentBranch->id)
            ->latest()
            ->get()
            ->map(function ($machine) {
                return [
                    'id' => $machine->id,
                    'alias' => $machine->alias,
                    'public_id' => $machine->public_id,
                    'status' => $machine->status,
                    'last_seen_at' => $machine->last_seen_at,
                    'created_at' => $machine->created_at,
                    'branch' => [
                        'id' => $machine->branch->id,
                        'name' => $machine->branch->name,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $machines
        ]);
    }
}
