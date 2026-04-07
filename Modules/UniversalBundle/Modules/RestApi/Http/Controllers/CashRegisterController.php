<?php

namespace Modules\RestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\CashRegister\Entities\CashRegister;
use Modules\CashRegister\Entities\CashRegisterCount;
use Modules\CashRegister\Entities\CashRegisterSession;
use Modules\CashRegister\Entities\CashRegisterTransaction;
use Modules\CashRegister\Entities\Denomination;
use Modules\RestApi\Http\Requests\CashTransactionRequest;
use Modules\RestApi\Http\Requests\CloseSessionRequest;
use Modules\RestApi\Http\Requests\OpenSessionRequest;
use Modules\RestApi\Traits\ApiResponse;

/**
 * Cash Register Controller - API endpoints for managing cash registers, sessions, transactions, and denominations.
 *
 * @package Modules\RestApi\Http\Controllers
 * @since   1.0.0
 * @api     /api/application-integration/pos/cash-register/*
 */
class CashRegisterController extends Controller
{
    use ApiResponse;

    private $branch;
    private $restaurant;

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
    }

    private function guardBranch(): ?JsonResponse
    {
        if (! $this->branch) {
            return response()->json([
                'success' => false,
                'message' => __('applicationintegration::messages.plan_not_allowed'),
            ], 400);
        }

        return null;
    }

    // ==================== REGISTERS ====================

    /**
     * List all cash registers for the current branch.
     *
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/registers
     */
    public function getRegisters(): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        if (! Schema::hasTable('cash_registers')) {
            return response()->json([
                'success' => false,
                'message' => 'Cash register module not installed',
            ], 404);
        }

        $registers = CashRegister::where('branch_id', $this->branch->id)
            ->orderBy('name')
            ->get()
            ->map(function ($register) {
                return [
                    'id' => $register->id,
                    'name' => $register->name,
                    'is_active' => (bool) $register->is_active,
                    'branch_id' => $register->branch_id,
                    'restaurant_id' => $register->restaurant_id,
                    'created_at' => $register->created_at?->toIso8601String(),
                    'updated_at' => $register->updated_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $registers,
        ]);
    }

    /**
     * Get a specific cash register.
     *
     * @param int $id
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/registers/{id}
     */
    public function getRegister(int $id): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $register = CashRegister::where('branch_id', $this->branch->id)
            ->find($id);

        if (! $register) {
            return response()->json([
                'success' => false,
                'message' => 'Cash register not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $register->id,
                'name' => $register->name,
                'is_active' => (bool) $register->is_active,
                'branch_id' => $register->branch_id,
                'restaurant_id' => $register->restaurant_id,
                'created_at' => $register->created_at?->toIso8601String(),
                'updated_at' => $register->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new cash register.
     *
     * @param Request $request
     * @return JsonResponse
     * @api POST /api/application-integration/pos/cash-register/registers
     */
    public function createRegister(Request $request): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $register = CashRegister::create([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
            'branch_id' => $this->branch->id,
            'restaurant_id' => $this->restaurant->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cash register created successfully',
            'data' => [
                'id' => $register->id,
                'name' => $register->name,
                'is_active' => (bool) $register->is_active,
                'branch_id' => $register->branch_id,
                'restaurant_id' => $register->restaurant_id,
            ],
        ], 201);
    }

    /**
     * Update an existing cash register.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     * @api PUT /api/application-integration/pos/cash-register/registers/{id}
     */
    public function updateRegister(int $id, Request $request): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $register = CashRegister::where('branch_id', $this->branch->id)
            ->find($id);

        if (! $register) {
            return response()->json([
                'success' => false,
                'message' => 'Cash register not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['name'])) {
            $register->name = $validated['name'];
        }
        if (isset($validated['is_active'])) {
            $register->is_active = $validated['is_active'];
        }

        $register->save();

        return response()->json([
            'success' => true,
            'message' => 'Cash register updated successfully',
            'data' => [
                'id' => $register->id,
                'name' => $register->name,
                'is_active' => (bool) $register->is_active,
            ],
        ]);
    }

    /**
     * Deactivate a cash register.
     *
     * @param int $id
     * @return JsonResponse
     * @api DELETE /api/application-integration/pos/cash-register/registers/{id}
     */
    public function deleteRegister(int $id): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $register = CashRegister::where('branch_id', $this->branch->id)
            ->find($id);

        if (! $register) {
            return response()->json([
                'success' => false,
                'message' => 'Cash register not found',
            ], 404);
        }

        // Check for active sessions
        $activeSession = CashRegisterSession::where('cash_register_id', $register->id)
            ->where('status', 'open')
            ->first();

        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate register with an active session',
            ], 409);
        }

        $register->is_active = false;
        $register->save();

        return response()->json([
            'success' => true,
            'message' => 'Cash register deactivated successfully',
        ]);
    }

    // ==================== SESSIONS ====================

    /**
     * List cash register sessions with filters.
     *
     * @param Request $request
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/sessions
     */
    public function getSessions(Request $request): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        if (! Schema::hasTable('cash_register_sessions')) {
            return response()->json([
                'success' => false,
                'message' => 'Cash register module not installed',
            ], 404);
        }

        $query = CashRegisterSession::where('branch_id', $this->branch->id)
            ->with(['register', 'openedBy', 'closer']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('cash_register_id')) {
            $query->where('cash_register_id', $request->cash_register_id);
        }
        if ($request->has('opened_by')) {
            $query->where('opened_by', $request->opened_by);
        }
        if ($request->has('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        $sessions = $query->orderBy('opened_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $sessions->map(function ($session) {
                return $this->formatSession($session);
            }),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ],
        ]);
    }

    /**
     * Get the current user's active session.
     *
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/sessions/active
     */
    public function getActiveSession(): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $userId = auth()->id();

        $session = CashRegisterSession::where('branch_id', $this->branch->id)
            ->where('opened_by', $userId)
            ->where('status', 'open')
            ->with(['register', 'openedBy', 'transactions'])
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No active session found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatSession($session, true),
        ]);
    }

    /**
     * Get a specific session with transactions.
     *
     * @param int $id
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/sessions/{id}
     */
    public function getSession(int $id): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $session = CashRegisterSession::where('branch_id', $this->branch->id)
            ->with(['register', 'openedBy', 'closer', 'transactions'])
            ->find($id);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatSession($session, true),
        ]);
    }

    /**
     * Open a new cash register session.
     *
     * @param OpenSessionRequest $request
     * @return JsonResponse
     * @api POST /api/application-integration/pos/cash-register/sessions/open
     */
    public function openSession(OpenSessionRequest $request): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $userId = auth()->id();

        // Check if user already has an active session
        $existingSession = CashRegisterSession::where('branch_id', $this->branch->id)
            ->where('opened_by', $userId)
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active session',
                'data' => ['session_id' => $existingSession->id],
            ], 409);
        }

        // Verify register exists and is active
        $register = CashRegister::where('branch_id', $this->branch->id)
            ->where('id', $request->cash_register_id)
            ->where('is_active', true)
            ->first();

        if (! $register) {
            return response()->json([
                'success' => false,
                'message' => 'Cash register not found or inactive',
            ], 404);
        }

        // Check if register already has an active session
        $registerSession = CashRegisterSession::where('cash_register_id', $register->id)
            ->where('status', 'open')
            ->first();

        if ($registerSession) {
            return response()->json([
                'success' => false,
                'message' => 'This register already has an active session',
            ], 409);
        }

        $session = CashRegisterSession::create([
            'cash_register_id' => $register->id,
            'restaurant_id' => $this->restaurant->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $userId,
            'opened_at' => now(),
            'opening_float' => $request->opening_float ?? 0,
            'status' => 'open',
        ]);

        // Record opening float as first transaction if provided
        if ($request->opening_float > 0) {
            CashRegisterTransaction::create([
                'cash_register_session_id' => $session->id,
                'restaurant_id' => $this->restaurant->id,
                'branch_id' => $this->branch->id,
                'happened_at' => now(),
                'type' => 'opening_float',
                'amount' => $request->opening_float,
                'running_amount' => $request->opening_float,
                'reason' => $request->note ?? 'Opening float',
                'created_by' => $userId,
            ]);
        }

        $session->load(['register', 'openedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Session opened successfully',
            'data' => $this->formatSession($session),
        ], 201);
    }

    /**
     * Close a cash register session.
     *
     * @param int $id
     * @param CloseSessionRequest $request
     * @return JsonResponse
     * @api POST /api/application-integration/pos/cash-register/sessions/{id}/close
     */
    public function closeSession(int $id, CloseSessionRequest $request): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $session = CashRegisterSession::where('branch_id', $this->branch->id)
            ->where('status', 'open')
            ->find($id);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or already closed',
            ], 404);
        }

        $userId = auth()->id();

        // Calculate expected cash from transactions
        $expectedCash = $this->calculateExpectedCash($session);

        // Use provided expected_cash or calculated value
        $finalExpectedCash = $request->expected_cash ?? $expectedCash;
        $countedCash = $request->counted_cash;
        $discrepancy = $countedCash - $finalExpectedCash;

        DB::beginTransaction();
        try {
            // Update session
            $session->update([
                'closed_by' => $userId,
                'closed_at' => now(),
                'expected_cash' => $finalExpectedCash,
                'counted_cash' => $countedCash,
                'discrepancy' => $discrepancy,
                'status' => 'closed',
                'closing_note' => $request->closing_note,
            ]);

            // Record denomination counts if provided
            if ($request->has('denomination_counts') && is_array($request->denomination_counts)) {
                foreach ($request->denomination_counts as $count) {
                    if (isset($count['denomination_id']) && isset($count['count'])) {
                        $denomination = Denomination::find($count['denomination_id']);
                        if ($denomination) {
                            CashRegisterCount::create([
                                'cash_register_session_id' => $session->id,
                                'cash_denomination_id' => $count['denomination_id'],
                                'count' => $count['count'],
                                'subtotal' => $denomination->value * $count['count'],
                            ]);
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to close session', [
                'session_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to close session',
            ], 500);
        }

        $session->load(['register', 'openedBy', 'closer']);

        return response()->json([
            'success' => true,
            'message' => 'Session closed successfully',
            'data' => [
                'id' => $session->id,
                'expected_cash' => (float) $finalExpectedCash,
                'counted_cash' => (float) $countedCash,
                'discrepancy' => (float) $discrepancy,
                'status' => 'closed',
                'closed_at' => $session->closed_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get session summary/report.
     *
     * @param int $id
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/sessions/{id}/summary
     */
    public function getSessionSummary(int $id): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $session = CashRegisterSession::where('branch_id', $this->branch->id)
            ->with(['register', 'openedBy', 'closer', 'transactions'])
            ->find($id);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $transactions = $session->transactions ?? collect();

        $summary = [
            'session' => $this->formatSession($session),
            'totals' => [
                'opening_float' => (float) $session->opening_float,
                'cash_sales' => (float) $transactions->where('type', 'cash_sale')->sum('amount'),
                'cash_in' => (float) $transactions->where('type', 'cash_in')->sum('amount'),
                'cash_out' => (float) $transactions->where('type', 'cash_out')->sum('amount'),
                'safe_drops' => (float) $transactions->where('type', 'safe_drop')->sum('amount'),
                'refunds' => (float) $transactions->where('type', 'refund')->sum('amount'),
            ],
            'expected_cash' => (float) ($session->expected_cash ?: $this->calculateExpectedCash($session)),
            'counted_cash' => (float) $session->counted_cash,
            'discrepancy' => (float) $session->discrepancy,
            'transactions_count' => $transactions->count(),
        ];

        // Calculate running total
        $summary['totals']['running_total'] =
            $summary['totals']['opening_float'] +
            $summary['totals']['cash_sales'] +
            $summary['totals']['cash_in'] -
            $summary['totals']['cash_out'] -
            $summary['totals']['safe_drops'] -
            $summary['totals']['refunds'];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    // ==================== TRANSACTIONS ====================

    /**
     * List transactions for a session.
     *
     * @param int $sessionId
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/sessions/{id}/transactions
     */
    public function getTransactions(int $sessionId): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $session = CashRegisterSession::where('branch_id', $this->branch->id)
            ->find($sessionId);

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        $transactions = CashRegisterTransaction::where('cash_register_session_id', $sessionId)
            ->orderBy('happened_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'running_amount' => (float) $transaction->running_amount,
                    'reason' => $transaction->reason,
                    'reference' => $transaction->reference,
                    'happened_at' => $transaction->happened_at?->toIso8601String(),
                    'created_by' => $transaction->created_by,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Record a cash-in transaction.
     *
     * @param CashTransactionRequest $request
     * @return JsonResponse
     * @api POST /api/application-integration/pos/cash-register/transactions/cash-in
     */
    public function recordCashIn(CashTransactionRequest $request): JsonResponse
    {
        return $this->recordTransaction($request, 'cash_in');
    }

    /**
     * Record a cash-out transaction.
     *
     * @param CashTransactionRequest $request
     * @return JsonResponse
     * @api POST /api/application-integration/pos/cash-register/transactions/cash-out
     */
    public function recordCashOut(CashTransactionRequest $request): JsonResponse
    {
        return $this->recordTransaction($request, 'cash_out');
    }

    /**
     * Record a safe drop transaction.
     *
     * @param CashTransactionRequest $request
     * @return JsonResponse
     * @api POST /api/application-integration/pos/cash-register/transactions/safe-drop
     */
    public function recordSafeDrop(CashTransactionRequest $request): JsonResponse
    {
        return $this->recordTransaction($request, 'safe_drop');
    }

    /**
     * Record a cash register transaction.
     *
     * @param CashTransactionRequest $request
     * @param string $type
     * @return JsonResponse
     */
    private function recordTransaction(CashTransactionRequest $request, string $type): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $userId = auth()->id();

        // Find user's active session
        $session = CashRegisterSession::where('branch_id', $this->branch->id)
            ->where('opened_by', $userId)
            ->where('status', 'open')
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No active session found. Please open a session first.',
            ], 404);
        }

        // Calculate running amount
        $lastTransaction = CashRegisterTransaction::where('cash_register_session_id', $session->id)
            ->orderBy('id', 'desc')
            ->first();

        $runningAmount = $lastTransaction ? $lastTransaction->running_amount : $session->opening_float;

        // Adjust running amount based on transaction type
        if (in_array($type, ['cash_in'])) {
            $runningAmount += $request->amount;
        } else {
            // cash_out, safe_drop subtract from running amount
            $runningAmount -= $request->amount;
        }

        $transaction = CashRegisterTransaction::create([
            'cash_register_session_id' => $session->id,
            'restaurant_id' => $this->restaurant->id,
            'branch_id' => $this->branch->id,
            'happened_at' => now(),
            'type' => $type,
            'amount' => $request->amount,
            'running_amount' => $runningAmount,
            'reason' => $request->reason,
            'reference' => $request->reference,
            'created_by' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction recorded successfully',
            'data' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => (float) $transaction->amount,
                'running_amount' => (float) $transaction->running_amount,
                'session_id' => $session->id,
            ],
        ], 201);
    }

    // ==================== DENOMINATIONS ====================

    /**
     * List active denominations.
     *
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/denominations
     */
    public function getDenominations(): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        if (! Schema::hasTable('denominations')) {
            return response()->json([
                'success' => false,
                'message' => 'Denominations table not found',
            ], 404);
        }

        $denominations = Denomination::where(function ($query) {
            $query->where('branch_id', $this->branch->id)
                ->orWhereNull('branch_id');
        })
            ->where(function ($query) {
                $query->where('restaurant_id', $this->restaurant->id)
                    ->orWhereNull('restaurant_id');
            })
            ->where('is_active', true)
            ->orderBy('value', 'asc')
            ->get()
            ->map(function ($denomination) {
                return [
                    'id' => $denomination->id,
                    'uuid' => $denomination->uuid,
                    'name' => $denomination->name,
                    'value' => (float) $denomination->value,
                    'type' => $denomination->type,
                    'type_label' => $denomination->type_label,
                    'is_active' => (bool) $denomination->is_active,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $denominations,
        ]);
    }

    /**
     * Get a specific denomination.
     *
     * @param string $uuid
     * @return JsonResponse
     * @api GET /api/application-integration/pos/cash-register/denominations/{uuid}
     */
    public function getDenomination(string $uuid): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $denomination = Denomination::where('uuid', $uuid)
            ->where(function ($query) {
                $query->where('branch_id', $this->branch->id)
                    ->orWhereNull('branch_id');
            })
            ->where(function ($query) {
                $query->where('restaurant_id', $this->restaurant->id)
                    ->orWhereNull('restaurant_id');
            })
            ->first();

        if (! $denomination) {
            return response()->json([
                'success' => false,
                'message' => 'Denomination not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $denomination->id,
                'uuid' => $denomination->uuid,
                'name' => $denomination->name,
                'value' => (float) $denomination->value,
                'type' => $denomination->type,
                'type_label' => $denomination->type_label,
                'description' => $denomination->description,
                'is_active' => (bool) $denomination->is_active,
                'created_at' => $denomination->created_at?->toIso8601String(),
                'updated_at' => $denomination->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new denomination.
     *
     * @param Request $request
     * @return JsonResponse
     * @api POST /api/application-integration/pos/cash-register/denominations
     */
    public function createDenomination(Request $request): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $validated = $request->validate(Denomination::getCreateRules());

        $denomination = Denomination::create([
            'name' => $validated['name'],
            'value' => $validated['value'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'branch_id' => $this->branch->id,
            'restaurant_id' => $this->restaurant->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Denomination created successfully',
            'data' => [
                'id' => $denomination->id,
                'uuid' => $denomination->uuid,
                'name' => $denomination->name,
                'value' => (float) $denomination->value,
                'type' => $denomination->type,
                'is_active' => (bool) $denomination->is_active,
            ],
        ], 201);
    }

    /**
     * Update a denomination.
     *
     * @param string $uuid
     * @param Request $request
     * @return JsonResponse
     * @api PUT /api/application-integration/pos/cash-register/denominations/{uuid}
     */
    public function updateDenomination(string $uuid, Request $request): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $denomination = Denomination::where('uuid', $uuid)
            ->where('branch_id', $this->branch->id)
            ->first();

        if (! $denomination) {
            return response()->json([
                'success' => false,
                'message' => 'Denomination not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'value' => 'nullable|numeric|min:0.01|max:999999.99',
            'type' => 'nullable|in:coin,note,bill',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $denomination->update(array_filter($validated, fn($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Denomination updated successfully',
            'data' => [
                'id' => $denomination->id,
                'uuid' => $denomination->uuid,
                'name' => $denomination->name,
                'value' => (float) $denomination->value,
                'type' => $denomination->type,
                'is_active' => (bool) $denomination->is_active,
            ],
        ]);
    }

    /**
     * Soft delete a denomination.
     *
     * @param string $uuid
     * @return JsonResponse
     * @api DELETE /api/application-integration/pos/cash-register/denominations/{uuid}
     */
    public function deleteDenomination(string $uuid): JsonResponse
    {
        if ($resp = $this->guardBranch()) {
            return $resp;
        }

        $denomination = Denomination::where('uuid', $uuid)
            ->where('branch_id', $this->branch->id)
            ->first();

        if (! $denomination) {
            return response()->json([
                'success' => false,
                'message' => 'Denomination not found',
            ], 404);
        }

        $denomination->delete();

        return response()->json([
            'success' => true,
            'message' => 'Denomination deleted successfully',
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Format a session for API response.
     *
     * @param CashRegisterSession $session
     * @param bool $includeTransactions
     * @return array
     */
    private function formatSession(CashRegisterSession $session, bool $includeTransactions = false): array
    {
        $transactions = $session->transactions ?? collect();

        $data = [
            'id' => $session->id,
            'cash_register' => $session->register ? [
                'id' => $session->register->id,
                'name' => $session->register->name,
            ] : null,
            'opened_by' => $session->openedBy ? [
                'id' => $session->openedBy->id,
                'name' => $session->openedBy->name,
            ] : null,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'opening_float' => (float) $session->opening_float,
            'closed_by' => $session->closer ? [
                'id' => $session->closer->id,
                'name' => $session->closer->name,
            ] : null,
            'closed_at' => $session->closed_at?->toIso8601String(),
            'expected_cash' => (float) $session->expected_cash,
            'counted_cash' => (float) $session->counted_cash,
            'discrepancy' => (float) $session->discrepancy,
            'status' => $session->status,
            'closing_note' => $session->closing_note,
            'transactions_count' => $transactions->count(),
            'cash_sales_total' => (float) $transactions->where('type', 'cash_sale')->sum('amount'),
            'cash_in_total' => (float) $transactions->where('type', 'cash_in')->sum('amount'),
            'cash_out_total' => (float) $transactions->where('type', 'cash_out')->sum('amount'),
            'safe_drops_total' => (float) $transactions->where('type', 'safe_drop')->sum('amount'),
            'running_total' => (float) ($transactions->last()?->running_amount ?? $session->opening_float),
        ];

        if ($includeTransactions) {
            $data['transactions'] = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'running_amount' => (float) $transaction->running_amount,
                    'reason' => $transaction->reason,
                    'reference' => $transaction->reference,
                    'happened_at' => $transaction->happened_at?->toIso8601String(),
                ];
            })->values()->all();
        }

        return $data;
    }

    /**
     * Calculate expected cash for a session.
     *
     * @param CashRegisterSession $session
     * @return float
     */
    private function calculateExpectedCash(CashRegisterSession $session): float
    {
        $transactions = $session->transactions ?? CashRegisterTransaction::where('cash_register_session_id', $session->id)->get();

        $expected = (float) $session->opening_float;

        foreach ($transactions as $transaction) {
            switch ($transaction->type) {
                case 'cash_sale':
                case 'cash_in':
                case 'opening_float':
                    $expected += (float) $transaction->amount;
                    break;
                case 'cash_out':
                case 'safe_drop':
                case 'refund':
                    $expected -= (float) $transaction->amount;
                    break;
            }
        }

        return $expected;
    }
}
