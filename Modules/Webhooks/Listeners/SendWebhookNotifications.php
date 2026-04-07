<?php

namespace Modules\Webhooks\Listeners;

use App\Models\Restaurant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Modules\Webhooks\Entities\Webhook;
use Modules\Webhooks\Entities\WebhookDelivery;
use Modules\Webhooks\Entities\SystemWebhook;
use Modules\Webhooks\Entities\SystemWebhookDelivery;
use Modules\Webhooks\Jobs\DispatchWebhook;
use Modules\Webhooks\Jobs\DispatchSystemWebhook;
use Modules\Webhooks\Support\EventPayloadFactory;
use Modules\Webhooks\Support\WebhookRouting;
use Modules\Webhooks\Support\BranchResolver;
use Modules\Webhooks\Support\RestaurantResolver;
use App\Models\Module;
use Illuminate\Support\Facades\Log;

class SendWebhookNotifications implements ShouldQueue
{
    /** @var array<int, Restaurant|null> */
    private static array $restaurantByIdCache = [];

    /** @var array<int, bool> */
    private static array $moduleAllowedByRestaurantId = [];

    /** @var array<string, \Illuminate\Database\Eloquent\Collection<int, SystemWebhook>> */
    private static array $systemWebhooksByEventName = [];

    /** @var array<string, \Illuminate\Database\Eloquent\Collection<int, Webhook>> */
    private static array $tenantWebhooksByQueryKey = [];

    /**
     * Clear process-level caches (tests, long-lived queue workers after config changes).
     */
    public static function clearProcessCaches(): void
    {
        self::$restaurantByIdCache = [];
        self::$moduleAllowedByRestaurantId = [];
        self::$systemWebhooksByEventName = [];
        self::$tenantWebhooksByQueryKey = [];
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $payload = EventPayloadFactory::from($event);


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

        // Send to system webhooks (platform-wide, regardless of tenant)
        $this->dispatchToSystemWebhooks($eventName, $restaurantId, $branchId, $data, $source, $sourceModel);

        $restaurant = $this->resolveRestaurantForWebhook($restaurantId);
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

        $webhooks = $this->cachedTenantWebhooks($restaurantId, $branchId, $eventName, $source);

        foreach ($webhooks as $webhook) {
            $payloadData = [
                'id' => (string) Str::uuid(),
                'event' => $eventName,
                'created_at' => now()->toIso8601String(),
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'source_module' => $source,
                'source_model' => $sourceModel,
                'data' => $webhook->redact_payload ? \Modules\Webhooks\Support\Redactor::apply($data) : $data,
            ];

            $delivery = WebhookDelivery::create([
                'webhook_id' => $webhook->id,
                'restaurant_id' => $restaurantId,
                'branch_id' => $branchId,
                'event' => $eventName,
                'status' => 'pending',
                'attempts' => 0,
                'payload' => $payloadData,
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
        $systemWebhooks = $this->cachedSystemWebhooksForEvent($eventName);

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
        $rid = (int) $restaurant->id;
        if (array_key_exists($rid, self::$moduleAllowedByRestaurantId)) {
            return self::$moduleAllowedByRestaurantId[$rid];
        }

        if (function_exists('restaurant_modules')) {
            $modules = restaurant_modules($restaurant);
            if (in_array('Webhooks', $modules, true)) {
                self::$moduleAllowedByRestaurantId[$rid] = true;

                return true;
            }
        }

        $packageId = $restaurant->package_id ?? null;
        if (! $packageId) {
            self::$moduleAllowedByRestaurantId[$rid] = false;

            return false;
        }

        $allowed = Module::where('name', 'Webhooks')
            ->whereHas('packages', fn ($q) => $q->where('packages.id', $packageId))
            ->exists();
        self::$moduleAllowedByRestaurantId[$rid] = $allowed;

        return $allowed;
    }

    private function resolveRestaurantForWebhook(?int $restaurantId): ?Restaurant
    {
        if (! $restaurantId) {
            return restaurant();
        }

        if (! array_key_exists($restaurantId, self::$restaurantByIdCache)) {
            self::$restaurantByIdCache[$restaurantId] = Restaurant::find($restaurantId);
        }

        return self::$restaurantByIdCache[$restaurantId];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SystemWebhook>
     */
    private function cachedSystemWebhooksForEvent(string $eventName)
    {
        if (! isset(self::$systemWebhooksByEventName[$eventName])) {
            self::$systemWebhooksByEventName[$eventName] = SystemWebhook::query()
                ->where('is_active', true)
                ->where(function ($q) use ($eventName) {
                    $q->whereNull('subscribed_events')
                        ->orWhereJsonLength('subscribed_events', 0)
                        ->orWhereJsonContains('subscribed_events', $eventName);
                })
                ->get();
        }

        return self::$systemWebhooksByEventName[$eventName];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Webhook>
     */
    private function cachedTenantWebhooks(?int $restaurantId, ?int $branchId, string $eventName, string $source)
    {
        $qkey = ($restaurantId ?? 0) . ':' . ($branchId ?? 'null') . ':' . $eventName . ':' . $source;

        if (! isset(self::$tenantWebhooksByQueryKey[$qkey])) {
            self::$tenantWebhooksByQueryKey[$qkey] = Webhook::query()
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
        }

        return self::$tenantWebhooksByQueryKey[$qkey];
    }
}

