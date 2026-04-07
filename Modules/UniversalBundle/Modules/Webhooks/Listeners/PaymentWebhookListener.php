<?php

namespace Modules\Webhooks\Listeners;

use App\Models\Restaurant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Webhooks\Support\EventPayloadFactory;
use Modules\Webhooks\Entities\Webhook;
use Modules\Webhooks\Entities\WebhookDelivery;
use Modules\Webhooks\Jobs\DispatchWebhook;
use Illuminate\Support\Str;
use Modules\Webhooks\Support\WebhookRouting;
use Modules\Webhooks\Entities\SystemWebhook;
use Modules\Webhooks\Entities\SystemWebhookDelivery;
use Modules\Webhooks\Jobs\DispatchSystemWebhook;
use Modules\Webhooks\Support\BranchResolver;
use App\Models\Module;
use Modules\Webhooks\Support\RestaurantResolver;

class PaymentWebhookListener implements ShouldQueue
{
    public function handle(object $event): void
    {
        $payload = EventPayloadFactory::fromPaymentEvent($event);

        if (! $payload) {
            return;
        }

        [$eventName, $restaurantId, $branchId, $data, $source] = $payload;
        $branchId = BranchResolver::resolve($event, $branchId, $data);
        $restaurantId = RestaurantResolver::resolve($event, $restaurantId, $branchId, $data);
        $sourceModel = $data['source_model'] ?? null;

        if ($branchId && (empty($data['branch_id']) || $data['branch_id'] !== $branchId)) {
            $data['branch_id'] = $branchId;
        }
        if ($restaurantId && (empty($data['restaurant_id']) || $data['restaurant_id'] !== $restaurantId)) {
            $data['restaurant_id'] = $restaurantId;
        }

        // Send to system webhooks (platform-wide)
        $this->dispatchToSystemWebhooks($eventName, $restaurantId, $branchId, $data, $source, $sourceModel);

        $restaurant = Restaurant::find($restaurantId);
        if (! $restaurant) {
            return;
        }

        if (! $this->moduleAllowedForRestaurant($restaurant)) {
            return;
        }

        $packageId = $restaurant->package_id ?? null;

        if (! WebhookRouting::allows($restaurantId, $branchId, $packageId, $source, $eventName)) {
            return;
        }

        $webhooks = Webhook::query()
            ->where('is_active', true)
            ->where('restaurant_id', $restaurantId)
            ->where(function ($q) use ($branchId) {
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                } else {
                    $q->whereNull('branch_id');
                }
            })
            ->where(function ($q) use ($eventName) {
                $q->whereNull('subscribed_events')
                    ->orWhereJsonLength('subscribed_events', 0)
                    ->orWhereJsonContains('subscribed_events', $eventName);
            })
            ->where(function ($q) use ($source) {
                $q->whereNull('source_modules')
                    ->orWhereJsonLength('source_modules', 0)
                    ->orWhereJsonContains('source_modules', $source);
            })
            ->get();

        foreach ($webhooks as $webhook) {
            $delivery = WebhookDelivery::create([
                'webhook_id' => $webhook->id,
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'event' => $eventName,
                'status' => 'pending',
                'attempts' => 0,
                'payload' => [
                    'id' => (string) Str::uuid(),
                    'event' => $eventName,
                    'created_at' => now()->toIso8601String(),
                    'restaurant_id' => $restaurantId,
                    'branch_id' => $branchId,
                    'source_module' => $source,
                    'source_model' => $sourceModel,
                    'data' => $data,
                ],
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            DispatchWebhook::dispatch($delivery);
        }
    }

    /**
     * Dispatch event to all active system webhooks.
     */
    private function dispatchToSystemWebhooks(
        string $eventName,
        ?int $restaurantId,
        ?int $branchId,
        array $data,
        string $source,
        ?string $sourceModel
    ): void {
        // Get all active system webhooks subscribed to this event
        $systemWebhooks = SystemWebhook::query()
            ->where('is_active', true)
            ->where(function ($q) use ($eventName) {
                $q->whereNull('subscribed_events')
                    ->orWhereJsonLength('subscribed_events', 0)
                    ->orWhereJsonContains('subscribed_events', $eventName);
            })
            ->get();

        foreach ($systemWebhooks as $webhook) {
            $payloadData = [
                'id' => (string) Str::uuid(),
                'event' => $eventName,
                'created_at' => now()->toIso8601String(),
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'source_module' => $source,
                'source_model' => $sourceModel,
                'webhook_type' => 'system',
                'data' => $data,
            ];

            $delivery = SystemWebhookDelivery::create([
                'system_webhook_id' => $webhook->id,
                'restaurant_id' => $restaurantId,
                'event' => $eventName,
                'status' => 'pending',
                'attempts' => 0,
                'payload' => $payloadData,
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            DispatchSystemWebhook::dispatch($delivery);
        }
    }

    private function moduleAllowedForRestaurant(Restaurant $restaurant): bool
    {
        if (function_exists('restaurant_modules')) {
            $modules = restaurant_modules($restaurant);
            if (in_array('Webhooks', $modules, true)) {
                return true;
            }
        }

        $packageId = $restaurant->package_id ?? null;
        if (! $packageId) {
            return false;
        }

        return Module::where('name', 'Webhooks')
            ->whereHas('packages', fn ($q) => $q->where('packages.id', $packageId))
            ->exists();
    }
}
