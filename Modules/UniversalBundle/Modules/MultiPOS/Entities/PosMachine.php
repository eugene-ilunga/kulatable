<?php

namespace Modules\MultiPOS\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use App\Models\Branch;
use App\Models\User;
use App\Models\Order;

class PosMachine extends BaseModel
{
    use HasFactory;

    protected $table = 'pos_machines';
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'last_seen_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Generate a new machine token
     */
    public function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Generate a public ID (ULID)
     */
    public function generatePublicId(): string
    {
        return (string) Str::ulid();
    }

    /**
     * Check if machine is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if machine is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if machine is declined
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    /**
     * Activate the machine
     */
    public function activate(User $user): void
    {
        $this->update([
            'status' => 'active',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Decline the machine
     */
    public function decline(): void
    {
        $this->update([
            'status' => 'declined',
            // Don't rotate token on decline - user needs to re-register
            // Token rotation would prevent them from seeing their declined status
        ]);
    }

    /**
     * Update last seen timestamp
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Rotate the machine token
     */
    public function rotateToken(): void
    {
        $this->update(['token' => $this->generateToken()]);
    }

    /**
     * Relationship: Machine belongs to a branch
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship: Machine creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withoutGlobalScope(\App\Scopes\BranchScope::class);
    }

    /**
     * Relationship: Machine approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')
            ->withoutGlobalScope(\App\Scopes\BranchScope::class);
    }

    /**
     * Relationship: Orders from this machine
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'pos_machine_id');
    }

    /**
     * Scope: Only active machines
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Only pending machines
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Only declined machines
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Scope: For a specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Note: We don't clear cookies in the model's boot method because:
        // 1. Cookies are browser-specific and can't be cleared from a different browser
        // 2. When admin deletes a machine from another browser, we can't clear that browser's cookie
        // 3. The middleware will naturally detect the missing machine and prompt for re-registration
        // 4. If the user is on the same browser, they'll be prompted to re-register when they visit POS
    }
}

