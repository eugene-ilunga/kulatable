<?php

namespace Modules\Webhooks\Support;

use App\Models\Order;
use App\Models\Table;

class BranchResolver
{
    /** @var array<int, int|null> */
    private static array $tableBranchCache = [];

    /** @var array<int, int|null> */
    private static array $orderBranchCache = [];

    /**
     * Cached lookup shared with RestaurantResolver (one Order/Table query per id per process).
     */
    public static function cachedBranchIdForTable(int $tableId): ?int
    {
        if (! array_key_exists($tableId, self::$tableBranchCache)) {
            self::$tableBranchCache[$tableId] = Table::where('id', $tableId)->value('branch_id');
        }

        $v = self::$tableBranchCache[$tableId];

        return $v !== null ? (int) $v : null;
    }

    /**
     * @internal
     */
    public static function cachedBranchIdForOrder(int $orderId): ?int
    {
        if (! array_key_exists($orderId, self::$orderBranchCache)) {
            self::$orderBranchCache[$orderId] = Order::where('id', $orderId)->value('branch_id');
        }

        $v = self::$orderBranchCache[$orderId];

        return $v !== null ? (int) $v : null;
    }

    public static function resolve(object $event, ?int $branchId, array $data = []): ?int
    {
        if ($branchId) {
            return $branchId;
        }

        foreach (['order', 'reservation', 'kot', 'printJob'] as $property) {
            if (property_exists($event, $property) && isset($event->{$property}) && $event->{$property}) {
                $modelBranchId = $event->{$property}->branch_id ?? null;
                if ($modelBranchId) {
                    return $modelBranchId;
                }
            }
        }

        if (! empty($data['branch_id'])) {
            return (int) $data['branch_id'];
        }

        if (! empty($data['table_id'])) {
            $tableBranchId = self::cachedBranchIdForTable((int) $data['table_id']);
            if ($tableBranchId) {
                return $tableBranchId;
            }
        }

        if (! empty($data['order_id'])) {
            $orderBranchId = self::cachedBranchIdForOrder((int) $data['order_id']);
            if ($orderBranchId) {
                return $orderBranchId;
            }
        }

        return null;
    }
}
