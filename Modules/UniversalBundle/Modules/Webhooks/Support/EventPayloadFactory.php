<?php

namespace Modules\Webhooks\Support;

use App\Events\NewOrderCreated;
use App\Events\OrderUpdated;
use App\Events\ReservationConfirmationSent;
use App\Events\ReservationReceived;
use App\Events\SendOrderBillEvent;
use App\Events\OrderSuccessEvent;
use App\Events\NewRestaurantCreatedEvent;
use App\Events\PaymentSuccess;
use App\Events\PaymentFailed;
use App\Events\KotUpdated;
use App\Events\PrintJobCreated;
// Inventory module may define its own events; handle only if class exists when event is dispatched.
use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Support\Str;
use Modules\Webhooks\Support\NotificationPayloadFactory;

class EventPayloadFactory
{
    /**
     * Dedupe heavy Eloquent loads when the same webhook payload is built repeatedly (sync queue, duplicate events).
     * Entries are keyed by kot id with updated_at fingerprint so queue workers do not serve stale payloads after real updates.
     *
     * @var array<int, array{u:int, p:array{0:string,1:int|null,2:int|null,3:array,4:string}}>
     */
    private static array $kotUpdatedPayloadByKotId = [];

    /**
     * @var array<string, array{0:string,1:int|null,2:int|null,3:array,4:string}>
     */
    private static array $orderPayloadCache = [];

