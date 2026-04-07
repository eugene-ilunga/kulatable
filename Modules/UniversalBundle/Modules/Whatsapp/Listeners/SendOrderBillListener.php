<?php

namespace Modules\Whatsapp\Listeners;

use App\Events\SendOrderBillEvent;
use Modules\Whatsapp\Entities\WhatsAppNotificationPreference;
use Modules\Whatsapp\Services\WhatsAppPhoneResolver;
use Modules\Whatsapp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendOrderBillListener
{
    protected WhatsAppNotificationService $notificationService;

    public function __construct(WhatsAppNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(SendOrderBillEvent $event): void
    {
        try {
            $order = $event->order;
            $restaurantId = $order->branch->restaurant_id ?? null;

            if (!$restaurantId) {
                return;
            }

            // Check if WhatsApp module is in restaurant's package
            if (function_exists('restaurant_modules')) {
                $restaurant = $order->branch->restaurant ?? \App\Models\Restaurant::find($restaurantId);
                if ($restaurant) {
                    $restaurantModules = restaurant_modules($restaurant);
                    if (!in_array('Whatsapp', $restaurantModules)) {
                        return;
                    }
                }
            }

            // Check if notification is enabled for customer
            $customerPreference = WhatsAppNotificationPreference::where('restaurant_id', $restaurantId)
                ->where(function ($query) {
                    $query->where('notification_type', 'order_bill_invoice')
                        ->orWhere('notification_type', 'order_notifications');
                })
                ->where('recipient_type', 'customer')
                ->where('is_enabled', true)
                ->first();

            $customerPhone = WhatsAppPhoneResolver::fromCustomer($order->customer);
            if ($customerPreference && $customerPhone) {
                $variables = $this->getOrderBillVariables($order);
                
                $this->notificationService->send(
                    $restaurantId,
                    'order_bill_invoice',
                    $customerPhone,
                    $variables
                );
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp Order Bill Listener Error: ' . $e->getMessage(), [
                'order_id' => $event->order->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getOrderBillVariables($order): array
    {
        $customerName = $order->customer->name ?? 'Customer';
        $orderNumber = $order->show_formatted_order_number ?? 'N/A';
        $totalAmount = $order->total_amount ?? 0;
        $currency = $order->branch->restaurant->currency->currency_symbol ?? '';
        $paymentStatus = $order->payment_status ?? 'Pending';
        $restaurantName = $order->branch->restaurant->name ?? '';
        $contactNumber = $order->branch->restaurant->contact_number ?? '';

        $restaurantHash = $order->branch->restaurant->hash ?? null;

        return [
            $customerName,        // [0] Customer name
            $orderNumber,         // [1] Order number
            $currency . number_format($totalAmount, 2), // [2] Amount
            ucfirst($paymentStatus), // [3] Payment method
            $order->id ?? null,   // [4] Order ID (for button URL)
            $restaurantHash,      // [5] Restaurant hash (for button URL)
        ];
    }
}
