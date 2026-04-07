<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MultiPosIntegrationController extends Controller
{
    /**
     * Register a POS device (MultiPOS) for the current restaurant/branch.
     */
    public function register(Request $request)
    {
        if (! $this->multiPosEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'MultiPOS module not enabled',
            ], 404);
        }

        $user = $request->user();

        $data = $request->validate([
            'branch_id' => 'nullable|integer',
            'alias' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:50',
        ]);

        $branchId = $data['branch_id'] ?? $user->branch_id;
        if (! $branchId) {
            return response()->json(['success' => false, 'message' => 'branch_id is required'], 422);
        }

        $branch = Branch::where('id', $branchId)
            ->where('restaurant_id', $user->restaurant_id)
            ->first();

        if (! $branch) {
            return response()->json(['success' => false, 'message' => 'Branch not found for this restaurant'], 404);
        }

        $deviceId = $data['device_id'] ?? Str::random(64);

        /** @var \Modules\MultiPOS\Entities\PosMachine $existing */
        $existing = \Modules\MultiPOS\Entities\PosMachine::where('branch_id', $branch->id)
            ->where('device_id', $deviceId)
            ->first();

        if ($existing) {
            $existing->updateQuietly(['last_seen_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Device already registered',
                'data' => $this->formatMachine($existing),
            ]);
        }

        // enforce package limit
        $limit = optional($branch->restaurant->package)->multipos_limit;
        $currentCount = \Modules\MultiPOS\Entities\PosMachine::where('branch_id', $branch->id)
            ->whereIn('status', ['active', 'pending'])
            ->count();

        if (! is_null($limit) && $limit >= 0 && $currentCount >= $limit) {
            return response()->json([
                'success' => false,
                'message' => 'Branch limit reached',
                'limit' => $limit,
                'current_count' => $currentCount,
            ], 400);
        }

        $status = config('multipos.approval.auto_approve') ? 'active' : 'pending';
        $tokenLength = config('multipos.security.token_length', 64);

        $machine = \Modules\MultiPOS\Entities\PosMachine::create([
            'branch_id' => $branch->id,
            'alias' => $data['alias'] ?? ($branch->name . ' POS ' . now()->format('Ymd_His')),
            'public_id' => (string) Str::ulid(),
            'token' => Str::random($tokenLength),
            'device_id' => $deviceId,
            'status' => $status,
            'created_by' => $user->id,
            'approved_by' => $status === 'active' ? $user->id : null,
            'approved_at' => $status === 'active' ? now() : null,
            'last_seen_at' => now(),
        ]);

        Log::info('MultiPOS API device registered', [
            'machine_id' => $machine->id,
            'status' => $machine->status,
            'branch_id' => $branch->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $status === 'active' ? 'Device registered' : 'Device pending approval',
            'data' => $this->formatMachine($machine),
        ]);
    }

    /**
     * Check device registration status by device_id (and branch).
     */
    public function check(Request $request)
    {
        if (! $this->multiPosEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'MultiPOS module not enabled',
            ], 404);
        }

        $user = $request->user();
        $data = $request->validate([
            'device_id' => 'required|string',
            'branch_id' => 'nullable|integer',
        ]);

        $branchId = $data['branch_id'] ?? $user->branch_id;

        $machine = \Modules\MultiPOS\Entities\PosMachine::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('device_id', $data['device_id'])
            ->first();

        if (! $machine) {
            return response()->json([
                'success' => true,
                'needs_registration' => true,
                'message' => 'No device found for this branch',
            ]);
        }

        $needsRegistration = $machine->status === 'declined';

        return response()->json([
            'success' => true,
            'needs_registration' => $needsRegistration,
            'data' => $this->formatMachine($machine),
        ]);
    }

    private function multiPosEnabled(): bool
    {
        return function_exists('module_enabled') && module_enabled('MultiPOS');
    }

    private function formatMachine(\Modules\MultiPOS\Entities\PosMachine $machine): array
    {
        return [
            'id' => $machine->id,
            'branch_id' => $machine->branch_id,
            'alias' => $machine->alias,
            'public_id' => $machine->public_id,
            'token' => $machine->token,
            'status' => $machine->status,
            'device_id' => $machine->device_id,
            'approved_at' => $machine->approved_at,
            'last_seen_at' => $machine->last_seen_at,
        ];
    }
}

