<?php

namespace Modules\RestApi\Support\Safety;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SafetyGuard
{
    private ?int $branchId;
    private ?int $restaurantId;

    public function __construct(?int $branchId, ?int $restaurantId)
    {
        $this->branchId = $branchId;
        $this->restaurantId = $restaurantId;
    }

    /**
    * Allowed order_status values from enum + app enum.
    */
    public function allowedOrderStatuses(): array
    {
        $values = [
            'placed',
            'confirmed',
            'preparing',
            'food_ready',
            'ready_for_pickup',
            'out_for_delivery',
            'served',
            'delivered',
            'cancelled',
        ];

        if (class_exists(\App\Enums\OrderStatus::class)) {
            $values = array_merge(
                $values,
                collect(\App\Enums\OrderStatus::cases())->map->value->all()
            );
        }

        $values = array_unique(array_filter($values));

        return $values;
    }

    /**
    * Allowed status column values (broader legacy set).
    */
    public function allowedStatusColumnValues(): array
    {
        return [
            'draft',
            'kot',
            'billed',
            'paid',
            'placed',
            'confirmed',
            'preparing',
            'food_ready',
            'ready_for_pickup',
            'out_for_delivery',
            'served',
            'delivered',
            'cancelled',
            'open',
            'closed',
        ];
    }

    public function sanitizeOrderStatus(?string $value, ?string $fallback = 'placed'): ?string
    {
        $value = $this->normalize($value);
        $allowed = $this->allowedOrderStatuses();

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    public function sanitizeStatusColumn(?string $value, ?string $fallback = 'draft'): ?string
    {
        $value = $this->normalize($value);
        $allowed = $this->allowedStatusColumnValues();

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    /**
    * Lightweight audit of known enum conflicts for this module.
    */
    public function audit(): array
    {
        $issues = [];

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'order_status')) {
            $badOrders = $this->findInvalidOrderStatuses();
            if ($badOrders->isNotEmpty()) {
                $issues[] = [
                    'type' => 'order_status',
                    'message' => 'Found orders with invalid order_status values.',
                    'count' => $badOrders->count(),
                    'sample_ids' => $badOrders->take(50)->pluck('id'),
                ];
            }
        }

        return $issues;
    }

    private function findInvalidOrderStatuses()
    {
        $allowed = $this->allowedOrderStatuses();

        return DB::table('orders')
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->whereNotIn(DB::raw('LOWER(TRIM(order_status))'), $allowed)
            ->select('id', 'order_status')
            ->limit(200)
            ->get();
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim($value ?? ''));
    }
}

