<?php

namespace Modules\MultiPOS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MultiPOS\Entities\PosMachine;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Modules\MultiPOS\Events\PosMachineRegistrationRequested;

class ClaimMachineController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(!in_array('MultiPOS', restaurant_modules()), 403);
            return $next($request);
        });
    }

    /**
     * Show the machine registration form
     */
    public function create()
    {
        $user = Auth::user();
        $branches = Branch::where('restaurant_id', $user->restaurant_id)->get();

        return view('multipos::claim', compact('branches'));
    }

    /**
     * Register a new POS machine
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'alias' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $branch = Branch::findOrFail($request->branch_id);
        $restaurant = $branch->restaurant;

        Log::info('POS Registration attempt', [
            'user_id' => $user->id,
            'branch_id' => $request->branch_id,
            'restaurant_id' => $restaurant->id,
            'alias' => $request->alias,
        ]);

        // Check if machine already exists for this browser/device AND branch (cookie-based ONLY, NOT user-based)
        // Use device_id from cookie to link machines from the same browser
        $cookieName = config('multipos.cookie.name', 'pos_token');
        $deviceId = $request->cookie($cookieName);
        $existingMachine = null;

        if ($deviceId) {
            // Check if machine exists for this device_id AND branch combination
            // This allows one machine per branch per browser/device
            $existingMachine = PosMachine::where('device_id', $deviceId)
                ->where('branch_id', $request->branch_id)
                ->first();

            if ($existingMachine) {
                // Machine already exists for this browser/device and branch - restore cookie and redirect
                // Use lifetime cookie (5 years = 1825 days)
                $minutes = config('multipos.cookie.days', 1825) * 24 * 60;

                // Queue the cookie to ensure it's set properly (lifetime cookie)
                Cookie::queue($cookieName, $existingMachine->device_id, $minutes, '/', null,
                    request()->secure() ? config('multipos.cookie.secure', true) : false,
                    config('multipos.cookie.http_only', true),
                    false,
                    config('multipos.cookie.same_site', 'Strict')
                );

                Log::info('POS Cookie restored - existing machine for branch', [
                    'machine_id' => $existingMachine->id,
                    'machine_status' => $existingMachine->status,
                    'branch_id' => $existingMachine->branch_id,
                    'device_id' => $existingMachine->device_id,
                    'cookie_name' => $cookieName,
                ]);

                return redirect()->route('pos.index')
                    ->with('info', __('multipos::messages.registration.already_registered', ['alias' => $existingMachine->alias]));
            }

            // Device ID exists but no machine for this branch - will create new machine with same device_id
            // This allows one machine per branch per browser
            Log::info('POS Device ID exists but no machine for this branch - will create new', [
                'device_id_exists' => true,
                'requested_branch_id' => $request->branch_id,
            ]);
        }

        // Generate device_id if it doesn't exist (first time registration)
        if (!$deviceId) {
            $deviceId = Str::random(64);
        }

        // Enforce branch-wise MultiPOS limit from package (applies to current branch only)
        $packageLimit = optional($restaurant->package)->multipos_limit;
        if (!is_null($packageLimit) && $packageLimit >= 0) {
            // Count active/pending machines only for the current branch
            $currentCount = PosMachine::where('branch_id', $branch->id)
                ->whereIn('status', ['active', 'pending'])
                ->count();

            Log::info('POS Limit Check', [
                'restaurant_id' => $restaurant->id,
                'branch_id' => $branch->id,
                'package_limit' => $packageLimit,
                'current_count' => $currentCount,
            ]);

            if ($currentCount >= $packageLimit) {
                return redirect()->route('pos.index')
                    ->with('error', __('multipos::messages.registration.limit_reached_error', ['limit' => $packageLimit]));
            }
        }

        // Create new POS machine
        // Each machine gets a unique token (required by database constraint)
        // But all machines from the same browser share the same device_id
        $machine = PosMachine::create([
            'branch_id' => $request->branch_id,
            'alias' => $request->alias ?? $branch->name . ' POS ' . time(),
            'public_id' => (string) Str::ulid(),
            'token' => Str::random(64), // Unique token for each machine
            'device_id' => $deviceId, // Shared device_id for machines from same browser
            'status' => config('multipos.approval.auto_approve') ? 'active' : 'pending',
            'created_by' => $user->id,
            'approved_by' => config('multipos.approval.auto_approve') ? $user->id : null,
            'approved_at' => config('multipos.approval.auto_approve') ? now() : null,
        ]);

        // Set secure cookie using queue to ensure it's set properly
        // Store device_id in cookie (not token) to link machines from same browser
        // Use lifetime cookie (5 years = 1825 days)
        $minutes = config('multipos.cookie.days', 1825) * 24 * 60;

        // Queue the cookie to ensure it's set properly (lifetime cookie)
        Cookie::queue($cookieName, $machine->device_id, $minutes, '/', null,
            request()->secure() ? config('multipos.cookie.secure', true) : false,
            config('multipos.cookie.http_only', true),
            false,
            config('multipos.cookie.same_site', 'Strict')
        );

        $message = config('multipos.approval.auto_approve')
            ? __('multipos::messages.registration.registered_success', ['alias' => $machine->alias, 'public_id' => $machine->public_id])
            : __('multipos::messages.registration.registered_pending');

        Log::info('POS Registration successful', [
            'machine_id' => $machine->id,
            'machine_status' => $machine->status,
            'machine_alias' => $machine->alias,
            'device_id' => substr($machine->device_id, 0, 10) . '...', // Log partial device_id for debugging
            'cookie_name' => $cookieName,
        ]);

        // Dispatch event to notify admins if machine requires approval
        if ($machine->status === 'pending') {
            event(new PosMachineRegistrationRequested($machine));
        }

        // Instead of redirect, just show success message and let JavaScript handle reload
        return redirect()->route('pos.index')
            ->with('success', $message)
            ->with('machine', $machine)
            ->with('needsApproval', !config('multipos.approval.auto_approve'))
            ->with('justRegistered', true);
    }

    /**
     * Check if device needs registration
     */
    public function check()
    {
        $deviceId = request()->cookie(config('multipos.cookie.name', 'pos_token'));
        $branchId = branch()->id ?? null;

        if (!$deviceId) {
            return response()->json([
                'needs_registration' => true
            ]);
        }

        // Check for machine with device_id and current branch
        $machine = PosMachine::where('device_id', $deviceId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$machine) {
            return response()->json([
                'needs_registration' => true,
                'reason' => 'Machine not found for this branch'
            ]);
        }

        if ($machine->status === 'declined') {
            return response()->json([
                'needs_registration' => true,
                'reason' => 'Machine declined'
            ]);
        }

        return response()->json([
            'needs_registration' => false,
            'machine' => [
                'id' => $machine->id,
                'alias' => $machine->alias,
                'public_id' => $machine->public_id,
                'status' => $machine->status,
                'last_seen_at' => $machine->last_seen_at,
            ]
        ]);
    }

    /**
     * Check branch limit for POS machines
     */
    public function checkBranchLimit(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branch = Branch::findOrFail($request->branch_id);
        $restaurant = $branch->restaurant;
        $packageLimit = optional($restaurant->package)->multipos_limit;

        if (is_null($packageLimit) || $packageLimit < 0) {
            // No limit or unlimited
            return response()->json([
                'limit_reached' => false,
                'limit' => null,
                'current_count' => 0,
            ]);
        }

        // Count active/pending machines for this branch
        $currentCount = PosMachine::where('branch_id', $branch->id)
            ->whereIn('status', ['active', 'pending'])
            ->count();

        $limitReached = $currentCount >= $packageLimit;

        return response()->json([
            'limit_reached' => $limitReached,
            'limit' => $packageLimit,
            'current_count' => $currentCount,
            'message' => $limitReached
                ? __('multipos::messages.registration.limit_reached.message', ['limit' => $packageLimit])
                : null,
        ]);
    }
}
