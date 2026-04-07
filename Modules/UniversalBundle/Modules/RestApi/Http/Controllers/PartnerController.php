<?php

namespace Modules\RestApi\Http\Controllers;

use App\Models\DeliveryExecutive;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Controller;
use Modules\RestApi\Entities\DeliveryExecutiveLocation;
use Modules\RestApi\Entities\DeliveryPartnerDeviceToken;
use Modules\RestApi\Traits\ApiResponse;

class PartnerController extends Controller
{
    use ApiResponse;

    /**
     * Get partner profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Request $request)
    {
        $partner = $request->get('partner') ?? $request->user();

        if (!$partner instanceof DeliveryExecutive) {
            return $this->errorResponse('Partner not found', 404);
        }

        $ordersQuery = Order::where('delivery_executive_id', $partner->id)
            ->where('order_status', OrderStatus::DELIVERED);

        $totalDeliveries = (clone $ordersQuery)->count();

        $todaysDeliveries = (clone $ordersQuery)
            ->whereDate('delivered_at', Carbon::today())
            ->count();

        return $this->successResponse([
            'id' => $partner->id,
            'name' => $partner->name,
            'phone' => $partner->phone,
            'photo' => $partner->photo,
            'status' => $partner->status,
            'branch_id' => $partner->branch_id,
            'unique_code' => $partner->unique_code,
            'branch' => $partner->branch ? [
                'id' => $partner->branch->id,
                'name' => $partner->branch->name,
            ] : null,
            'restaurant' => $partner->branch && $partner->branch->restaurant ?  $partner->branch->restaurant->name : null,

            'totalDeliveries' => $totalDeliveries,
            'todaysDeliveries' => $todaysDeliveries,
            'onboardingDate' => $partner->created_at?->format('Y-m-d'),
        ], 'Profile retrieved successfully');
    }

    /**
     * Get latest active order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLatestOrder(Request $request)
    {
        $partner = $request->get('partner') ?? $request->user();

        if (!$partner instanceof \App\Models\DeliveryExecutive) {
            return $this->errorResponse('Partner not found', 404);
        }

        $order = Order::where('delivery_executive_id', $partner->id)
            ->whereIn('order_status', [
                OrderStatus::CONFIRMED->value,
                OrderStatus::PREPARING->value,
                OrderStatus::FOOD_READY->value,
                OrderStatus::PICKED_UP->value,
                OrderStatus::OUT_FOR_DELIVERY->value,
                OrderStatus::READY_FOR_PICKUP->value,
                OrderStatus::REACHED_DESTINATION->value,

            ])
            ->with([
                'customer',
                'customer.latestDeliveryAddress',
                'items.menuItem',
                'items.menuItemVariation',
                'branch.restaurant',
                'orderType',
                'deliveryExecutive',
            ])
            ->latest()
            ->first();

        if ($order) {
            $branch = $order->branch;
            $restaurant = $branch?->restaurant;
            $restaurantCoordinates = [
                'latitude' => (float)($branch->lat ?? 0),
                'longitude' => (float)($branch->lng ?? 0),
            ];

            $customer = $order->customer;
            $customerDelivery = $customer?->latestDeliveryAddress;
            $customerCoordinates = [
                'latitude' => (float)($customerDelivery->lat ?? 0),
                'longitude' => (float)($customerDelivery->lng ?? 0),
            ];

            $statusMap = [
                OrderStatus::OUT_FOR_DELIVERY->value => 'out_for_delivery',
                OrderStatus::READY_FOR_PICKUP->value => 'ready_for_pickup',
            ];
            $status = $statusMap[$order->order_status->value] ?? $order->order_status->value;

            $partnerData = [
                'name' => $partner->name ?? '',
                'contact' => $partner->phone ?? '',
            ];

            // Build items array with name, quantity, price (MenuItem uses item_name, not name)
            $items = $order->items->map(function ($item) {
                $name = $item->menuItem
                    ? ($item->menuItem->item_name ?? $item->menuItem->getTranslatedValue('item_name'))
                    : '';
                if ($name === '' && $item->menuItemVariation) {
                    $name = $item->menuItemVariation->name ?? $item->menuItemVariation->variation ?? '';
                }
                return [
                    'name' => $name,
                    'quantity' => (int) $item->quantity,
                    'price' => $item->price ?? 0,
                    'total' => $item->total ?? (($item->price ?? 0) * (int) $item->quantity),
                ];
            })->toArray();

            $instructions = $order->delivery_instructions ?? null;

            $modeMap = [
                'cod' => 'COD',
                'cash_on_delivery' => 'COD',
                'online' => 'Prepaid',
                'prepaid' => 'Prepaid',
            ];
            $paymentMode = $modeMap[strtolower($order->payment_mode ?? '')] ?? ($order->payment_mode ?? 'Prepaid');
            $currencySymbol = method_exists($order, 'currency_symbol') ? $order->currency_symbol() : '₹';
            $paymentAmount = $currencySymbol . number_format($order->grand_total ?? 0, 0);

            // Distance/duration calculation
            $restaurantLat = $restaurantCoordinates['latitude'];
            $restaurantLng = $restaurantCoordinates['longitude'];
            $customerLat = $customerCoordinates['latitude'];
            $customerLng = $customerCoordinates['longitude'];
            $distanceToCustomer = $durationToCustomer = null;

            if ($restaurantLat && $restaurantLng && $customerLat && $customerLng) {
                $earthRadius = 6371; // km
                $latFrom = deg2rad($restaurantLat);
                $lngFrom = deg2rad($restaurantLng);
                $latTo = deg2rad($customerLat);
                $lngTo = deg2rad($customerLng);
                $latDelta = $latTo - $latFrom;
                $lngDelta = $lngTo - $lngFrom;

                $a = pow(sin($latDelta / 2), 2) +
                     cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2);
                $angle = 2 * asin(sqrt($a));
                $distance = $earthRadius * $angle;
                $distanceToCustomer = round($distance, 2) . ' km';

                $averageSpeedKmh = 25;
                $averageSpeedKpm = $averageSpeedKmh / 60;
                $estimatedMinutes = ($averageSpeedKpm > 0) ? max(1, round($distance / $averageSpeedKpm)) : null;
                $durationToCustomer = $estimatedMinutes ? str_pad($estimatedMinutes, 2, '0', STR_PAD_LEFT) . ' mins' : null;
            }

            $dropETA = $durationToCustomer ?? '---';

            $currency = $restaurant->currency;

            // Timeline construction condensed
            $timeline = [];
            if ($order->created_at) {
                $timeline[] = ['time' => $order->created_at->format('H:i'), 'label' => 'Order placed'];
            }
            if (!empty($order->ready_at)) {
                $timeline[] = ['time' => $order->ready_at->format('H:i'), 'label' => 'Ready for pickup'];
            }
            if (!empty($order->picked_at)) {
                $timeline[] = ['time' => $order->picked_at->format('H:i'), 'label' => 'Picked up'];
            }
            if (!empty($order->delivered_at)) {
                $timeline[] = ['time' => $order->delivered_at->format('H:i'), 'label' => 'Delivered'];
            }

            $currency = $restaurant->currency;

            $result = [
                'id' => $order->id,
                'uid' => $order->uuid,
                'order_number' => $order->order_number ?? ('ORD-' . $order->id),
                'status' => $status,
                'restaurant' => [
                    'name' => $restaurant->name ?? '',
                    'address' => $restaurant->address ?? '',
                    'contact' => $restaurant->phone ?? '',
                    'coordinates' => $restaurantCoordinates,
                ],
                'customer' => [
                    'name' => $customer->name ?? '',
                    'phone' => $customer->phone ?? '',
                    'address' => $customerDelivery->address ?? '',
                    'coordinates' => $customerCoordinates,
                ],
                'partner' => $partnerData,
                'dropETA' => $dropETA,
                'distanceToCustomer' => $distanceToCustomer,
                'instructions' => $instructions,
                'payment' => [
                    'status' => $order->status,
                    'total_amount' => $order->total,
                    'due_amount' => ($order->total - $order->amount_paid),
                    'paid_amount' => $order->amount_paid,
                ],
                'timeline' => $timeline,
                'items' => $items,
                'total' => $order->total,
                'currency' => $currency->currency_name,
                'currency_symbol' => $currency->currency_symbol,
                'currency_code' => $currency->currency_code,
            ];
        }

        if (!$order) {
            return $this->successResponse(null, 'No active order found');
        }

        return $this->successResponse($result, 'Active order retrieved successfully');
    }

    /**
     * Get order history
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderHistory(Request $request)
    {
        $partner = $request->get('partner') ?? $request->user();

        if (!$partner instanceof DeliveryExecutive) {
            return $this->errorResponse('Partner not found', 404);
        }

        $perPage = $request->get('per_page', 15);
        $orders = Order::where('delivery_executive_id', $partner->id)
            ->with([
                'customer',
                'customer.latestDeliveryAddress',
                'items.menuItem',
                'items.menuItemVariation',
                'branch',
                'branch.restaurant',
                'orderType',
            ])
            ->latest()
            ->paginate($perPage);
        // get all data like latestOrder function
        $orderHistoryData = [];

        foreach ($orders as $order) {
            $branch = $order->branch ?? null;
            $restaurant = $branch ? $branch->restaurant : null;

            $restaurantCoordinates = [
                'latitude' => $branch ? (float)($branch->lat ?? 0) : 0,
                'longitude' => $branch ? (float)($branch->lng ?? 0) : 0,
            ];

            $customer = $order->customer;
            $customerDelivery = $customer && $customer->latestDeliveryAddress ? $customer->latestDeliveryAddress : null;
            $customerCoordinates = [
                'latitude' => $customerDelivery ? (float)($customerDelivery->lat ?? 0) : 0,
                'longitude' => $customerDelivery ? (float)($customerDelivery->lng ?? 0) : 0,
            ];

            $statusMap = [
                OrderStatus::OUT_FOR_DELIVERY->value => 'out_for_delivery',
                OrderStatus::READY_FOR_PICKUP->value => 'ready_for_pickup',
            ];
            $status = $statusMap[$order->order_status->value] ?? $order->order_status->value;

            $partnerData = [
                'name' => $partner->name ?? '',
                'contact' => $partner->phone ?? '',
            ];

            $items = [];
            foreach ($order->items as $item) {
                $name = $item->menuItem
                    ? ($item->menuItem->item_name ?? $item->menuItem->getTranslatedValue('item_name'))
                    : '';
                if ($name === '' && $item->menuItemVariation) {
                    $name = $item->menuItemVariation->name ?? $item->menuItemVariation->variation ?? '';
                }
                $items[] = [
                    'name' => $name,
                    'quantity' => (int) $item->quantity,
                    'price' => $item->price ?? 0,
                    'total' => $item->total ?? (($item->price ?? 0) * (int) $item->quantity),
                ];
            }

            $currency = $restaurant->currency;

            // Add dropETA, distanceToCustomer, instructions, payment, timeline if needed
            // Use uuid as order_id for consistency with getLatestOrder (for API calls).
            $orderHistoryData[] = [
                'id' => $order->id,
                'uid' => $order->uuid,
                'order_number' => $order->order_number ?? ('ORD-' . $order->id),
                'status' => $status,
                'created_at' => $order->created_at,
                'branch' => $branch ? [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'coordinates' => $restaurantCoordinates,
                    // Add restaurant info if needed
                ] : null,
                'restaurant' => $restaurant ? [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'address' => $restaurant->address ?? '',
                    'contact' => $restaurant->phone_code.' '.$restaurant->phone_number ?? '',
                    'coordinates' => $restaurantCoordinates,
                ] : null,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'contact' => $customer->phone,
                    'address' => $customerDelivery->address ?? '',
                    'coordinates' => $customerCoordinates,
                ] : null,
                'partner' => $partnerData,
                'dropETA' => null, // or code to calculate if needed
                'distanceToCustomer' => null,
                'instructions' => $order->instructions ?? null,
                'payment' => [
                    'status' => $order->status,
                    'total_amount' => $order->total,
                    'due_amount' => ($order->total - $order->amount_paid),
                    'paid_amount' => $order->amount_paid,
                ],
                'timeline' => null,
                'items' => $items,
                'total' => $order->total,
                'currency' => $currency->currency_name,
                'currency_symbol' => $currency->currency_symbol,
                'currency_code' => $currency->currency_code,
            ];
        }

        // Overwrite $transformedOrders with the detailed array for response
        $transformedOrders = collect($orderHistoryData);

        return $this->paginatedResponse(
            $orders->setCollection($transformedOrders),
            'Order history retrieved successfully'
        );
    }

    /**
     * Start order (accept order)
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function startOrder(Request $request, string $id)
    {
        $partner = $request->get('partner') ?? $request->user();

        if (!$partner instanceof DeliveryExecutive) {
            return $this->errorResponse('Partner not found', 404);
        }

        $order = Order::where('delivery_executive_id', $partner->id)
            ->where(function ($q) use ($id) {
                $q->where('uuid', $id)
                    ->orWhere('id', $id);
            })
            ->first();

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        if ($order->delivery_executive_id && $order->delivery_executive_id !== $partner->id) {
            return $this->errorResponse('Order is assigned to another partner', 403);
        }

        // Assign order to partner
        $order->delivery_executive_id = $partner->id;
        $order->order_status = OrderStatus::OUT_FOR_DELIVERY->value;
        $order->save();

        // Update partner status
        $partner->status = 'on_delivery';
        $partner->save();

        return $this->successResponse($this->transformOrder($order), 'Order started successfully');
    }

    /**
     * Update order status. Enforces valid delivery flow:
     * food_ready/ready_for_pickup → picked_up → out_for_delivery → reached_destination → delivered.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $partner = $request->get('partner') ?? $request->user();

        if (!$partner instanceof DeliveryExecutive) {
            return $this->errorResponse('Partner not found', 404);
        }

        $order = Order::where(function ($q) use ($id) {
            $q->where('uuid', $id)
                ->orWhere('id', $id);
        })
            ->where('delivery_executive_id', $partner->id)
            ->first();

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        $requestedStatus = OrderStatus::tryFrom($request->status);
        if (!$requestedStatus) {
            return $this->errorResponse('Invalid status value', 422);
        }

        $current = $order->order_status;

        // Validate allowed transitions (including delivered)
        $allowedFrom = [
            OrderStatus::PICKED_UP->value => [OrderStatus::READY_FOR_PICKUP->value, OrderStatus::FOOD_READY->value],
            OrderStatus::OUT_FOR_DELIVERY->value => [OrderStatus::PICKED_UP->value],
            OrderStatus::REACHED_DESTINATION->value => [OrderStatus::OUT_FOR_DELIVERY->value],
            OrderStatus::DELIVERED->value => [OrderStatus::REACHED_DESTINATION->value],
        ];

        if (isset($allowedFrom[$requestedStatus->value])) {
            if (! in_array($current->value, $allowedFrom[$requestedStatus->value], true)) {
                $allowed = implode('" or "', array_map(fn ($s) => OrderStatus::from($s)->label(), $allowedFrom[$requestedStatus->value]));
                return $this->errorResponse(
                    sprintf('Order can only be set to "%s" when current status is "%s". Current status is "%s".', $requestedStatus->label(), $allowed, $current->label()),
                    400
                );
            }
        }

        $order->order_status = $requestedStatus;
        $order->save();

        // When marking as delivered, set partner back to available
        if ($requestedStatus === OrderStatus::DELIVERED) {
            $partner->status = 'available';
            $partner->save();
        }

        return $this->successResponse($this->transformOrder($order), 'Order status updated successfully');
    }

    /**
     * Store partner's current location for an order (for delivery tracking).
     * Expects: order_id (or id), latitude, longitude. restaurant_id and branch_id are taken from the order.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeLocation(Request $request)
    {
        $request->validate([
            'order_id' => 'required_without:id|nullable|string',
            'id' => 'required_without:order_id|nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $orderId = $request->input('order_id') ?? $request->input('id');
        $latitude = (float) $request->input('latitude');
        $longitude = (float) $request->input('longitude');

        $partner = $request->get('partner') ?? $request->user();
        if (!$partner instanceof DeliveryExecutive) {
            return $this->errorResponse('Partner not found', 404);
        }

        $order = Order::where('delivery_executive_id', $partner->id)
            ->where(function ($q) use ($orderId) {
                $q->where('uuid', $orderId)->orWhere('id', $orderId);
            })
            ->with('branch')
            ->first();

        if (!$order) {
            return $this->notFoundResponse('Order not found or not assigned to you');
        }

        $branchId = $order->branch_id;
        $restaurantId = $order->branch?->restaurant_id;

        if (!$restaurantId) {
            return $this->errorResponse('Order branch or restaurant not found', 422);
        }

        // If a location record for this partner and order exists, update it, else create a new one
        $location = DeliveryExecutiveLocation::updateOrCreate(
            [
                'delivery_executive_id' => $partner->id,
                'order_id' => $order->id,
            ],
            [
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]
        );

        return $this->successResponse([
            'id' => $location->id,
            'order_id' => $order->uuid,
            'restaurant_id' => (int) $restaurantId,
            'branch_id' => (int) $branchId,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'recorded_at' => $location->created_at->toIso8601String(),
        ], 'Location recorded successfully');
    }

    /**
     * Register or update FCM token for push notifications (order assigned, cancelled, ready for pickup).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerFcmToken(Request $request)
    {
        $partner = $request->get('partner') ?? $request->user();

        if (!$partner instanceof DeliveryExecutive) {
            return $this->errorResponse('Partner not found', 404);
        }

        $request->validate([
            'fcm_token' => 'required|string|max:500',
            'platform' => 'nullable|string|in:android,ios|max:20',
        ]);

        $token = $request->input('fcm_token');
        $platform = $request->input('platform');

        DeliveryPartnerDeviceToken::updateOrCreate(
            [
                'delivery_executive_id' => $partner->id,
                'fcm_token' => $token,
            ],
            ['platform' => $platform]
        );

        return $this->successResponse(null, 'FCM token registered successfully');
    }

    /**
     * Transform order data for response
     *
     * @param Order $order
     * @return array
     */
    protected function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'order_status' => $order->order_status->value,
            'order_status_label' => $order->order_status->label(),
            'order_type' => $order->order_type ?? null,
            'date_time' => $order->date_time?->toIso8601String(),
            'total' => $order->total,
            'delivery_address' => $order->delivery_address,
            'delivery_time' => $order->delivery_time?->toIso8601String(),
            'estimated_delivery_time' => $order->estimated_delivery_time?->toIso8601String(),
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
                'phone_code' => $order->customer->phone_code,
            ] : null,
            'branch' => $order->branch ? [
                'id' => $order->branch->id,
                'name' => $order->branch->name,
                'address' => $order->branch->address,
            ] : null,
            'items' => $order->items->map(function ($item) {
                $itemName = $item->menuItem
                    ? ($item->menuItem->item_name ?? $item->menuItem->getTranslatedValue('item_name'))
                    : null;
                return [
                    'id' => $item->id,
                    'menu_item_name' => $itemName,
                    'variation_name' => $item->menuItemVariation?->name ?? $item->menuItemVariation?->variation ?? null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                ];
            }),
        ];
    }

}
