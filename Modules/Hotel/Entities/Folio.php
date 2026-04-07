<?php

namespace Modules\Hotel\Entities;

use App\Models\BaseModel;
use App\Models\Restaurant;
use App\Models\Branch;
use App\Models\User;
use App\Traits\HasBranch;
use App\Models\Order;
use Modules\Hotel\Enums\FolioStatus;
use Modules\Hotel\Enums\FolioLineType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folio extends BaseModel
{
    use HasBranch;

    protected $table = 'hotel_folios';

    protected $guarded = ['id'];

    protected $casts = [
        'status' => FolioStatus::class,
        'total_charges' => 'decimal:2',
        'total_payments' => 'decimal:2',
        'balance' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(Stay::class, 'stay_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function folioLines(): HasMany
    {
        return $this->hasMany(FolioLine::class, 'folio_id');
    }

    public function folioPayments(): HasMany
    {
        return $this->hasMany(FolioPayment::class, 'folio_id');
    }

    /**
     * Generate a unique folio number
     */
    public static function generateFolioNumber($branchId): string
    {
        $prefix = 'FOLIO';
        $year = date('Y');
        $month = date('m');
        
        $lastFolio = self::where('branch_id', $branchId)
            ->where('folio_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastFolio) {
            $lastNumber = (int) substr($lastFolio->folio_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextNumber);
    }

    /**
     * Recalculate folio totals
     */
    public function recalculateTotals(): void
    {
        // Base totals from folio lines and payments
        $this->total_charges = $this->folioLines()->sum('net_amount');
        $this->total_payments = $this->folioPayments()->sum('amount');

        // Default balance = charges - payments
        $balance = $this->total_charges - $this->total_payments;

        // Adjust balance for room-service (F&B) postings that are already PAID in POS.
        //
        // Business rule:
        // - When a POS room-service order is posted to room (bill_to = POST_TO_ROOM)
        //   and the POS order itself is PAID, that amount should NOT remain as an
        //   outstanding balance on the hotel folio.
        // - We detect such lines via FNB_POSTING folio lines that reference
        //   App\Models\Order records with status = 'paid', and subtract their
        //   net_amount from the balance.
        try {
            $roomServiceLines = $this->folioLines()
                ->where('type', FolioLineType::FNB_POSTING)
                ->whereIn('reference_type', [Order::class, 'App\\Models\\Order'])
                ->get();

            if ($roomServiceLines->isNotEmpty()) {
                $orderIds = $roomServiceLines
                    ->pluck('reference_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (! empty($orderIds)) {
                    $paidOrderIds = Order::whereIn('id', $orderIds)
                        ->where('status', 'paid')
                        ->pluck('id')
                        ->all();

                    if (! empty($paidOrderIds)) {
                        $paidRoomServiceTotal = $roomServiceLines
                            ->whereIn('reference_id', $paidOrderIds)
                            ->sum('net_amount');

                        $balance -= $paidRoomServiceTotal;
                    }
                }
            }
        } catch (\Throwable $e) {
            // If anything fails (e.g. Orders table not available), fallback
            // silently to the basic balance logic.
        }

        $this->balance = $balance;
        $this->save();
    }
}
