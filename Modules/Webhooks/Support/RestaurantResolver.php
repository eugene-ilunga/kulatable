<?php

namespace Modules\Webhooks\Support;

use App\Models\Branch;

class RestaurantResolver
{
    /** @var array<int, int|null> branch id => restaurant_id */
    private static array $branchRestaurantCache = [];

    /** @var array<int, int|null> order id => restaurant_id */
    private static array $orderRestaurantCache = [];

    /** @var array<int, int|null> table id => restaurant_id */
    private static array $tableRestaurantCache = [];

    private static function restaurantIdForBranch(int $branchId): ?int
    {
        if (! array_key_exists($branchId, self::$branchRestaurantCache)) {
            self::$branchRestaurantCache[$branchId] = Branch::where('id', $branchId)->value('restaurant_id');
        }

        $v = self::$branchRestaurantCache[$branchId];

        return $v !== null ? (int) $v : null;
    }

    private static function restaurantIdForOrder(int $orderId): ?int
    {
        if (! array_key_exists($orderId, self::$orderRestaurantCache)) {
            $orderBranch = BranchResolver::cachedBranchIdForOrder($orderId);
            self::$orderRestaurantCache[$orderId] = $orderBranch
                ? self::restaurantIdForBranch($orderBranch)
                : null;
        }

        return self::$orderRestaurantCache[$orderId];
    }

    private static function restaurantIdForTable(int $tableId): ?int
    {
        if (! array_key_exists($tableId, self::$tableRestaurantCache)) {
            $tableBranch = BranchResolver::cachedBranchIdForTable($tableId);
            self::$tableRestaurantCache[$tableId] = $tableBranch
                ? self::restaurantIdForBranch($tableBranch)
                : null;
        }

        return self::$tableRestaurantCache[$tableId];
    }

    public static function resolve(object $event, ?int $restaurantId, ?int $branchId, array $data = []): ?int
    {
        if ($restaurantId) {
            return $restaurantId;
        }

        if (property_exists($event, 'restaurant_id') && $event->restaurant_id) {
            return (int) $event->restaurant_id;
        }

        foreach (['order', 'reservation', 'kot', 'printJob', 'restaurant'] as $property) {
            if (property_exists($event, $property) && isset($event->{$property}) && $event->{$property}) {
                $model = $event->{$property};
                if (! empty($model->restaurant_id)) {
                    return (int) $model->restaurant_id;
                }
                if (! empty($model->branch_id)) {
                    $fromBranch = self::restaurantIdForBranch((int) $model->branch_id);
                    if ($fromBranch) {
                        return $fromBranch;
                    }
                }
            }
        }

        if ($branchId) {
            $fromBranch = self::restaurantIdForBranch((int) $branchId);
            if ($fromBranch) {
                return $fromBranch;
            }
        }

        if (! empty($data['restaurant_id'])) {
            return (int) $data['restaurant_id'];
        }

        if (! empty($data['branch_id'])) {
            $fromBranch = self::restaurantIdForBranch((int) $data['branch_id']);
            if ($fromBranch) {
                return $fromBranch;
            }
        }

        if (! empty($data['table_id'])) {
            $fromRestaurant = self::restaurantIdForTable((int) $data['table_id']);
            if ($fromRestaurant) {
                return $fromRestaurant;
            }
        }

        if (! empty($data['order_id'])) {
            $fromRestaurant = self::restaurantIdForOrder((int) $data['order_id']);
            if ($fromRestaurant) {
                return $fromRestaurant;
            }
        }

        return null;
    }
}