    /**
     * Shared order line-items snapshot per order version (dedupes OrderUpdated vs OrderSuccessEvent, etc.).
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    private static array $orderItemsLinesCache = [];

    public static function clearPayloadCaches(): void
    {
        self::$kotUpdatedPayloadByKotId = [];
        self::$orderPayloadCache = [];
        self::$orderItemsLinesCache = [];
    }

    /**
     * @return array{0:string,1:int|null,2:int|null,3:array,4:string}|null
     */
    public static function from(object $event): ?array
    {
        // Notifications: fall back early if it matches
        if ($notify = NotificationPayloadFactory::from($event)) {
            return $notify;
        }

        if ($event instanceof NewOrderCreated) {
            return self::fromOrder($event->order, 'order.created');
        }

        if ($event instanceof OrderUpdated) {
            return self::fromOrder($event->order, 'order.updated', [
                'action' => $event->action,
            ]);
        }

        if ($event instanceof ReservationReceived) {
            return self::fromReservation($event->reservation, 'reservation.received');
        }

        if ($event instanceof ReservationConfirmationSent) {
            return self::fromReservation($event->reservation, 'reservation.confirmed');
        }

        if ($event instanceof OrderSuccessEvent && property_exists($event, 'order') && $event->order instanceof Order) {
            return self::fromOrder($event->order, 'order.paid', ['payment_status' => 'paid']);
        }

        if ($event instanceof SendOrderBillEvent && isset($event->order)) {
            return self::fromOrder($event->order, 'order.bill_sent', ['payment_status' => $event->order->payment_status ?? null]);
        }

        if ($event instanceof NewRestaurantCreatedEvent) {
            return [
                'restaurant.created',
                $event->restaurant->id,
                null,
                [
                    'id' => $event->restaurant->id,
                    'name' => $event->restaurant->restaurant_name ?? $event->restaurant->name ?? null,
                    'hash' => $event->restaurant->hash ?? null,
                    'created_at' => $event->restaurant->created_at,
                ],
                'Onboarding',
            ];
        }

        if ($event instanceof KotUpdated && isset($event->kot)) {
            $kot = $event->kot;
            $kotId = (int) $kot->id;
            $kotUpdatedTs = $kot->updated_at instanceof \DateTimeInterface ? $kot->updated_at->getTimestamp() : 0;
            if (
                $kotId > 0
                && isset(self::$kotUpdatedPayloadByKotId[$kotId])
                && (int) (self::$kotUpdatedPayloadByKotId[$kotId]['u'] ?? 0) === $kotUpdatedTs
            ) {
                return self::clonePayloadTuple(self::$kotUpdatedPayloadByKotId[$kotId]['p']);
            }

            $kot->loadMissing([
                'items.menuItem',
                'items.menuItemVariation',
                'items.modifierOptions',
                'items.orderItem',
                'cancelReason',
                'order',
                'order.table',
                'kotPlace',
                'orderType',
            ]);

            $order = $kot->order;
            $table = $kot->table ?? $order?->table;
            $cancelReason = $kot->cancelReason;
            $items = self::buildKotItems($kot->items, $kot->order_type_id ?? null, $order?->delivery_app_id ?? null);
            $kotAmount = array_sum(array_map(static fn ($item) => (float) ($item['amount'] ?? 0), $items));

            $payload = [
                'kot.updated',
                $kot->restaurant_id ?? $order?->restaurant_id ?? null,
                $kot->branch_id ?? $order?->branch_id ?? null,
                [
                    'schema_version' => 1,
                    'source_model' => 'kot',
                    'id' => $kot->id,
                    'kot_number' => $kot->kot_number ?? null,
                    'token_number' => $kot->token_number ?? null,
                    'status' => $kot->status ?? null,
                    'note' => $kot->note ?? null,
                    'amount' => $kotAmount,
                    'order_id' => $kot->order_id ?? null,
                    'order_number' => $order?->order_number ?? $order?->show_formatted_order_number ?? null,
                    'order_type_id' => $kot->order_type_id ?? null,
                    'order_type' => $kot->orderType?->translated_name ?? $kot->orderType?->order_type_name ?? null,
                    'transaction_id' => $kot->transaction_id ?? null,
                    'branch_id' => $kot->branch_id ?? null,
                    'restaurant_id' => $kot->restaurant_id ?? $order?->restaurant_id ?? null,
                    'table_id' => $table?->id,
                    'table_code' => $table?->table_code ?? null,
                    'kot_place_id' => $kot->kitchen_place_id ?? null,
                    'kot_place' => $kot->kotPlace?->name ?? null,
                    'cancel_reason_id' => $kot->cancel_reason_id ?? null,
                    'cancel_reason_text' => $kot->cancel_reason_text ?? null,
                    'cancel_reason' => $cancelReason ? [
                        'id' => $cancelReason->id,
                        'reason' => $cancelReason->reason ?? null,
                        'cancel_order' => (bool) ($cancelReason->cancel_order ?? false),
                        'cancel_kot' => (bool) ($cancelReason->cancel_kot ?? false),
                    ] : null,
                    'items' => $items,
                    'created_at' => $kot->created_at ?? null,
                    'updated_at' => $kot->updated_at ?? null,
                ],
                'Kitchen',
            ];

            if ($kotId > 0) {
                $kotUpdatedTsAfter = $kot->updated_at instanceof \DateTimeInterface ? $kot->updated_at->getTimestamp() : 0;
                self::$kotUpdatedPayloadByKotId[$kotId] = [
                    'u' => $kotUpdatedTsAfter,
                    'p' => $payload,
                ];
            }

            return self::clonePayloadTuple($payload);
        }

        if ($event instanceof PrintJobCreated && isset($event->printJob)) {
            return [
                'printjob.created',
                $event->printJob->restaurant_id ?? null,
                $event->printJob->branch_id ?? null,
                [
                    'schema_version' => 1,
                    'id' => $event->printJob->id,
                    'type' => $event->printJob->type ?? null,
                    'status' => $event->printJob->status ?? null,
                    'branch_id' => $event->printJob->branch_id ?? null,
                    'created_at' => $event->printJob->created_at ?? null,
                ],
                'Kitchen',
            ];
        }

        if (class_exists('\\Modules\\Inventory\\Events\\InventoryStockUpdated') && $event instanceof \Modules\Inventory\Events\InventoryStockUpdated) {
            return [
                'inventory.stock_updated',
                $event->restaurantId ?? null,
                $event->branchId ?? null,
                [
                    'schema_version' => 1,
                    'inventory_item_id' => $event->inventoryItemId ?? null,
                    'change' => $event->change ?? null,
                    'reason' => $event->reason ?? null,
                    'branch_id' => $event->branchId ?? null,
                ],
                'Inventory',
            ];
        }

        return null;
    }

