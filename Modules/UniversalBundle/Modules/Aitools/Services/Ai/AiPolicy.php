<?php

namespace Modules\Aitools\Services\Ai;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AiPolicy
{
    /**
     * Check if AI is enabled for the restaurant
     */
    public function isEnabled(Restaurant $restaurant): bool
    {
        return (bool) $restaurant->ai_enabled;
    }

    /**
     * Check if user role is allowed
     */
    public function isRoleAllowed(User $user, Restaurant $restaurant): bool
    {
        $allowedRoles = $restaurant->ai_allowed_roles ?? [];
        
        // Ensure it's an array (handle JSON string from database)
        if (is_string($allowedRoles)) {
            $allowedRoles = json_decode($allowedRoles, true) ?? [];
        }
        
        if (!is_array($allowedRoles) || empty($allowedRoles)) {
            // Default: allow owner and admin
            $allowedRoles = ['owner', 'admin'];
        }

        $userRole = $this->getUserRole($user, $restaurant);
        
        return in_array($userRole, $allowedRoles);
    }

    /**
     * Check monthly package token limit
     */
    public function checkMonthlyLimit(Restaurant $restaurant): bool
    {
        // Load package relationship
        $restaurant->load('package');
        
        if (!$restaurant->package) {
            return true; // No package means no limit
        }

        $monthlyLimit = $restaurant->package->ai_monthly_token_limit ?? -1;
        
        // -1 means unlimited
        if ($monthlyLimit == -1) {
            return true;
        }

        // Check if we need to reset monthly count
        $this->checkAndResetMonthlyCount($restaurant);

        $used = $restaurant->ai_monthly_tokens_used ?? 0;
        
        return $used < $monthlyLimit;
    }

    /**
     * Get remaining monthly tokens
     */
    public function getRemainingMonthlyTokens(Restaurant $restaurant): int
    {
        // Load package relationship
        $restaurant->load('package');
        
        if (!$restaurant->package) {
            return 999999; // No package means unlimited
        }

        $monthlyLimit = $restaurant->package->ai_monthly_token_limit ?? -1;
        
        // -1 means unlimited
        if ($monthlyLimit == -1) {
            return 999999;
        }

        // Check if we need to reset monthly count
        $this->checkAndResetMonthlyCount($restaurant);

        $used = $restaurant->ai_monthly_tokens_used ?? 0;
        
        return max(0, $monthlyLimit - $used);
    }

    /**
     * Check and reset monthly token count if needed
     */
    private function checkAndResetMonthlyCount(Restaurant $restaurant): void
    {
        $resetAt = $restaurant->ai_monthly_reset_at;
        
        // If no reset date set, set it to next month from now
        if (!$resetAt) {
            $restaurant->ai_monthly_reset_at = now()->addMonth()->startOfMonth();
            $restaurant->ai_monthly_tokens_used = 0;
            $restaurant->saveQuietly();
            return;
        }

        // If reset date has passed, save current month's usage to history and reset the count
        if (now()->greaterThanOrEqualTo($resetAt)) {
            $currentMonth = now()->subMonth()->format('Y-m');
            $tokensUsed = $restaurant->ai_monthly_tokens_used ?? 0;
            $tokenLimit = $restaurant->package->ai_monthly_token_limit ?? -1;
            
            // Save to history if tokens were used
            if ($tokensUsed > 0) {
                DB::table('ai_token_usage_history')->updateOrInsert(
                    [
                        'restaurant_id' => $restaurant->id,
                        'month' => $currentMonth,
                    ],
                    [
                        'tokens_used' => $tokensUsed,
                        'token_limit' => $tokenLimit,
                        'updated_at' => now(),
                    ]
                );
            }
            
            $restaurant->ai_monthly_tokens_used = 0;
            $restaurant->ai_monthly_reset_at = now()->addMonth()->startOfMonth();
            $restaurant->saveQuietly();
        }
    }

    /**
     * Get remaining tokens (monthly limit based on package)
     */
    public function getRemainingTokens(Restaurant $restaurant): int
    {
        // Monthly token limit is enforced based on package assignment
        return $this->getRemainingMonthlyTokens($restaurant);
    }

    /**
     * Check if user can access AI
     */
    public function canAccess(User $user, Restaurant $restaurant): array
    {
        if (!$this->isEnabled($restaurant)) {
            return [
                'allowed' => false,
                'reason' => 'AI is not enabled for this restaurant',
            ];
        }

        if (!$this->isRoleAllowed($user, $restaurant)) {
            return [
                'allowed' => false,
                'reason' => 'Your role does not have access to AI',
            ];
        }

        if (!$this->checkMonthlyLimit($restaurant)) {
            return [
                'allowed' => false,
                'reason' => 'Monthly token limit reached. Limit will reset when package renews.',
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $this->getRemainingTokens($restaurant),
        ];
    }

    /**
     * Get user role name (normalized to match allowed roles)
     */
    private function getUserRole(User $user, Restaurant $restaurant): string
    {
        $role = $user->roles->first();
        
        if (!$role) {
            return 'cashier';
        }

        // Use display_name if available (preferred method)
        $displayName = $role->display_name ?? null;
        
        if ($displayName) {
            // Normalize display name to lowercase for matching
            $normalized = strtolower(trim($displayName));
            
            // Map actual role display names to allowed role names
            $roleMapping = [
                'admin' => 'admin',
                'branch head' => 'admin', // Branch Head has admin-level access
                'waiter' => 'cashier',
                'chef' => 'manager',
                'manager' => 'manager',
                'cashier' => 'cashier',
                'owner' => 'owner',
            ];
            
            return $roleMapping[$normalized] ?? 'cashier'; // Default to cashier if unknown
        }

        // Fallback: extract from name field (e.g., "Admin_123" -> "admin")
        $roleName = strtolower($role->name);
        
        // Extract role name (remove restaurant ID suffix if present)
        if (str_contains($roleName, '_')) {
            $parts = explode('_', $roleName);
            $baseName = trim($parts[0]); // Get "admin" from "admin_123"
            
            // Map to allowed role names
            $roleMapping = [
                'admin' => 'admin',
                'branch head' => 'admin',
                'waiter' => 'cashier',
                'chef' => 'manager',
                'manager' => 'manager',
                'cashier' => 'cashier',
                'owner' => 'owner',
            ];
            
            return $roleMapping[$baseName] ?? 'cashier';
        }

        // If no underscore, use the name directly (normalized)
        $normalized = strtolower(trim($roleName));
        $roleMapping = [
            'admin' => 'admin',
            'branch head' => 'admin',
            'waiter' => 'cashier',
            'chef' => 'manager',
            'manager' => 'manager',
            'cashier' => 'cashier',
            'owner' => 'owner',
        ];
        
        return $roleMapping[$normalized] ?? 'cashier';
    }
}