    /**
     * Handle payment gateways (if wired to custom events).
     *
     * @return array{0:string,1:int|null,2:int|null,3:array,4:string}|null
     */
    public static function fromPaymentEvent(object $event): ?array
    {
        if ($event instanceof PaymentSuccess) {
            return [
                'payment.success',
                $event->restaurant_id ?? null,
                $event->branch_id ?? null,
                [
                    'schema_version' => 1,
                    'gateway' => $event->gateway ?? null,
                    'transaction_id' => $event->transaction_id ?? null,
                    'amount' => $event->amount ?? null,
                    'currency' => $event->currency ?? null,
                    'order_id' => $event->order_id ?? null,
                ],
                'Payment',
            ];
        }

        if ($event instanceof PaymentFailed) {
            return [
                'payment.failed',
                $event->restaurant_id ?? null,
                $event->branch_id ?? null,
                [
                    'schema_version' => 1,
                    'gateway' => $event->gateway ?? null,
                    'transaction_id' => $event->transaction_id ?? null,
                    'amount' => $event->amount ?? null,
                    'currency' => $event->currency ?? null,
                    'order_id' => $event->order_id ?? null,
                    'reason' => $event->reason ?? null,
                ],
                'Payment',
            ];
        }

        return null;
    }

    private static function fromOrder(Order $order, string $eventName, array $extra = []): array
    {
        $orderUpdatedTs = $order->updated_at instanceof \DateTimeInterface ? $order->updated_at->getTimestamp() : 0;
        $cacheKey = $eventName . ':' . $order->id . ':' . sha1(serialize($extra)) . ':' . $orderUpdatedTs;
        if (isset(self::$orderPayloadCache[$cacheKey])) {
            return self::clonePayloadTuple(self::$orderPayloadCache[$cacheKey]);
        }

        $status = $order->status ?? $order->order_status ?? null;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

            $data = [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'number' => $order->order_number ?? $order->show_formatted_order_number ?? null,
                'status' => $status,
            'total' => $order->total ?? null,
            'order_type' => $order->order_type ?? null,
            'branch_id' => $order->branch_id,
            'restaurant_id' => $order->restaurant_id,
            'customer_id' => $order->customer_id,
            'table_id' => $order->table_id,
            'placed_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'payment_status' => $order->payment_status ?? null,
            'currency' => $order->currency ?? null,
            'hash' => method_exists($order, 'getAttribute') ? $order->getAttribute('hash') : null,
            'items' => self::orderItemsLinesForVersion($order, $orderUpdatedTs),
        ];

        if ($eventName === 'order.created') {
            $order->loadMissing([
                'kot.items.menuItem',
                'kot.items.menuItemVariation',
                'kot.items.modifierOptions',
                'kot.items.orderItem',
                'kot.cancelReason',
                'kot.orderType',
                'kot.kotPlace',
            ]);

            $data['kots'] = $order->kot->map(function ($kot) use ($order) {
                $items = self::buildKotItems($kot->items, $kot->order_type_id ?? null, $order?->delivery_app_id ?? null);
                $kotAmount = array_sum(array_map(static fn ($item) => (float) ($item['amount'] ?? 0), $items));
                $cancelReason = $kot->cancelReason;

                return [
                    'source_model' => 'kot',
                    'id' => $kot->id,
                    'kot_number' => $kot->kot_number ?? null,
                    'token_number' => $kot->token_number ?? null,
                    'status' => $kot->status ?? null,
                    'note' => $kot->note ?? null,
                    'amount' => $kotAmount,
                    'order_id' => $kot->order_id ?? null,
                    'order_type_id' => $kot->order_type_id ?? null,
                    'order_type' => $kot->orderType?->translated_name ?? $kot->orderType?->order_type_name ?? null,
                    'transaction_id' => $kot->transaction_id ?? null,
                    'branch_id' => $kot->branch_id ?? null,
                    'restaurant_id' => $kot->restaurant_id ?? null,
                    'kot_place_id' => $kot->kitchen_place_id ?? null,
                    'kot_place' => $kot->kotPlace?->name ?? null,
                    'cancel_reason_id' => $kot->cancel_reason_id ?? null,
                    'cancel_reason_text' => $kot->cancel_reason_text ?? null,
                    'cancel_reason' => $cancelReason ? [
                        'id' => $cancelReason->id,
                        'reason' => $cancelReason->reason ?? null,
                        'cancel_order' => (bool) ($cancelReason->cancel_order ?? false),
                        'cancel_kot' => (bool) ($cancelReason->cancel_kot ?? false),
                    ] : null,
                    'items' => $items,
                    'created_at' => $kot->created_at ?? null,
                    'updated_at' => $kot->updated_at ?? null,
                ];
            })->toArray();
        }

        $tuple = [
            $eventName,
            $order->restaurant_id,
            $order->branch_id,
            array_merge(['schema_version' => 1], $data, $extra),
            'Order',
        ];
        self::$orderPayloadCache[$cacheKey] = $tuple;

        return self::clonePayloadTuple($tuple);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function orderItemsLinesForVersion(Order $order, int $orderUpdatedTs): array
    {
        $ivKey = $order->id . ':' . $orderUpdatedTs;
        if (! isset(self::$orderItemsLinesCache[$ivKey])) {
            if (! method_exists($order, 'items')) {
                self::$orderItemsLinesCache[$ivKey] = [];
            } else {
                $order->loadMissing('items');
                self::$orderItemsLinesCache[$ivKey] = $order->items->map(function ($item) {
                    return [
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $item->name ?? null,
                        'quantity' => $item->quantity ?? null,
                        'price' => $item->price ?? null,
                        'total' => $item->total ?? null,
                    ];
                })->toArray();
            }
        }

        return self::$orderItemsLinesCache[$ivKey];
    }

    /**
     * @param  array{0:string,1:int|null,2:int|null,3:array,4:string}  $tuple
     * @return array{0:string,1:int|null,2:int|null,3:array,4:string}
     */
    private static function clonePayloadTuple(array $tuple): array
    {
        /** @var array{0:string,1:int|null,2:int|null,3:array,4:string} $copy */
        $copy = unserialize(serialize($tuple));

        return $copy;
    }

    private static function fromReservation(Reservation $reservation, string $eventName): array
    {
        $data = [
            'id' => $reservation->id,
            'restaurant_id' => $reservation->restaurant_id,
            'branch_id' => $reservation->branch_id,
            'table_id' => $reservation->table_id,
            'status' => $reservation->status ?? null,
            'reservation_date_time' => $reservation->reservation_date_time ?? $reservation->reservation_time ?? null,
            'name' => $reservation->name ?? null,
            'phone' => $reservation->phone ?? null,
            'party_size' => $reservation->party_size ?? null,
            'created_at' => $reservation->created_at,
            'updated_at' => $reservation->updated_at,
        ];

        return [
            $eventName,
            $reservation->restaurant_id,
            $reservation->branch_id,
            array_merge(['schema_version' => 1], $data),
            'Reservation',
        ];
    }

    private static function buildKotItems($kotItems, ?int $orderTypeId, ?int $deliveryAppId): array
    {
        return collect($kotItems)->map(function ($item) use ($orderTypeId, $deliveryAppId) {
            $menuItem = $item->menuItem;
            $variation = $item->menuItemVariation;
            $orderItem = $item->orderItem;

            $modifiers = $item->modifierOptions->map(function ($modifier) {
                return [
                    'id' => $modifier->id,
                    'name' => $modifier->name ?? null,
                    'price' => $modifier->price ?? null,
                ];
            })->values()->toArray();

            $modifiersTotal = array_sum(array_map(static fn ($modifier) => (float) ($modifier['price'] ?? 0), $modifiers));

            $unitPrice = $orderItem?->price;
            if ($unitPrice === null || (float) $unitPrice <= 0) {
                $basePrice = null;
                if ($menuItem && method_exists($menuItem, 'resolvePrice')) {
                    $basePrice = (float) $menuItem->resolvePrice($orderTypeId, $deliveryAppId, $item->menu_item_variation_id);
                }

                if ($basePrice <= 0 && $variation) {
                    $basePrice = (float) ($variation->price ?? 0);
                }

                if ($basePrice <= 0 && $menuItem) {
                    $basePrice = (float) ($menuItem->price ?? 0);
                }

                $unitPrice = $basePrice + $modifiersTotal;
            }

            $quantity = $item->quantity ?? 0;
            $lineAmount = $orderItem?->amount;
            if ($lineAmount === null || (float) $lineAmount <= 0) {
                $lineAmount = $unitPrice * $quantity;
            }

            return [
                'id' => $item->id,
                'menu_item_id' => $item->menu_item_id,
                'menu_item_name' => $menuItem?->item_name ?? $menuItem?->name ?? null,
                'menu_item_variation_id' => $item->menu_item_variation_id,
                'menu_item_variation' => $variation?->variation ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $lineAmount,
                'status' => $item->status ?? null,
                'cancel_reason_id' => $item->cancel_reason_id ?? null,
                'cancel_reason_text' => $item->cancel_reason_text ?? null,
                'modifiers' => $modifiers,
            ];
        })->toArray();
    }
}
